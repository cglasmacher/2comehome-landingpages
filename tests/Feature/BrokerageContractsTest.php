<?php

namespace Tests\Feature;

use App\Models\BrokerageContract;
use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\Lead;
use App\Models\User;
use App\Services\Contracts\BrokerageContractService;
use App\Services\Maps\GoogleStaticMapService;
use App\Services\Pdf\ValuationReportPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrokerageContractsTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Storage::fake('local');
        $this->agent = User::factory()->create(['is_admin' => true]);
        $template = LandingPageTemplate::create(['name' => 'Test', 'key' => 'contract-test', 'schema' => [], 'default_content' => [], 'is_active' => true]);
        $page = LandingPage::create(['landing_page_template_id' => $template->id, 'slug' => 'contract-test', 'title' => 'Test', 'content' => []]);
        $this->lead = Lead::create(['landing_page_id' => $page->id, 'first_name' => 'Erika', 'last_name' => 'Muster', 'email' => 'erika@example.test']);
        $this->lead->property()->create(['street' => 'Objektstraße', 'house_number' => '1', 'zip' => '40721', 'city' => 'Hilden', 'property_type' => 'wohnung']);
    }

    private function data(array $overrides = []): array
    {
        return array_replace([
            'agent_id' => $this->agent->id,
            'sellers' => [['name' => 'Erika Muster', 'address' => "Privatstraße 2\n40721 Hilden", 'email' => 'erika@example.test']],
            'property_address' => 'Objektstraße 1, 40721 Hilden', 'property_description' => 'Wohnung',
            'offer_price' => '795000.00', 'commission_percent' => '3.57',
            'starts_on' => '2026-11-02', 'duration_months' => 6, 'ends_on' => '2027-05-01',
            'notice_months' => 1, 'renewal_months' => 1, 'signing_place' => 'Hilden', 'special_agreements' => '',
        ], $overrides);
    }

    private function contract(array $overrides = []): BrokerageContract
    {
        return $this->lead->brokerageContracts()->create($this->data($overrides));
    }

    private function signature(bool $blank = false): string
    {
        $image = imagecreatetruecolor(600, 180);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        if (! $blank) {
            imagesetthickness($image, 3);
            imageline($image, 40, 140, 200, 40, imagecolorallocate($image, 20, 30, 50));
            imageline($image, 200, 40, 450, 130, imagecolorallocate($image, 20, 30, 50));
        }
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function withAgentSignature(): void
    {
        Storage::disk('local')->put('agent-signatures/test.png', base64_decode(explode(',', $this->signature())[1]));
        $this->agent->forceFill(['signature_path' => 'agent-signatures/test.png'])->save();
    }

    private function finalized(array $overrides = []): BrokerageContract
    {
        $this->withAgentSignature();
        $contract = $this->contract($overrides);
        app(BrokerageContractService::class)->finalize($contract);

        return $contract->fresh();
    }

    private function signingUrl($signer, string $route = 'contracts.sign'): string
    {
        return URL::temporarySignedRoute($route, $signer->contract->signing_expires_at, ['signer' => $signer->uuid]);
    }

    private function acceptance(array $overrides = []): array
    {
        return array_replace([
            'signature' => $this->signature(), 'signing_place' => 'Hilden', 'accepted' => true,
            'early_start_requested' => false, 'withdrawal_loss_acknowledged' => false,
        ], $overrides);
    }

    public function test_defaults_and_lead_prefill_do_not_assume_seller_address(): void
    {
        $this->actingAs($this->agent)->get(route('admin.leads.contracts.create', $this->lead))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/ContractEdit')
            ->where('broker', '2 COME HOME Immobilien - Maximilian Schäfer')
            ->where('fields.commission_percent', '3.57')->where('fields.duration_months', 6)
            ->where('fields.notice_months', 1)->where('fields.sellers.0.address', '')
            ->where('fields.property_address', 'Objektstraße 1, 40721 Hilden'));
    }

    public function test_only_admins_can_manage_contracts_and_signatures(): void
    {
        $this->get(route('admin.leads.contracts.create', $this->lead))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.leads.contracts.create', $this->lead))->assertForbidden();
        $this->get(route('admin.signature.edit'))->assertForbidden();
    }

    public function test_individual_terms_are_saved_without_changing_defaults_or_broker(): void
    {
        $data = $this->data(['commission_percent' => '2,38', 'duration_months' => 12, 'ends_on' => '2027-11-01', 'notice_months' => 2, 'renewal_months' => 0]);
        $data['broker_name'] = 'Someone else';
        $this->actingAs($this->agent)->post(route('admin.leads.contracts.store', $this->lead), $data)->assertSessionHasNoErrors()->assertRedirect();
        $contract = BrokerageContract::firstOrFail();
        $this->assertSame('2.38', $contract->commission_percent);
        $this->assertSame(2, $contract->notice_months);
        $this->assertSame(0, $contract->renewal_months);
        $this->assertSame('3.57', config('brokerage.commission_percent'));
        $this->assertSame('2 COME HOME Immobilien - Maximilian Schäfer', app(BrokerageContractService::class)->snapshot($contract)['broker_name']);
    }

    public function test_invalid_terms_and_non_admin_agent_are_rejected(): void
    {
        $data = $this->data(['offer_price' => '-1', 'commission_percent' => 101, 'ends_on' => '2026-01-01', 'agent_id' => User::factory()->create(['is_admin' => false])->id]);
        $this->actingAs($this->agent)->post(route('admin.leads.contracts.store', $this->lead), $data)
            ->assertSessionHasErrors(['offer_price', 'commission_percent', 'ends_on', 'agent_id']);
        $this->assertSame(0, BrokerageContract::count());
    }

    public function test_signature_upload_is_private_and_scoped_to_current_user(): void
    {
        $other = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->agent)->post(route('admin.signature.update'), ['signature' => UploadedFile::fake()->image('signature.png', 300, 100)])
            ->assertSessionHasNoErrors();
        $path = $this->agent->fresh()->signature_path;
        Storage::disk('local')->assertExists($path);
        $this->get(route('admin.signature.show'))->assertOk();
        $this->actingAs($other)->get(route('admin.signature.show'))->assertNotFound();
        $this->actingAs($this->agent)->delete(route('admin.signature.destroy'))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
        $this->assertNull($this->agent->fresh()->signature_path);
    }

    public function test_signature_rejects_svg_and_oversized_dimensions(): void
    {
        $this->actingAs($this->agent)->post(route('admin.signature.update'), ['signature' => UploadedFile::fake()->createWithContent('evil.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')])->assertSessionHasErrors('signature');
        $this->post(route('admin.signature.update'), ['signature' => UploadedFile::fake()->image('large.png', 2500, 30)])->assertSessionHasErrors('signature');
    }

    public function test_finalize_requires_offer_price_and_agent_signature(): void
    {
        $contract = $this->contract(['offer_price' => null]);
        $this->actingAs($this->agent)->post(route('admin.contracts.finalize', $contract))->assertSessionHasErrors('agent_id');
        $this->withAgentSignature();
        $this->post(route('admin.contracts.finalize', $contract))->assertSessionHasErrors('offer_price');
        $this->assertSame('draft', $contract->fresh()->status);
        $this->assertSame(0, $contract->signers()->count());
    }

    public function test_finalize_freezes_pdf_signature_broker_and_terms(): void
    {
        $contract = $this->finalized();
        $original = Storage::disk('local')->get($contract->pdf_path);
        $snapshot = $contract->snapshot;
        $this->assertStringStartsWith('%PDF-', $original);
        $this->assertSame(hash('sha256', $original), $contract->pdf_sha256);
        $this->assertSame(1, $contract->signers()->count());
        $this->actingAs($this->agent)->delete(route('admin.signature.destroy'));
        $this->lead->update(['first_name' => 'Changed']);
        config(['brokerage.commission_percent' => '9.00']);
        $this->assertSame($snapshot, $contract->fresh()->snapshot);
        $response = $this->get(route('admin.contracts.pdf', $contract))->assertOk();
        $this->assertSame(hash('sha256', $original), hash('sha256', $response->streamedContent()));
        $this->put(route('admin.contracts.update', $contract), $this->data(['offer_price' => 1]))->assertStatus(409);
        $this->post(route('admin.contracts.finalize', $contract))->assertStatus(409);
    }

    public function test_public_link_rejects_missing_tampered_expired_and_revoked_signatures(): void
    {
        $contract = $this->finalized();
        $signer = $contract->signers()->first();
        $url = $this->signingUrl($signer, 'contracts.show');
        $this->get(route('contracts.show', $signer->uuid))->assertForbidden();
        $this->get($url.'tampered')->assertForbidden();
        $this->get($url)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertInertia(fn (Assert $page) => $page->component('Contracts/Sign')->where('seller', 'Erika Muster'));
        $expired = URL::temporarySignedRoute('contracts.show', now()->subMinute(), ['signer' => $signer->uuid]);
        $this->get($expired)->assertForbidden();
        $this->actingAs($this->agent)->post(route('admin.contracts.revoke', $contract))->assertRedirect();
        $this->get($url)->assertStatus(410);
        $this->post($this->signingUrl($signer), $this->acceptance())->assertStatus(410);
    }

    public function test_multiple_sellers_sign_once_and_original_pdf_is_preserved(): void
    {
        $contract = $this->finalized(['sellers' => [
            ['name' => 'Erika Muster', 'address' => 'Privatstraße 2'],
            ['name' => 'Max Muster', 'address' => 'Privatstraße 2'],
        ]]);
        [$first, $second] = $contract->signers()->get()->all();
        $original = Storage::disk('local')->get($contract->pdf_path);
        $this->post($this->signingUrl($first), $this->acceptance())->assertSessionHasNoErrors()->assertRedirect();
        $contract->refresh();
        $this->assertSame('partially_signed', $contract->status);
        $firstSignedPath = $contract->signed_pdf_path;
        $this->assertNotNull($first->fresh()->signed_at);
        $this->assertSame($contract->pdf_sha256, $first->fresh()->document_sha256);
        $this->post($this->signingUrl($first), $this->acceptance())->assertStatus(409);
        $this->post($this->signingUrl($second), $this->acceptance(['early_start_requested' => true, 'withdrawal_loss_acknowledged' => true]))->assertSessionHasNoErrors()->assertRedirect();
        $contract->refresh();
        $this->assertSame('signed', $contract->status);
        $this->assertNotSame($firstSignedPath, $contract->signed_pdf_path);
        $this->assertSame($original, Storage::disk('local')->get($contract->pdf_path));
        Storage::disk('local')->assertExists($firstSignedPath);
        $this->assertTrue($second->fresh()->early_start_requested);
        $this->get($this->signingUrl($first, 'contracts.pdf'))->assertOk();
    }

    public function test_signing_requires_consent_visible_signature_and_early_start_acknowledgement(): void
    {
        $contract = $this->finalized();
        $signer = $contract->signers()->first();
        $url = $this->signingUrl($signer);
        $this->post($url, $this->acceptance(['accepted' => false]))->assertSessionHasErrors('accepted');
        $this->post($url, $this->acceptance(['early_start_requested' => true]))->assertSessionHasErrors('withdrawal_loss_acknowledged');
        $this->post($url, $this->acceptance(['signature' => $this->signature(true)]))->assertSessionHasErrors('signature');
        $this->post($url, $this->acceptance(['signature' => 'data:image/svg+xml;base64,PHN2Zz4=']))->assertSessionHasErrors('signature');
        $this->assertNull($signer->fresh()->signed_at);
    }

    public function test_new_version_is_a_draft_with_new_uuid_and_without_signatures(): void
    {
        $contract = $this->finalized();
        $this->actingAs($this->agent)->post(route('admin.contracts.duplicate', $contract))->assertRedirect();
        $copy = BrokerageContract::latest('id')->first();
        $this->assertNotSame($contract->uuid, $copy->uuid);
        $this->assertSame('draft', $copy->status);
        $this->assertNull($copy->snapshot);
        $this->assertNull($copy->pdf_path);
        $this->assertSame(0, $copy->signers()->count());
        $this->assertSame($contract->offer_price, $copy->offer_price);
    }

    public function test_pdf_failure_rolls_back_finalization_and_signer_creation(): void
    {
        $this->withAgentSignature();
        $contract = $this->contract();
        Pdf::shouldReceive('loadView')->once()->andThrow(new \RuntimeException('Renderer unavailable'));
        try {
            app(BrokerageContractService::class)->finalize($contract);
            $this->fail('Expected PDF renderer failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Renderer unavailable', $exception->getMessage());
        }
        $this->assertSame('draft', $contract->fresh()->status);
        $this->assertNull($contract->fresh()->snapshot);
        $this->assertSame(0, $contract->signers()->count());
    }

    public function test_shared_valuation_pdf_does_not_expose_other_sellers_signing_links(): void
    {
        $this->finalized(['sellers' => [
            ['name' => 'Erika Muster', 'address' => 'Privatstraße 2'],
            ['name' => 'Max Muster', 'address' => 'Privatstraße 2'],
        ]]);
        $maps = $this->createMock(GoogleStaticMapService::class);
        $maps->method('forProperty')->willReturn(null);
        $pdf = (new ValuationReportPdfService($maps))->bytes($this->lead, ValuationReportPdfService::TYPE_FINAL);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringNotContainsString('/maklerauftrag/', $pdf);
    }

    public function test_saved_draft_preview_and_final_report_cta_render_real_pdf(): void
    {
        $draft = $this->contract();
        $this->actingAs($this->agent)->get(route('admin.contracts.pdf', $draft))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $contract = $this->finalized();
        $this->lead->valuation()->create(['provider' => 'manual', 'estimated_value' => 795000, 'range_low' => 780000, 'range_high' => 800000, 'range_percent' => 0, 'status' => 'success']);
        $maps = $this->createMock(GoogleStaticMapService::class);
        $maps->method('forProperty')->willReturn(null);
        $service = new ValuationReportPdfService($maps);
        $pdf = $service->bytes($this->lead, ValuationReportPdfService::TYPE_FINAL);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('maklerauftrag', $pdf);
        // Save QA artifacts outside the repository only when requested by the local run.
        if ($dir = getenv('BROKERAGE_QA_DIR')) {
            @mkdir($dir, 0777, true);
            file_put_contents($dir.'/contract.pdf', Storage::disk('local')->get($contract->pdf_path));
            file_put_contents($dir.'/final-valuation.pdf', $pdf);
        }
    }
}
