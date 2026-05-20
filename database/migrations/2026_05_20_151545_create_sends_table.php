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
        Schema::create('sends', function (Blueprint $table): void {
            $table->id();
            $table->ulid('uuid')->unique(); // signed URL token, time-ordered for index locality
            $table->morphs('sendable'); // Campaign | AutomationMail | TransactionalMail
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('transport_message_id')->nullable(); // SES/Postmark message id
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            // Denormalized first-event timestamps for fast "has X happened" queries
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamps();

            $table->unique(['sendable_type', 'sendable_id', 'subscriber_id'], 'sends_unique_sendable_subscriber');
            $table->index('transport_message_id'); // webhook lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sends');
    }
};
