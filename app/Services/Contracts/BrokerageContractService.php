<?php

namespace App\Services\Contracts;

use App\Models\BrokerageContract;
use App\Models\BrokerageContractSigner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrokerageContractService
{
    public function snapshot(BrokerageContract $contract, bool $requireSignature = false): array
    {
        $contract->load('agent');
        $path = $contract->agent?->signature_path;
        $disk = Storage::disk('local');
        $signature = null;
        if ($path && $disk->exists($path)) {
            $signature = 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path));
        }
        if ($requireSignature && ! $signature) {
            throw ValidationException::withMessages(['agent_id' => 'Bitte zuerst im Profil des Bearbeiters eine Unterschrift hinterlegen.']);
        }
        if ($requireSignature && ! $contract->offer_price) {
            throw ValidationException::withMessages(['offer_price' => 'Für den fertigen Vertrag ist ein Angebotspreis erforderlich.']);
        }

        return [
            'uuid' => $contract->uuid,
            'broker_name' => config('brokerage.broker_name'),
            'broker_address' => config('brokerage.broker_address'),
            'broker_email' => config('brokerage.broker_email'),
            'agent_name' => $contract->agent?->name,
            'agent_signature' => $signature,
            'sellers' => $contract->sellers,
            'property_address' => $contract->property_address,
            'property_description' => $contract->property_description,
            'offer_price' => $contract->offer_price,
            'commission_percent' => $contract->commission_percent,
            'starts_on' => $contract->starts_on->format('d.m.Y'),
            'ends_on' => $contract->ends_on->format('d.m.Y'),
            'notice_months' => $contract->notice_months,
            'renewal_months' => $contract->renewal_months,
            'signing_place' => $contract->signing_place,
            'issued_on' => now()->timezone('Europe/Berlin')->format('d.m.Y'),
            'special_agreements' => $contract->special_agreements,
            'template_version' => config('brokerage.template_version'),
            'artwork' => json_decode(file_get_contents(resource_path('contracts/brand/artwork.json')), true, flags: JSON_THROW_ON_ERROR),
            'terms' => json_decode(file_get_contents(resource_path('contracts/sale-terms.json')), true, flags: JSON_THROW_ON_ERROR),
            'logo' => is_file(public_path('images/logo-2comehome.png'))
                ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-2comehome.png'))) : null,
        ];
    }

    public function bytes(BrokerageContract $contract): string
    {
        $document = $contract->snapshot ?? $this->snapshot($contract);
        $view = isset($document['artwork']) ? 'pdf.brokerage-contract-branded' : 'pdf.brokerage-contract';

        $pdf = Pdf::loadView($view, [
            'document' => $document,
            'draft' => $contract->status === 'draft',
            'signers' => $contract->signers()->get(),
        ])->setPaper('a4')->setOption('isRemoteEnabled', false);

        if (isset($document['artwork'])) {
            $pdf->setOptions([
                'fontDir' => storage_path('framework/cache'),
                'fontCache' => storage_path('framework/cache'),
            ], mergeWithDefaults: true);
        }

        return $pdf->output();
    }

    public function finalize(BrokerageContract $contract): void
    {
        $createdPath = null;
        try {
            DB::transaction(function () use ($contract, &$createdPath) {
                $locked = BrokerageContract::query()->lockForUpdate()->findOrFail($contract->id);
                abort_unless($locked->status === 'draft', 409, 'Dieser Vertrag wurde bereits fertiggestellt.');
                $locked->snapshot = $this->snapshot($locked, true);
                $locked->status = 'created';
                $locked->finalized_at = now();
                $locked->signing_expires_at = now()->addDays(config('brokerage.signing_link_days'));
                foreach ($locked->sellers as $position => $seller) {
                    $locked->signers()->create([
                        'position' => $position, 'name' => $seller['name'], 'email' => $seller['email'] ?? null,
                    ]);
                }
                $bytes = $this->bytes($locked);
                $createdPath = 'brokerage-contracts/'.$locked->uuid.'/'.Str::uuid().'.pdf';
                throw_unless(Storage::disk('local')->put($createdPath, $bytes), \RuntimeException::class, 'PDF konnte nicht gespeichert werden.');
                $locked->pdf_path = $createdPath;
                $locked->pdf_sha256 = hash('sha256', $bytes);
                $locked->save();
            });
        } catch (\Throwable $e) {
            if ($createdPath) {
                Storage::disk('local')->delete($createdPath);
            }
            throw $e;
        }
    }

    public function sign(BrokerageContractSigner $signer, array $data): void
    {
        $image = $this->validateSignature($data['signature']);
        $createdPath = null;
        try {
            DB::transaction(function () use ($signer, $data, $image, &$createdPath) {
                // Serialize all sellers of a contract so concurrent signatures cannot overwrite a PDF.
                $contract = BrokerageContract::query()->lockForUpdate()->findOrFail($signer->brokerage_contract_id);
                abort_if($contract->links_revoked_at || ! $contract->signing_expires_at?->isFuture(), 410, 'Dieser Link ist nicht mehr gültig.');
                abort_unless(in_array($contract->status, ['created', 'partially_signed', 'signed'], true), 409);
                $locked = BrokerageContractSigner::query()->lockForUpdate()->findOrFail($signer->id);
                abort_if($locked->signed_at, 409, 'Die Unterschrift wurde bereits gespeichert.');
                $locked->forceFill([
                    'signature_data' => $image, 'signing_place' => $data['signing_place'],
                    'signed_at' => now(), 'terms_accepted_at' => now(),
                    'early_start_requested' => $data['early_start_requested'] ?? false,
                    'withdrawal_loss_acknowledged' => $data['withdrawal_loss_acknowledged'] ?? false,
                    'document_sha256' => $contract->pdf_sha256,
                ])->save();
                $contract->status = $contract->signers()->whereNull('signed_at')->exists() ? 'partially_signed' : 'signed';
                $bytes = $this->bytes($contract);
                // Keep every issued PDF, including intermediate signatures.
                $createdPath = 'brokerage-contracts/'.$contract->uuid.'/'.Str::uuid().'.pdf';
                throw_unless(Storage::disk('local')->put($createdPath, $bytes), \RuntimeException::class, 'PDF konnte nicht gespeichert werden.');
                $contract->signed_pdf_path = $createdPath;
                $contract->signed_pdf_sha256 = hash('sha256', $bytes);
                $contract->save();
            });
        } catch (\Throwable $e) {
            if ($createdPath) {
                Storage::disk('local')->delete($createdPath);
            }
            throw $e;
        }
    }

    private function validateSignature(string $data): string
    {
        $prefix = 'data:image/png;base64,';
        $bytes = str_starts_with($data, $prefix) ? base64_decode(substr($data, strlen($prefix)), true) : false;
        $size = $bytes ? @getimagesizefromstring($bytes) : false;
        if (! $size || $size[2] !== IMAGETYPE_PNG || strlen($bytes) > 500000
            || $size[0] < 100 || $size[1] < 40 || $size[0] > 1600 || $size[1] > 600) {
            throw ValidationException::withMessages(['signature' => 'Bitte im Unterschriftsfeld unterschreiben.']);
        }
        // Decode and normalize the PNG; reject empty canvases, including opaque white ones.
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw ValidationException::withMessages(['signature' => 'Die Unterschrift konnte nicht gelesen werden.']);
        }
        $ink = 0;
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $c = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                if ($c['alpha'] < 100 && min($c['red'], $c['green'], $c['blue']) < 220) {
                    $ink++;
                }
            }
        }
        if ($ink < 30) {
            imagedestroy($image);
            throw ValidationException::withMessages(['signature' => 'Bitte eine sichtbare Unterschrift eingeben.']);
        }
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);
        $normalized = ob_get_clean();
        imagedestroy($image);

        return $prefix.base64_encode($normalized);
    }
}
