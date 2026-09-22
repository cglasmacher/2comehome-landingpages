<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim((string) $request->query('search'));

        $leads = Lead::query()
            ->with(['landingPage:id,slug,title', 'property', 'valuation', 'latestOnOfficeSync'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('property', function ($query) use ($search): void {
                            $query->where('street', 'like', '%'.$search.'%')
                                ->orWhere('city', 'like', '%'.$search.'%')
                                ->orWhere('zip', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('syncLogs', function ($query) use ($search): void {
                            $query->where('provider', 'onoffice')
                                ->where(function ($query) use ($search): void {
                                    $query->where('onoffice_kdnr', 'like', '%'.$search.'%')
                                        ->orWhere('onoffice_immonr', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $leads->through(fn (Lead $lead) => [
            'id' => $lead->id,
            'uuid' => $lead->uuid,
            'name' => trim($lead->first_name.' '.$lead->last_name) ?: 'Ohne Namen',
            'email' => $lead->email,
            'phone' => $lead->phone,
            'created_at' => $lead->created_at?->format('d.m.Y H:i'),
            'landing_page' => $lead->landingPage?->title,
            'landing_page_slug' => $lead->landingPage?->slug,
            'property' => [
                'type' => $lead->property?->property_type_label,
                'address' => trim(implode(' ', array_filter([
                    $lead->property?->street,
                    $lead->property?->house_number,
                ]))),
                'location' => trim(implode(' ', array_filter([
                    $lead->property?->zip,
                    $lead->property?->city,
                ]))),
            ],
            'onoffice' => [
                'kdnr' => $lead->latestOnOfficeSync?->onoffice_kdnr,
                'immonr' => $lead->latestOnOfficeSync?->onoffice_immonr,
            ],
            'valuation' => [
                'estimated_value' => $lead->valuation?->estimated_value,
                'range_low' => $lead->valuation?->range_low,
                'range_high' => $lead->valuation?->range_high,
                'source_label' => $lead->valuation?->source_label,
                'status' => $lead->valuation?->status,
            ],
            'edit_url' => route('admin.leads.edit', $lead),
        ]);

        return Inertia::render('Admin/Dashboard', [
            'leads' => $leads,
            'filters' => ['search' => $search],
        ]);
    }
}
