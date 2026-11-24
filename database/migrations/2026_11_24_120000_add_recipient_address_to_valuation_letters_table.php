<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('valuation_letters', function (Blueprint $table) {
            $table->string('recipient_street')->nullable()->after('recipient_name');
            $table->string('recipient_zip', 20)->nullable()->after('recipient_street');
            $table->string('recipient_city')->nullable()->after('recipient_zip');
        });
    }

    public function down(): void
    {
        Schema::table('valuation_letters', function (Blueprint $table) {
            $table->dropColumn(['recipient_street', 'recipient_zip', 'recipient_city']);
        });
    }
};
