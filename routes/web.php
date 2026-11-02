<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBrokerageContractController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLeadController;
use App\Http\Controllers\Admin\AdminSignatureController;
use App\Http\Controllers\ContractSigningController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LeadSubmissionController;
use App\Http\Controllers\ValuationReportController;
use App\Http\Controllers\ValuationResultController;
use Illuminate\Support\Facades\Route;

Route::domain(config('landingpages.public_domain'))->group(function () {
    Route::permanentRedirect('/', '/kostenlose-wertermittlung');

    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');

    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

        Route::get('/profile/signature', [AdminSignatureController::class, 'edit'])->name('signature.edit');
        Route::post('/profile/signature', [AdminSignatureController::class, 'update'])->name('signature.update');
        Route::get('/profile/signature/image', [AdminSignatureController::class, 'show'])->name('signature.show');
        Route::delete('/profile/signature', [AdminSignatureController::class, 'destroy'])->name('signature.destroy');

        Route::get('/leads/{lead}/contracts/create', [AdminBrokerageContractController::class, 'create'])->name('leads.contracts.create');
        Route::post('/leads/{lead}/contracts', [AdminBrokerageContractController::class, 'store'])->name('leads.contracts.store');
        Route::get('/contracts/{contract}/edit', [AdminBrokerageContractController::class, 'edit'])->name('contracts.edit');
        Route::put('/contracts/{contract}', [AdminBrokerageContractController::class, 'update'])->name('contracts.update');
        Route::post('/contracts/{contract}/finalize', [AdminBrokerageContractController::class, 'finalize'])->name('contracts.finalize');
        Route::get('/contracts/{contract}/pdf', [AdminBrokerageContractController::class, 'pdf'])->name('contracts.pdf');
        Route::post('/contracts/{contract}/duplicate', [AdminBrokerageContractController::class, 'duplicate'])->name('contracts.duplicate');
        Route::post('/contracts/{contract}/revoke', [AdminBrokerageContractController::class, 'revoke'])->name('contracts.revoke');

        Route::get('/leads/{lead}/edit', [AdminLeadController::class, 'edit'])->name('leads.edit');
        Route::put('/leads/{lead}/valuation', [AdminLeadController::class, 'update'])->name('leads.valuation.update');
        Route::get('/leads/{lead}/pdf', [AdminLeadController::class, 'preview'])->name('leads.pdf.preview');
        Route::get('/leads/{lead}/pdf/download', [AdminLeadController::class, 'download'])->name('leads.pdf.download');
        Route::get('/leads/{lead}/final-pdf', [AdminLeadController::class, 'finalPreview'])->name('leads.final-pdf.preview');
        Route::get('/leads/{lead}/final-pdf/download', [AdminLeadController::class, 'finalDownload'])->name('leads.final-pdf.download');
    });

    Route::middleware(['signed', 'throttle:30,1'])->prefix('maklerauftrag')->group(function () {
        Route::get('/{signer:uuid}', [ContractSigningController::class, 'show'])->name('contracts.show');
        Route::get('/{signer:uuid}/pdf', [ContractSigningController::class, 'pdf'])->name('contracts.pdf');
        Route::post('/{signer:uuid}', [ContractSigningController::class, 'sign'])->name('contracts.sign');
    });

    Route::get('/{landingPage:slug}', [LandingPageController::class, 'show'])->name('landing-pages.show');
    Route::post('/{landingPage:slug}/leads', [LeadSubmissionController::class, 'store'])->name('landing-pages.leads.store');
    Route::get('/{landingPage:slug}/results/{lead:uuid}', [ValuationResultController::class, 'show'])->name('landing-pages.results.show');
    Route::get('/reports/{lead:uuid}', [ValuationReportController::class, 'show'])->name('valuation-reports.show');
});
