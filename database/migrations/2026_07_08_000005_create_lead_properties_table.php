<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('property_type')->nullable();
            $table->string('street')->nullable();
            $table->string('house_number')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('DE');
            $table->unsignedSmallInteger('construction_year')->nullable();
            $table->unsignedInteger('living_area')->nullable();
            $table->unsignedInteger('plot_area')->nullable();
            $table->unsignedSmallInteger('rooms')->nullable();
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_properties');
    }
};
