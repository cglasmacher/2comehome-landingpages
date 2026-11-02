<?php

namespace App\Http\Controllers;

use App\Models\BrokerageContractSigner;
use App\Services\Contracts\BrokerageContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class ContractSigningController extends Controller
{
    private function check(BrokerageContractSigner $signer): void
    {
        $signer->load('contract');
        abort_if($signer->contract->links_revoked_at || ! $signer->contract->signing_expires_at?->isFuture(), 410, 'Dieser Link ist nicht mehr gültig.');
    }

    public function show(BrokerageContractSigner $signer)
    {
        $this->check($signer);
        $contract = $signer->contract;
        $params = ['signer' => $signer->uuid];

        return Inertia::render('Contracts/Sign', [
            'seller' => $signer->name,
            'signedAt' => $signer->signed_at?->timezone('Europe/Berlin')->format('d.m.Y H:i'),
            'complete' => $contract->status === 'signed',
            'broker' => $contract->snapshot['broker_name'],
            'propertyAddress' => $contract->snapshot['property_address'],
            'pdfUrl' => URL::temporarySignedRoute('contracts.pdf', $contract->signing_expires_at, $params),
            'submitUrl' => URL::temporarySignedRoute('contracts.sign', $contract->signing_expires_at, $params),
        ])->toResponse(request())->withHeaders(['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow']);
    }

    public function pdf(BrokerageContractSigner $signer)
    {
        $this->check($signer);
        $path = $signer->contract->signed_pdf_path ?? $signer->contract->pdf_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'Maklerauftrag.pdf', [
            'Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function sign(Request $request, BrokerageContractSigner $signer, BrokerageContractService $service)
    {
        $this->check($signer);
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:700000'],
            'signing_place' => ['required', 'string', 'max:255'],
            'accepted' => ['accepted'],
            'early_start_requested' => ['required', 'boolean'],
            'withdrawal_loss_acknowledged' => ['required', 'boolean', 'accepted_if:early_start_requested,1,true'],
        ]);
        $service->sign($signer, $data);

        return redirect(URL::temporarySignedRoute('contracts.show', $signer->contract->signing_expires_at, ['signer' => $signer->uuid]));
    }
}
