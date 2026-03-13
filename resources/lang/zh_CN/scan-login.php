<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 扫码登录语言文件 - 简体中文
    |--------------------------------------------------------------------------
    */

    // 页面标题
    'qr_code_page_title' => '扫码登录',
    'qr_code_page_subtitle' => '使用手机扫描下方二维码登录',
    'mobile_confirm_title' => '扫码登录确认',
    'mobile_confirm_subtitle' => '请核对以下信息，确认是否在此设备上登录您的账户',

    // 操作按钮
    'confirm_login' => '确认登录',
    'cancel_login' => '取消',

    // 状态描述
    'status' => [
        'pending' => '等待扫描',
        'claimed' => '已扫描，等待确认',
        'consumed' => '登录成功',
        'cancelled' => '已取消',
        'expired' => '已过期',
    ],

    // 结果消息
    'results' => [
        'login_approved' => [
            'title' => '登录成功',
            'message' => '您已确认登录<br>请回到电脑端继续操作',
            'hint' => '此页面可以安全关闭',
        ],
        'login_cancelled' => [
            'title' => '已取消登录',
            'message' => '本次登录请求已取消<br>如需登录请回到电脑端重新扫码',
            'hint' => '此页面可以安全关闭',
        ],
        'token_consumed' => [
            'title' => '二维码已被使用',
            'message' => '这个登录二维码已经完成登录<br>不能再次扫码确认',
            'hint' => '请回到电脑端刷新后重新生成二维码',
        ],
        'token_cancelled' => [
            'title' => '二维码已取消',
            'message' => '这个登录请求已经取消<br>不能再次扫码确认',
            'hint' => '请回到电脑端重新生成二维码',
        ],
        'token_expired' => [
            'title' => '二维码已失效',
            'message' => '这个登录二维码已经过期<br>请勿继续确认登录',
            'hint' => '请回到电脑端刷新后重新生成二维码',
        ],
        'token_claimed' => [
            'title' => '二维码已被其他设备领取',
            'message' => '这个登录请求已经在另一台手机上打开<br>请确认是否为您本人操作',
            'hint' => '如不是本人操作，请回到电脑端刷新二维码',
        ],
        'rate_limit_exceeded' => [
            'title' => '操作过于频繁',
            'message' => '您的操作过于频繁<br>请稍后再试',
            'hint' => '为了您的账户安全，我们限制了操作频率',
        ],
    ],

    // 设备信息
    'device_info' => [
        'location' => '登录地点',
        'device' => '设备',
        'system' => '系统',
        'browser' => '浏览器',
        'unknown' => '未知',
        'unknown_device' => '未知设备',
        'unknown_system' => '未知系统',
        'unknown_browser' => '未知浏览器',
    ],

    // 安全提示
    'security_notice' => '安全提示：请仔细核对登录地点、设备、系统和浏览器信息，确认是本人操作后再同意登录。如有疑问请点击取消。',

    // 说明步骤
    'instructions' => [
        'step1' => '使用手机打开应用并登录',
        'step2' => '扫描上方二维码',
        'step3' => '在手机上确认登录',
    ],

    // 提示信息
    'hints' => [
        'qr_code_expires' => '二维码将在 :minutes 分钟后失效',
        'page_can_close' => '此页面可以安全关闭',
    ],

    // 错误消息
    'errors' => [
        'token_not_found' => '登录令牌不存在',
        'token_invalid' => '登录令牌无效',
        'token_unavailable' => '登录令牌不可用',
        'rate_limit' => '操作过于频繁，请稍后再试',
        'unauthorized' => '未授权访问',
    ],
];
