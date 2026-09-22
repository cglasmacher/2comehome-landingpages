<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_sync_logs', function (Blueprint $table): void {
            $table->string('onoffice_kdnr')->nullable()->after('external_contact_id');
            $table->string('onoffice_immonr')->nullable()->after('external_estate_id');
        });
    }

    public function down(): void
    {
        Schema::table('lead_sync_logs', function (Blueprint $table): void {
            $table->dropColumn(['onoffice_kdnr', 'onoffice_immonr']);
        });
    }
};
