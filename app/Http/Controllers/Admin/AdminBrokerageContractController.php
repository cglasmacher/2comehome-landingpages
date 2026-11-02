<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBrokerageContractRequest;
use App\Models\BrokerageContract;
use App\Models\Lead;
use App\Models\User;
use App\Services\Contracts\BrokerageContractService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class AdminBrokerageContractController extends Controller
{
    public function create(Request $request, Lead $lead)
    {
        $lead->load('property');
        $start = CarbonImmutable::today('Europe/Berlin');

        return $this->form($lead, null, [
            'agent_id' => $request->user()->id,
            // The lead's property address is not assumed to be the seller's home address.
            'sellers' => [['name' => trim($lead->first_name.' '.$lead->last_name), 'address' => '', 'email' => $lead->email ?? '']],
            'property_address' => trim(implode(' ', array_filter([$lead->property?->street, $lead->property?->house_number])).', '.implode(' ', array_filter([$lead->property?->zip, $lead->property?->city])), ', '),
            'property_description' => $lead->property?->property_type_label ?? '',
            'offer_price' => '', 'commission_percent' => config('brokerage.commission_percent'),
            'starts_on' => $start->toDateString(),
            'duration_months' => config('brokerage.duration_months'),
            'ends_on' => $start->addMonthsNoOverflow(config('brokerage.duration_months'))->subDay()->toDateString(),
            'notice_months' => config('brokerage.notice_months'), 'renewal_months' => config('brokerage.renewal_months'),
            'signing_place' => 'Hilden', 'special_agreements' => '',
        ]);
    }

    public function store(SaveBrokerageContractRequest $request, Lead $lead)
    {
        $contract = $lead->brokerageContracts()->create($request->validated());

        return redirect()->route('admin.contracts.edit', $contract)->with('success', 'Vertragsentwurf gespeichert.');
    }

    public function edit(BrokerageContract $contract)
    {
        $contract->load(['lead', 'signers']);
        $fields = collect($contract->getAttributes())->only([
            'agent_id', 'sellers', 'property_address', 'property_description', 'offer_price', 'commission_percent',
            'duration_months', 'notice_months', 'renewal_months', 'signing_place', 'special_agreements',
        ])->all();
        $fields['sellers'] = $contract->sellers;
        $fields['starts_on'] = $contract->starts_on->toDateString();
        $fields['ends_on'] = $contract->ends_on->toDateString();

        return $this->form($contract->lead, $contract, $fields);
    }

    private function form(Lead $lead, ?BrokerageContract $contract, array $fields)
    {
        return Inertia::render('Admin/ContractEdit', [
            'lead' => $lead->only('id', 'first_name', 'last_name'),
            'contract' => $contract ? [
                'id' => $contract->id, 'status' => $contract->status,
                'finalized_at' => $contract->finalized_at?->timezone('Europe/Berlin')->format('d.m.Y H:i'),
                'links_revoked' => (bool) $contract->links_revoked_at,
                'signing_expires_at' => $contract->signing_expires_at?->timezone('Europe/Berlin')->format('d.m.Y H:i'),
                'signers' => $contract->signers->map(fn ($signer) => [
                    'id' => $signer->id, 'name' => $signer->name, 'email' => $signer->email,
                    'signed_at' => $signer->signed_at?->timezone('Europe/Berlin')->format('d.m.Y H:i'),
                    'url' => ! $contract->links_revoked_at && $contract->signing_expires_at?->isFuture()
                        ? URL::temporarySignedRoute('contracts.show', $contract->signing_expires_at, ['signer' => $signer->uuid]) : null,
                ]),
            ] : null,
            'fields' => $fields,
            'broker' => config('brokerage.broker_name'),
            'agents' => User::query()->where('is_admin', true)->orderBy('name')->get(['id', 'name', 'signature_path'])
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name,
                    'has_signature' => $user->signature_path && Storage::disk('local')->exists($user->signature_path)]),
        ]);
    }

    public function update(SaveBrokerageContractRequest $request, BrokerageContract $contract)
    {
        DB::transaction(function () use ($request, $contract) {
            $locked = BrokerageContract::query()->lockForUpdate()->findOrFail($contract->id);
            abort_unless($locked->status === 'draft', 409, 'Fertige Verträge können nicht geändert werden. Bitte eine neue Fassung anlegen.');
            $locked->update($request->validated());
        });

        return back()->with('success', 'Vertragsentwurf gespeichert.');
    }

    public function finalize(BrokerageContract $contract, BrokerageContractService $service)
    {
        $service->finalize($contract);

        return back()->with('success', 'Maklerauftrag fertiggestellt. Die persönlichen Unterschriftslinks stehen bereit.');
    }

    public function pdf(Request $request, BrokerageContract $contract, BrokerageContractService $service)
    {
        if ($contract->status === 'draft') {
            return response($service->bytes($contract), 200, [
                'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Maklerauftrag-Entwurf.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        }
        $path = $request->boolean('original') ? $contract->pdf_path : ($contract->signed_pdf_path ?? $contract->pdf_path);
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $filename = 'Maklerauftrag-'.$contract->id.'.pdf';

        return $request->boolean('download')
            ? Storage::disk('local')->download($path, $filename, ['Cache-Control' => 'private, no-store'])
            : Storage::disk('local')->response($path, $filename, ['Cache-Control' => 'private, no-store']);
    }

    public function duplicate(BrokerageContract $contract)
    {
        $copy = $contract->replicate([
            'uuid', 'snapshot', 'pdf_path', 'pdf_sha256', 'signed_pdf_path', 'signed_pdf_sha256',
            'finalized_at', 'signing_expires_at', 'links_revoked_at',
        ]);
        $copy->status = 'draft';
        $copy->save();

        return redirect()->route('admin.contracts.edit', $copy)->with('success', 'Neue Fassung als Entwurf angelegt.');
    }

    public function revoke(BrokerageContract $contract)
    {
        DB::transaction(function () use ($contract) {
            $locked = BrokerageContract::query()->lockForUpdate()->findOrFail($contract->id);
            abort_if($locked->status === 'draft', 409);
            $locked->update(['links_revoked_at' => now()]);
        });

        return back()->with('success', 'Alle Unterschriftslinks dieses Auftrags wurden gesperrt.');
    }
}
