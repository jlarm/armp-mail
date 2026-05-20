<?php

use App\Enums\Status;
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
        Schema::create('email_list_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(Status::UNCONFIRMED->value);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('subscribe_source')->nullable();
            $table->timestamps();

            $table->unique(['email_list_id', 'subscriber_id']);
            $table->index(['email_list_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_list_subscribers');
    }
};
