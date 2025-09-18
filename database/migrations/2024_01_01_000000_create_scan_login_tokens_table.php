<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scan_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'used', 'expired'])->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('token');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('scan_login_tokens');
    }
};