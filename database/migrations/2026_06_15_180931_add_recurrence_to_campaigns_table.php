<?php

use App\Enums\CampaignFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->string('frequency')->default(CampaignFrequency::ONCE->value)->after('status');
            $table->timestamp('next_run_at')->nullable()->after('scheduled_at');
            $table->timestamp('last_sent_at')->nullable()->after('sent_at');

            $table->index('next_run_at'); // for the dispatch scheduler
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropIndex(['next_run_at']);
            $table->dropColumn(['frequency', 'next_run_at', 'last_sent_at']);
        });
    }
};
