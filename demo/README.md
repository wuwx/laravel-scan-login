# Laravel Scan Login Demo Application

This demo application showcases the complete functionality of Laravel Scan Login package, including both desktop and mobile user experiences.

## Features Demonstrated

- **QR Code Generation**: Dynamic QR code creation with real-time updates
- **Mobile Login Interface**: Responsive mobile login form
- **Real-time Status Polling**: Desktop browser automatically detects mobile login
- **Error Handling**: Comprehensive error scenarios and recovery
- **Security Features**: HTTPS enforcement, rate limiting, token expiration
- **Customization Examples**: Custom views, styling, and behavior

## Demo Scenarios

### 1. Basic Login Flow
- User visits desktop login page
- QR code is displayed automatically
- User scans QR code with mobile device
- User enters credentials on mobile
- Desktop automatically logs in user

### 2. Error Scenarios
- Token expiration and automatic refresh
- Invalid credentials handling
- Network error recovery
- Rate limiting demonstration

### 3. Customization Examples
- Custom branded mobile login page
- Different QR code sizes and styles
- Custom success/error messages
- Integration with existing authentication

## Quick Start

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Setup Database**
   ```bash
   touch database/demo.sqlite
   php artisan migrate
   php artisan db:seed
   ```

4. **Start Development Server**
   ```bash
   php artisan serve
   ```

5. **Visit Demo**
   - Open http://localhost:8000 in your desktop browser
   - Use your mobile device to scan the QR code
   - Login with demo credentials: `demo@example.com` / `password`

## Demo Pages

- `/` - Home page with feature overview
- `/demo/basic` - Basic scan login implementation
- `/demo/custom` - Custom styled implementation
- `/demo/api` - API integration example
- `/demo/errors` - Error handling demonstration
- `/admin` - Admin dashboard (after login)

## Test Accounts

| Email | Password | Role |
|-------|----------|------|
| demo@example.com | password | User |
| admin@example.com | password | Admin |
| test@example.com | password | User |

## Mobile Testing

For the best demo experience:

1. **Use HTTPS**: Configure SSL for your local development
2. **Mobile Device**: Use a real mobile device for QR code scanning
3. **Network**: Ensure both devices are on the same network
4. **Browser**: Use modern browsers with camera access

## Code Examples

The demo includes practical code examples for:

- Basic integration
- Custom authentication logic
- Error handling
- API usage
- React/Vue.js integration
- Mobile-first design

## Architecture

```
demo/
├── app/
│   ├── Http/Controllers/
│   │   ├── DemoController.php
│   │   ├── ApiDemoController.php
│   │   └── AdminController.php
│   ├── Models/
│   │   └── User.php
│   └── Services/
│       └── CustomScanLoginService.php
├── resources/
│   ├── views/
│   │   ├── demo/
│   │   ├── layouts/
│   │   └── vendor/scan-login/
│   ├── js/
│   │   ├── demo.js
│   │   └── components/
│   └── css/
│       └── demo.css
├── routes/
│   ├── web.php
│   └── api.php
├── database/
│   ├── migrations/
│   └── seeders/
└── public/
    ├── demo-assets/
    └── screenshots/
```

## Contributing

This demo application serves as both a showcase and a testing ground for new features. Contributions are welcome!

## Support

If you encounter issues with the demo:

1. Check the [Troubleshooting Guide](../docs/troubleshooting.md)
2. Review the [Configuration Guide](../docs/configuration.md)
3. Open an issue on [GitHub](https://github.com/wuwx/laravel-scan-login/issues)