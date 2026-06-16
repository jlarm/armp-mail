<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sends', function (Blueprint $table): void {
            $table->timestamp('unsubscribed_at')->nullable()->after('complained_at');
        });
    }

    public function down(): void
    {
        Schema::table('sends', function (Blueprint $table): void {
            $table->dropColumn('unsubscribed_at');
        });
    }
};
