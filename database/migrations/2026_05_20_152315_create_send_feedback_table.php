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
        Schema::create('send_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('send_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('url', 2048)->nullable(); // for clicks
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable(); // ipv6-safe
            $table->json('payload')->nullable(); // raw ESP webhook body for forensics
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['send_id', 'type']);
            $table->index(['type', 'happened_at']); // analytics rollups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_feedback');
    }
};
