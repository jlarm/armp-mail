<?php

use App\Enums\AutomationStatus;
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
        Schema::create('automations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_list_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default(AutomationStatus::PAUSED->value);
            $table->json('triggers')->nullable();
            $table->timestamp('last_ran_at')->nullable();
            $table->timestamps();

            $table->index(['email_list_id', 'status']);
        });

        Schema::create('automation_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('order');
            $table->foreignId('parent_step_id')->nullable()->constrained('automation_steps')->nullOnDelete();
            $table->json('config');
            $table->timestamps();

            $table->index(['automation_id', 'order']);
        });

        Schema::create('automation_action_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->timestamp('run_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('halted_at')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'subscriber_id']);
            $table->index(['run_at', 'completed_at']); // the worker query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_action_subscribers');
        Schema::dropIfExists('automation_steps');
        Schema::dropIfExists('automations');
    }
};
