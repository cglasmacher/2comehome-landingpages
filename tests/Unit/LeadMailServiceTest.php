<?php

namespace Tests\Unit;

use App\Mail\LeadValuationSummaryMail;
use App\Mail\NewLandingPageLeadMail;
use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\Lead;
use App\Models\LeadMailDelivery;
use App\Services\Mail\LeadMailService;
use App\Services\Pdf\ValuationReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class LeadMailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('mail.default', 'smtp');
        config()->set('landingpages.lead_notification_email', 'c.glasmacher@2comehome.de');
    }

    private function lead(): Lead
    {
        $template = LandingPageTemplate::create(['name' => 'Test', 'key' => 'mail-test', 'schema' => [], 'default_content' => [], 'is_active' => true]);
        $page = LandingPage::create(['landing_page_template_id' => $template->id, 'slug' => 'haus-verkaufen', 'title' => 'Test', 'content' => [], 'published_at' => now()]);
        $lead = Lead::create(['landing_page_id' => $page->id, 'first_name' => 'Test', 'last_name' => 'Person', 'email' => 'prospect@example.test', 'phone' => '0123456']);
        $lead->property()->create(['country' => 'DE', 'property_type' => 'einfamilienhaus', 'city' => 'Hilden']);
        return $lead;
    }

    public function test_both_deliveries_are_durable_and_already_sent_mail_is_not_repeated(): void
    {
        Mail::fake();
        $lead = $this->lead();
        $service = app(LeadMailService::class);
        $service->prepare($lead);
        $service->prepare($lead);
        $this->assertSame(2, LeadMailDelivery::count());
        $service->sendForLead($lead);
        $service->sendForLead($lead);
        Mail::assertSent(LeadValuationSummaryMail::class, fn ($mail) => $mail->hasTo('prospect@example.test'));
        Mail::assertSent(NewLandingPageLeadMail::class, fn ($mail) => $mail->hasTo('c.glasmacher@2comehome.de'));
        Mail::assertSentCount(2);
        $this->assertSame(2, LeadMailDelivery::where('status', 'sent')->count());
    }

    public function test_log_transport_is_never_marked_as_sent(): void
    {
        Mail::fake();
        config()->set('mail.default', 'log');
        $lead = $this->lead();
        $service = app(LeadMailService::class);
        $service->prepare($lead);
        $service->sendForLead($lead);
        Mail::assertNothingSent();
        $this->assertSame(2, LeadMailDelivery::where('status', 'failed')->count());
        $this->assertStringContainsString('SMTP', LeadMailDelivery::first()->last_error);
    }

    public function test_failed_prospect_email_does_not_prevent_team_notification(): void
    {
        $pending = Mockery::mock();
        $pending->shouldReceive('send')->once()->andThrow(new RuntimeException('Simulated failure'));
        $team = Mockery::mock();
        $team->shouldReceive('send')->once()->with(Mockery::type(NewLandingPageLeadMail::class));
        Mail::shouldReceive('to')->with('prospect@example.test')->once()->andReturn($pending);
        Mail::shouldReceive('to')->with('c.glasmacher@2comehome.de')->once()->andReturn($team);
        $lead = $this->lead();
        $service = app(LeadMailService::class);
        $service->prepare($lead);
        $service->sendForLead($lead);
        $this->assertSame('failed', LeadMailDelivery::where('audience', 'prospect')->first()->status);
        $this->assertSame('sent', LeadMailDelivery::where('audience', 'team')->first()->status);
    }

    public function test_failure_is_retried_only_when_due_and_only_up_to_five_attempts(): void
    {
        Mail::fake();
        config()->set('mail.default', 'log');
        $lead = $this->lead();
        $service = app(LeadMailService::class);
        $service->prepare($lead);
        $service->sendForLead($lead);
        $service->sendForLead($lead);
        $this->assertSame(1, LeadMailDelivery::first()->attempts);
        LeadMailDelivery::query()->update(['attempts' => 5, 'next_attempt_at' => now()->subMinute()]);
        config()->set('mail.default', 'smtp');
        $service->sendForLead($lead);
        Mail::assertNothingSent();
        $this->artisan('leads:send-mail', ['--lead' => (string) $lead->id, '--retry' => true])->assertExitCode(0);
        Mail::assertSentCount(2);
    }

    public function test_failover_to_log_is_rejected(): void
    {
        config()->set('mail.default', 'failover');
        $this->expectException(RuntimeException::class);
        app(LeadMailService::class)->assertLiveMailer();
    }

    public function test_both_mailables_include_real_pdf_bytes_and_team_reply_to_prospect(): void
    {
        $lead = $this->lead();
        $pdf = app(ValuationReportPdfService::class)->bytes($lead);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $pdfService = Mockery::mock(ValuationReportPdfService::class);
        $pdfService->shouldReceive('bytes')->andReturn($pdf);
        $this->app->instance(ValuationReportPdfService::class, $pdfService);
        foreach ([new LeadValuationSummaryMail($lead), new NewLandingPageLeadMail($lead)] as $mail) {
            $mail->assertHasAttachedData($pdf, 'Immobilienbewertung-'.$lead->uuid.'.pdf', ['mime' => 'application/pdf']);
        }
        $team = new NewLandingPageLeadMail($lead);
        $this->assertSame('Landingpage Lead von haus-verkaufen', $team->envelope()->subject);
        $this->assertSame($lead->email, $team->envelope()->replyTo[0]->address);
        $this->assertStringContainsString('haus-verkaufen', $team->render());
    }
}
