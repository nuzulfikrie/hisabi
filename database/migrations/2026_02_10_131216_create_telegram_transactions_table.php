<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telegram_chat_id');
            $table->string('telegram_message_id');
            $table->text('raw_message');
            $table->string('status')->default('pending'); // pending, processed, failed, ignored
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->json('parsed_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_chat_id', 'status'], 'tg_chat_status_idx');
            $table->unique(['telegram_chat_id', 'telegram_message_id'], 'tg_unique_message');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_transactions');
    }
};
