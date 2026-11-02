<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable();
        });

        Schema::create('brokerage_contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->json('sellers');
            $table->text('property_address');
            $table->string('property_description')->nullable();
            $table->decimal('offer_price', 14, 2)->nullable();
            $table->decimal('commission_percent', 5, 2);
            $table->date('starts_on');
            $table->unsignedSmallInteger('duration_months');
            $table->date('ends_on');
            $table->unsignedSmallInteger('notice_months');
            $table->unsignedSmallInteger('renewal_months');
            $table->string('signing_place');
            $table->text('special_agreements')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_sha256', 64)->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('signed_pdf_sha256', 64)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('signing_expires_at')->nullable();
            $table->timestamp('links_revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('brokerage_contract_signers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('brokerage_contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name');
            $table->string('email')->nullable();
            $table->text('signature_data')->nullable();
            $table->string('signing_place')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->boolean('early_start_requested')->default(false);
            $table->boolean('withdrawal_loss_acknowledged')->default(false);
            $table->string('document_sha256', 64)->nullable();
            $table->timestamps();
            $table->unique(['brokerage_contract_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brokerage_contract_signers');
        Schema::dropIfExists('brokerage_contracts');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('signature_path'));
    }
};
