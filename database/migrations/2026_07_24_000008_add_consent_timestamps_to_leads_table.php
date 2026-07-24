<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('phone_contact_consent_at')->nullable()->after('consultation_requested_at');
            $table->timestamp('valuation_disclaimer_accepted_at')->nullable()->after('phone_contact_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'phone_contact_consent_at',
                'valuation_disclaimer_accepted_at',
            ]);
        });
    }
};
