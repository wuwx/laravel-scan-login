# Laravel Scan Login

一个基于 QR 码的 Laravel 扫码登录包，允许用户通过手机扫描二维码快速登录桌面应用。

## 功能特性

- 🔐 **安全的扫码登录**：使用状态机管理登录流程
- 📱 **移动端确认**：手机扫码后在移动端确认登录
- ⚡ **实时状态同步**：实时更新登录状态
- 🎨 **现代化 UI**：响应式界面设计
- 🛡️ **安全防护**：Token 自动过期，防止重放攻击

## 安装

### 1. 安装包

```bash
composer require wuwx/laravel-scan-login
```

### 2. 发布配置文件

```bash
php artisan vendor:publish --provider="Wuwx\LaravelScanLogin\ScanLoginServiceProvider"
```

### 3. 配置环境变量

在 `.env` 文件中添加以下配置（可选）：

```env
# Token 过期时间（分钟，默认：5）
SCAN_LOGIN_TOKEN_EXPIRY_MINUTES=5

# QR 码大小（像素，默认：200）
SCAN_LOGIN_QR_CODE_SIZE=200

# 登录成功后的重定向地址（默认：/）
SCAN_LOGIN_SUCCESS_REDIRECT=/dashboard

# 状态轮询间隔（秒，默认：3）
SCAN_LOGIN_POLLING_INTERVAL=3

# Token 长度（字符数，默认：64）
SCAN_LOGIN_TOKEN_LENGTH=64

# 启用/禁用扫码登录功能（默认：true）
SCAN_LOGIN_ENABLED=true

# 启用/禁用 GeoIP 地理位置显示（默认：true）
SCAN_LOGIN_ENABLE_GEOIP=true
```

### 4. 配置 GeoIP（可选）

本包使用 `torann/geoip` 来显示登录地理位置信息。首先发布 GeoIP 配置文件：

```bash
php artisan vendor:publish --provider="Torann\GeoIP\GeoIPServiceProvider" --tag=config
```

然后更新 GeoIP 数据库：

```bash
php artisan geoip:update
```

如果不需要地理位置功能，可以在 `.env` 中设置：

```env
SCAN_LOGIN_ENABLE_GEOIP=false
```

### 5. 运行迁移

```bash
php artisan migrate
```

## 使用方法

安装完成后，包会自动注册路由：

- `/scan-login` - 桌面端二维码登录页面
- `/scan-login/{token}` - 移动端确认登录页面

### 基本使用

1. **桌面端**：用户访问 `/scan-login` 页面，系统会显示二维码
2. **移动端**：用户扫描二维码后，会跳转到确认页面
3. **确认登录**：用户在移动端确认后，桌面端自动完成登录

## 登录流程

### 1. 桌面端流程

1. 用户访问扫码登录页面
2. 系统生成 QR 码，包含登录链接
3. 用户使用手机扫描 QR 码
4. 系统轮询检查登录状态
5. 登录成功后自动跳转

### 2. 移动端流程

1. 用户扫描 QR 码
2. 跳转到移动端确认页面（需要登录）
3. 显示登录设备信息
4. 用户确认或取消登录
5. 桌面端自动完成登录

## 自定义视图

### 发布视图文件

```bash
php artisan vendor:publish --provider="Wuwx\LaravelScanLogin\ScanLoginServiceProvider" --tag="views"
```

视图文件位于 `resources/views/vendor/scan-login/`：

- `livewire/pages/qr-code-login-page.blade.php` - 桌面端二维码页面
- `livewire/pages/mobile-login-confirm-page.blade.php` - 移动端确认页面

## 事件系统

本包提供了完整的事件系统，允许你在登录流程的各个阶段执行自定义逻辑。

### 可用事件

- `ScanLoginTokenCreated` - Token 创建时触发
- `ScanLoginTokenClaimed` - 二维码被扫描时触发
- `ScanLoginTokenConsumed` - 登录成功时触发
- `ScanLoginTokenCancelled` - 用户取消登录时触发
- `ScanLoginTokenExpired` - Token 过期时触发

### 监听事件示例

在 `app/Providers/EventServiceProvider.php` 中注册监听器：

```php
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;
use App\Listeners\SendLoginNotification;

protected $listen = [
    ScanLoginTokenConsumed::class => [
        SendLoginNotification::class,
    ],
];
```

创建监听器：

```php
<?php

namespace App\Listeners;

use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;

class SendLoginNotification
{
    public function handle(ScanLoginTokenConsumed $event): void
    {
        $user = \App\Models\User::find($event->consumerId);
        
        // 发送登录通知
        $user->notify(new \App\Notifications\ScanLoginSuccess($event->token));
    }
}
```

详细文档请参考 [EVENTS.md](EVENTS.md)。

## 维护命令

### 清理过期 Token

定期清理过期的 token 以保持数据库整洁：

```bash
# 删除 7 天前的 token（默认）
php artisan scan-login:cleanup

# 删除 30 天前的 token
php artisan scan-login:cleanup --days=30

# 只删除特定状态的 token
php artisan scan-login:cleanup --status=expired

# 预览将要删除的 token（不实际删除）
php artisan scan-login:cleanup --dry-run

# 强制删除，不需要确认
php artisan scan-login:cleanup --force
```

### 查看统计信息

查看 token 使用统计：

```bash
# 查看最近 30 天的统计（默认）
php artisan scan-login:stats

# 查看最近 7 天的统计
php artisan scan-login:stats --days=7
```

### 自动清理

建议在调度任务中添加自动清理：

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // 每天清理 7 天前的 token
    $schedule->command('scan-login:cleanup --force')->daily();
    
    // 每月更新 GeoIP 数据库
    $schedule->command('geoip:update')->monthly();
}
```

## 安全考虑

1. **Token 过期**：Token 默认 5 分钟后过期
2. **状态验证**：严格的状态转换规则
3. **设备信息记录**：记录 IP 地址和用户代理
4. **地理位置显示**：使用本地 GeoIP 数据库，无需外部 API 调用
5. **速率限制**：防止暴力攻击和滥用行为
6. **自动清理**：建议定期清理过期 token

## 速率限制

本包内置了完整的速率限制功能，防止暴力攻击和滥用。

### 配置

在 `.env` 文件中配置：

```env
# 启用/禁用速率限制
SCAN_LOGIN_RATE_LIMIT_ENABLED=true

# 最大尝试次数
SCAN_LOGIN_RATE_LIMIT_MAX_ATTEMPTS=10

# 时间窗口（分钟）
SCAN_LOGIN_RATE_LIMIT_DECAY_MINUTES=1

# 限制策略：ip, user, ip_and_user, session
SCAN_LOGIN_RATE_LIMIT_STRATEGY=ip
```

### 功能特性

- ✅ 多种限制策略（IP、用户、会话）
- ✅ 针对不同操作的独立限制
- ✅ IP 白名单和黑名单
- ✅ 自动日志记录
- ✅ 优雅的错误提示

详细文档请参考 [RATE_LIMITING.md](RATE_LIMITING.md)。

## 多语言支持

本包提供完整的多语言支持，默认包含简体中文和英文。

### 支持的语言

- 🇨🇳 简体中文 (zh_CN)
- 🇺🇸 英文 (en)

### 配置语言

在 `config/app.php` 或 `.env` 中设置：

```env
APP_LOCALE=zh_CN  # 或 en
```

### 发布语言文件

```bash
php artisan vendor:publish --provider="Wuwx\LaravelScanLogin\ScanLoginServiceProvider" --tag="translations"
```

### 在视图中使用

```blade
<h1>{{ __('scan-login::scan-login.qr_code_page_title') }}</h1>
<p>{{ __('scan-login::scan-login.qr_code_page_subtitle') }}</p>
```

详细文档请参考 [LOCALIZATION.md](LOCALIZATION.md)。

## GeoIP 功能说明

本包集成了 `torann/geoip` 来提供基于 IP 的地理位置显示功能：

### 特性

- 本地数据库查询，无需外部 API 调用
- 自动缓存查询结果（24小时）
- 显示国家、省份/州、城市信息
- 可通过配置开关启用/禁用

### 配置

GeoIP 功能默认启用。如需禁用，在 `.env` 中设置：

```env
SCAN_LOGIN_ENABLE_GEOIP=false
```

### 更新 GeoIP 数据库

建议定期更新 GeoIP 数据库以获得最准确的位置信息：

```bash
php artisan geoip:update
```

可以在调度任务中添加自动更新：

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('geoip:update')->monthly();
}
```

## 故障排除

### 常见问题

1. **QR 码不显示**
   - 检查 `simplesoftwareio/simple-qrcode` 包是否正确安装
   - 检查视图文件是否存在

2. **状态不更新**
   - 检查 Livewire 组件是否正确注册
   - 检查 JavaScript 是否正常加载

3. **登录失败**
   - 检查用户是否已认证
   - 检查 Token 是否过期
   - 检查状态转换是否正确

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License. 详见 [LICENSE](LICENSE.md) 文件。