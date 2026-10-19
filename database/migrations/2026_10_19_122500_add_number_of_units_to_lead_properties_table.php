<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_properties', function (Blueprint $table) {
            $table->unsignedSmallInteger('number_of_units')->nullable()->after('rooms');
        });
    }

    public function down(): void
    {
        Schema::table('lead_properties', function (Blueprint $table) {
            $table->dropColumn('number_of_units');
        });
    }
};
