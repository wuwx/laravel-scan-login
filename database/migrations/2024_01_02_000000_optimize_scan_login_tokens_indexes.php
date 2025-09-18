<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('scan_login_tokens', function (Blueprint $table) {
            // Add composite indexes for optimized queries
            $table->index(['status', 'expires_at', 'user_id'], 'idx_status_expires_user');
            $table->index(['expires_at', 'status'], 'idx_expires_status');
            $table->index(['user_id', 'status'], 'idx_user_status');
            
            // Add index for cleanup operations
            $table->index(['expires_at', 'id'], 'idx_cleanup_batch');
            
            // Add index for statistics queries
            $table->index(['created_at', 'status'], 'idx_created_status');
        });
    }

    public function down()
    {
        Schema::table('scan_login_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_status_expires_user');
            $table->dropIndex('idx_expires_status');
            $table->dropIndex('idx_user_status');
            $table->dropIndex('idx_cleanup_batch');
            $table->dropIndex('idx_created_status');
        });
    }
};