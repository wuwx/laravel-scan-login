<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('scan_login_tokens', function (Blueprint $table) {
            // 生成二维码时的设备信息
            $table->string('ip_address', 45)->nullable()->after('user_id')->comment('生成二维码时的IP地址');
            $table->text('user_agent')->nullable()->after('ip_address')->comment('生成二维码时的User Agent');
            
            // 添加索引以提高查询性能
            $table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::table('scan_login_tokens', function (Blueprint $table) {
            // 删除索引
            $table->dropIndex(['ip_address']);
            
            // 删除字段
            $table->dropColumn([
                'ip_address',
                'user_agent',
            ]);
        });
    }
};
