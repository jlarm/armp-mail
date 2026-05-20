<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppressions', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamp('suppressed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppressions');
    }
};
