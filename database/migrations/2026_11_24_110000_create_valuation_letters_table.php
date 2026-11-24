<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('valuation_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('adviser_name');
            $table->string('adviser_role')->nullable();
            $table->string('adviser_mobile')->nullable();
            $table->string('adviser_email')->nullable();
            $table->date('issued_on');
            $table->string('property_label');
            $table->string('floor_location')->nullable();
            $table->string('room_summary')->nullable();
            $table->text('object_description');
            $table->text('market_description');
            $table->decimal('value_low', 12, 2);
            $table->decimal('value_high', 12, 2);
            $table->decimal('orientation_value', 12, 2);
            $table->text('valuation_notes');
            $table->date('valuation_date');
            $table->json('photo_paths')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuation_letters');
    }
};
