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
```

### 4. 运行迁移

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

## 安全考虑

1. **Token 过期**：Token 默认 5 分钟后过期
2. **状态验证**：严格的状态转换规则
3. **设备信息记录**：记录 IP 地址和用户代理
4. **自动清理**：建议定期清理过期 token

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