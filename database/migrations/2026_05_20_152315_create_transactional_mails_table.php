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
        Schema::create('transactional_mails', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable(); // template key, e.g. 'audit-form-submitted'
            $table->string('subject');
            $table->longText('html');
            $table->longText('structured_html')->nullable();
            $table->boolean('store_mail')->default(true);
            $table->boolean('track_opens')->default(false); // usually off for transactional
            $table->boolean('track_clicks')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactional_mails');
    }
};
