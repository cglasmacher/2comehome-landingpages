<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LeadSubmissionController;
use App\Http\Controllers\ValuationReportController;
use Illuminate\Support\Facades\Route;

Route::domain(config('landingpages.public_domain'))->group(function () {
    Route::get('/{landingPage:slug}', [LandingPageController::class, 'show'])->name('landing-pages.show');
    Route::post('/{landingPage:slug}/leads', [LeadSubmissionController::class, 'store'])->name('landing-pages.leads.store');
    Route::get('/reports/{lead:uuid}', [ValuationReportController::class, 'show'])->name('valuation-reports.show');
});
