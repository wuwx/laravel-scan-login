<?php

namespace Wuwx\LaravelScanLogin\Listeners;

use Illuminate\Support\Facades\Notification;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;

class SendScanLoginNotification
{
    /**
     * Handle the event.
     */
    public function handle(ScanLoginTokenConsumed $event): void
    {
        // 获取用户
        $user = \App\Models\User::find($event->consumerId);

        if (!$user) {
            return;
        }

        // 这里可以发送通知
        // 例如：邮件通知、短信通知、站内通知等
        
        // 示例：记录登录通知（需要创建对应的 Notification 类）
        // Notification::send($user, new ScanLoginSuccessNotification($event->token));
        
        // 或者使用简单的邮件通知
        // Mail::to($user->email)->send(new ScanLoginSuccessMail($event->token));
    }
}
