<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // User management fields
            $table->string('status')->default('active')->after('remember_token');
            $table->string('role')->default('user')->after('status');
            $table->string('locale')->default('en')->after('role');
            $table->string('timezone')->nullable()->after('locale');
            $table->string('phone')->nullable()->after('timezone');
            $table->timestamp('last_login_at')->nullable()->after('phone');
            
            // Telegram fields
            $table->string('telegram_chat_id')->nullable()->unique()->after('last_login_at');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->timestamp('telegram_verified_at')->nullable()->after('telegram_username');
            $table->string('telegram_verification_code')->nullable()->after('telegram_verified_at');
            
            // Indexes
            $table->index('status');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['role']);
            $table->dropUnique(['telegram_chat_id']);
            $table->dropColumn([
                'status',
                'role',
                'locale',
                'timezone',
                'phone',
                'last_login_at',
                'telegram_chat_id',
                'telegram_username',
                'telegram_verified_at',
                'telegram_verification_code',
            ]);
        });
    }
};
