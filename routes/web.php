<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLeadController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LeadSubmissionController;
use App\Http\Controllers\ValuationReportController;
use App\Http\Controllers\ValuationResultController;
use Illuminate\Support\Facades\Route;

Route::domain(config('landingpages.public_domain'))->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');

    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

        Route::get('/leads/{lead}/edit', [AdminLeadController::class, 'edit'])->name('leads.edit');
        Route::put('/leads/{lead}/valuation', [AdminLeadController::class, 'update'])->name('leads.valuation.update');
        Route::get('/leads/{lead}/pdf', [AdminLeadController::class, 'preview'])->name('leads.pdf.preview');
        Route::get('/leads/{lead}/pdf/download', [AdminLeadController::class, 'download'])->name('leads.pdf.download');
    });

    Route::get('/{landingPage:slug}', [LandingPageController::class, 'show'])->name('landing-pages.show');
    Route::post('/{landingPage:slug}/leads', [LeadSubmissionController::class, 'store'])->name('landing-pages.leads.store');
    Route::get('/{landingPage:slug}/results/{lead:uuid}', [ValuationResultController::class, 'show'])->name('landing-pages.results.show');
    Route::get('/reports/{lead:uuid}', [ValuationReportController::class, 'show'])->name('valuation-reports.show');
});
