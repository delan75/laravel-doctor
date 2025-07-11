# 🩺 Laravel Doctor

A comprehensive Laravel application diagnostics tool with a **beautiful web interface** that performs thorough health checks on your Laravel projects. Laravel Doctor helps developers and system administrators detect misconfigurations, security risks, environment issues, missing packages, and incorrect setups during development, deployment, or troubleshooting.

## ✨ Features

### 🎨 **Modern Web Interface**
- **Beautiful Dashboard** - Responsive web interface with real-time health monitoring
- **Interactive Charts** - Visual analytics with Chart.js integration for health trends
- **Mobile-First Design** - Touch-optimized interface that works perfectly on all devices
- **Real-time Updates** - WebSocket integration for live diagnostic monitoring
- **Smart Notifications** - Browser notifications and customizable alert system
- **Advanced Filtering** - Search, sort, and filter diagnostic results with ease
- **Configuration Management** - Web-based settings panel with live validation
- **Data Visualization** - Comprehensive charts showing health metrics and trends

### 🔍 **Comprehensive Diagnostics**
- **Environment & Configuration Checks** - Validates .env files, critical variables, and security settings
- **Filesystem Permissions** - Ensures proper directory permissions and security
- **Security Validation** - Detects debug tools in production, exposed files, and security risks
- **Database Connectivity** - Tests database connections and configurations
- **Service Connectivity** - Validates Redis, cache, queue, and mail services
- **Log Analysis** - Parses Laravel logs for errors and common issues
- **Code Quality Checks** - Integrates with PHP CS Fixer and PHPStan
- **Composer Dependencies** - Validates dependencies and checks for outdated packages
- **Schedule & Queue Monitoring** - Checks running processes and failed jobs
- **Version Consistency** - Validates Laravel and PHP version compatibility

### 🔔 **Alerts & Integrations**
- **Email & Webhook Alerts** - Sends notifications for critical issues
- **Slack Integration** - Real-time notifications to Slack channels
- **Discord Integration** - Webhook support for Discord servers
- **Multiple Export Formats** - Console, JSON, HTML, and PDF output
- **API Endpoints** - RESTful API for external integrations
- **Prometheus Metrics** - Export metrics for monitoring systems

## 🌟 **What Makes Laravel Doctor Special?**

### 🎨 **Beautiful Web Interface**
Unlike other diagnostic tools that only work in the command line, Laravel Doctor provides a **stunning web interface** that makes monitoring your Laravel application a pleasure:

- **📊 Real-time Dashboard** - Watch your application health in real-time
- **📱 Mobile-First Design** - Perfect experience on phones, tablets, and desktops
- **🎯 Interactive Charts** - Visual analytics with beautiful Chart.js graphs
- **🔍 Smart Search & Filtering** - Find exactly what you're looking for instantly
- **⚙️ Web-Based Configuration** - No more editing config files manually
- **🔔 Smart Notifications** - Get alerted the moment something needs attention

### 🚀 **Production-Ready Features**
- **⚡ Real-time Updates** - WebSocket integration for live monitoring
- **🔒 Enterprise Security** - Authentication, CSRF protection, rate limiting
- **📈 Historical Analytics** - Track your application health over time
- **🌐 API-First Design** - Integrate with your existing monitoring stack
- **📱 Progressive Web App** - Install on mobile devices like a native app

## 🚀 Installation

### Option 1: Composer Installation (Recommended)

```bash
composer require --dev laravel-doctor/laravel-doctor
```

### Option 2: Manual Installation

1. Copy the Laravel Doctor files to your project:
```bash
git clone https://github.com/laravel-doctor/laravel-doctor.git
cd laravel-doctor
composer install
```

2. Add the service provider to your `config/app.php`:
```php
'providers' => [
    // ...
    LaravelDoctor\LaravelDoctorServiceProvider::class,
],
```

3. Publish the configuration and assets:
```bash
php artisan vendor:publish --tag=laravel-doctor-config
php artisan vendor:publish --tag=laravel-doctor-assets
```

4. Add the routes to your `routes/web.php`:
```php
require_once base_path('routes/laravel-doctor.php');
```

## 📖 Usage

### 🌐 Web Interface (Recommended)

Access the beautiful web dashboard at:
```
https://your-app.com/doctor
```

**Features:**
- 📊 **Real-time Dashboard** with health score and interactive charts
- 📱 **Mobile-Responsive** design that works on all devices
- 🔍 **Advanced Filtering** and search capabilities
- ⚙️ **Configuration Management** with live validation
- 🔔 **Smart Notifications** with browser alerts
- 📈 **Data Visualization** showing health trends over time
- 📤 **Export Options** (JSON, HTML, PDF)

### 🖥️ Artisan Command

For command-line usage:

```bash
# Basic usage
php artisan laravel:doctor

# Export results to JSON
php artisan laravel:doctor --format=json --export=reports/doctor-report.json

# Export to HTML
php artisan laravel:doctor --format=html --export=reports/doctor-report.html

# Send email alerts for critical issues
php artisan laravel:doctor --email

# Disable colored output
php artisan laravel:doctor --no-color
```

### Programmatic Usage

```php
use LaravelDoctor\LaravelDoctor;

// Basic usage
$doctor = new LaravelDoctor();
$results = $doctor->diagnose();

// With configuration
$doctor = new LaravelDoctor([
    'send_email_alerts' => true,
    'admin_email' => 'admin@example.com',
    'export_format' => 'json',
]);

$results = $doctor->diagnose();

// Get summary statistics
$summary = $doctor->getSummary();

// Export results
$jsonReport = $doctor->getResults('json');
$htmlReport = $doctor->getResults('html');
```

### 📱 Mobile App Integration

Use the API endpoints for mobile apps or external integrations:

```javascript
// Get health status
fetch('/api/doctor/mobile/health')
  .then(response => response.json())
  .then(data => {
    console.log('Health Score:', data.health_score);
    console.log('Status:', data.status);
  });

// Get full diagnostic data
fetch('/api/doctor/mobile/dashboard')
  .then(response => response.json())
  .then(data => {
    console.log('Results:', data.results);
    console.log('Summary:', data.summary);
  });
```

### 🔗 API Integration

```php
// Get diagnostic data programmatically
$doctor = new LaravelDoctor();
$results = $doctor->diagnose();
$summary = $doctor->getSummary();
$healthScore = $doctor->calculateHealthScore($summary);

// Export results
$jsonReport = $doctor->getResults('json');
$htmlReport = $doctor->getResults('html');
```

## ⚙️ Configuration

### 🌐 Web-Based Configuration

The easiest way to configure Laravel Doctor is through the web interface:

1. Visit `/doctor` in your browser
2. Click the **⚙️ Settings** button
3. Configure all options through the intuitive interface:
   - **General Settings** - Auto-refresh, notifications, themes
   - **Email Alerts** - SMTP settings and alert thresholds
   - **Webhook Integration** - Slack, Discord, and custom webhooks
   - **Diagnostic Checks** - Enable/disable specific checks
   - **Custom Checks** - Add your own diagnostic logic

### 📝 File-Based Configuration

Alternatively, publish and edit the configuration file:

```bash
php artisan vendor:publish --tag=laravel-doctor-config
```

### 🔧 Environment Variables

Add these to your `.env` file:

```env
# Email Alerts
LARAVEL_DOCTOR_EMAIL_ALERTS=true
ADMIN_ALERT_EMAIL=admin@example.com
LARAVEL_DOCTOR_EMAIL_SUBJECT="Laravel Doctor Alert"

# Slack Integration
LARAVEL_DOCTOR_SLACK_ENABLED=true
LARAVEL_DOCTOR_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
LARAVEL_DOCTOR_SLACK_CHANNEL=#alerts

# Discord Integration
LARAVEL_DOCTOR_DISCORD_ENABLED=true
LARAVEL_DOCTOR_DISCORD_WEBHOOK=https://discord.com/api/webhooks/YOUR/DISCORD/WEBHOOK

# Real-time Features
LARAVEL_DOCTOR_WEBSOCKET_ENABLED=true
LARAVEL_DOCTOR_AUTO_REFRESH=true
```

### Custom Checks

Add custom diagnostic checks in your configuration:

```php
// config/laravel-doctor.php
'custom_checks' => [
    \App\Diagnostics\CustomDatabaseCheck::class . '@handle',
    \App\Diagnostics\CustomSecurityCheck::class . '@handle',
],
```

Example custom check:

```php
<?php

namespace App\Diagnostics;

use LaravelDoctor\LaravelDoctor;

class CustomDatabaseCheck
{
    public function handle(LaravelDoctor $doctor): array
    {
        // Your custom logic here
        $isHealthy = $this->checkDatabaseHealth();
        
        return [
            'message' => 'Custom Database Health Check',
            'level' => $isHealthy ? 'ok' : 'error',
            'advice' => $isHealthy ? 'Database is healthy' : 'Database needs attention',
            'details' => ['custom_metric' => $this->getCustomMetric()],
        ];
    }
    
    private function checkDatabaseHealth(): bool
    {
        // Your custom database health logic
        return true;
    }
    
    private function getCustomMetric(): mixed
    {
        // Return custom metrics
        return ['connections' => 10, 'slow_queries' => 2];
    }
}
```

## 🔍 Diagnostic Checks

### Environment & Configuration
- ✅ .env file existence and readability
- ✅ Critical environment variables validation
- ✅ Production security settings
- ✅ Sensitive file exposure detection

### Filesystem Permissions
- ✅ Writable directories check
- ✅ Secure file permissions
- ✅ File ownership validation

### Security
- ✅ Debug tools in production detection
- ✅ Exposed sensitive files
- ✅ Backup files and database dumps
- ✅ Security headers and configurations

### Database
- ✅ Connection testing
- ✅ Configuration validation
- ✅ Migration status
- ✅ SQLite in production warnings

### Services
- ✅ Redis connectivity
- ✅ Cache system testing
- ✅ Queue system validation
- ✅ Mail configuration
- ✅ Failed jobs detection

### Logs
- ✅ Log file analysis
- ✅ Error level counting
- ✅ Common issue detection
- ✅ Recent critical errors

### Code Quality
- ✅ PHP CS Fixer integration
- ✅ PHPStan/Larastan integration
- ✅ TODO/FIXME comment detection
- ✅ Debug statement detection
- ✅ Large file identification

### Dependencies
- ✅ Composer validation
- ✅ Outdated package detection
- ✅ Critical package version checks

### System
- ✅ Schedule configuration
- ✅ Queue worker detection
- ✅ Version consistency
- ✅ PHP version compatibility
- ✅ Laravel EOL detection

## 📊 Output Formats & Interfaces

### 🌐 Web Dashboard
- **Interactive Dashboard** with real-time health monitoring
- **Beautiful Charts** showing health trends and metrics
- **Mobile-Responsive** design for all devices
- **Real-time Notifications** with browser alerts
- **Advanced Filtering** and search capabilities
- **Export Options** (JSON, HTML, PDF)

### 📱 Mobile Interface
- **Touch-Optimized** design with swipe gestures
- **Bottom Navigation** for easy thumb access
- **Pull-to-Refresh** functionality
- **Offline Capability** for viewing cached results
- **Push Notifications** for critical issues

### 🖥️ Console Output
Colorized console output with icons and detailed information.

### 📄 JSON Output
```json
{
  "message": "Database Connection Working",
  "level": "ok",
  "advice": "Successfully connected to database",
  "details": {},
  "timestamp": "2024-01-15T10:30:00.000000Z"
}
```

### 🎨 HTML Output
Beautiful HTML report with summary statistics and detailed results.

### 📊 API Endpoints
- **RESTful API** for external integrations
- **WebSocket Support** for real-time updates
- **Prometheus Metrics** for monitoring systems
- **Health Check Endpoints** for load balancers

## 🔔 Notifications

### Email Alerts
Automatically sends email notifications when critical issues are detected.

### Slack Integration
Sends formatted messages to Slack channels with issue details.

### Discord Integration
Posts notifications to Discord channels with embedded issue information.

### Custom Webhooks
Supports custom webhook integrations for other services.

## 🛠️ Advanced Usage

### 🕒 Scheduled Diagnostics

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('laravel:doctor --email')
             ->daily()
             ->at('09:00');
}
```

### 🔄 CI/CD Integration

```yaml
# GitHub Actions example
- name: Run Laravel Doctor
  run: |
    php artisan laravel:doctor --format=json --export=doctor-report.json
    if [ $? -ne 0 ]; then
      echo "Critical issues found!"
      exit 1
    fi
```

### 🐳 Docker Health Checks

```dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD php artisan laravel:doctor || exit 1
```

### 📊 Monitoring Integration

#### Prometheus Metrics
```bash
# Get metrics for Prometheus
curl https://your-app.com/cli/doctor/metrics
```

#### Health Check for Load Balancers
```bash
# Simple health check endpoint
curl https://your-app.com/health
```

#### Webhook Triggers
```bash
# Trigger diagnostics via webhook
curl -X POST https://your-app.com/webhooks/doctor/trigger
```

### 🔧 Custom Middleware

Add authentication middleware for the web interface:

```php
// In your RouteServiceProvider or web.php
Route::middleware(['auth', 'admin'])->group(function () {
    require_once base_path('routes/laravel-doctor.php');
});
```

## 🎯 Screenshots

### 📊 Dashboard
![Laravel Doctor Dashboard](docs/images/dashboard.png)
*Beautiful, responsive dashboard with real-time health monitoring*

### 📱 Mobile Interface
![Mobile Interface](docs/images/mobile.png)
*Touch-optimized mobile interface with swipe gestures*

### 📈 Analytics
![Analytics Charts](docs/images/analytics.png)
*Comprehensive charts showing health trends and metrics*

### ⚙️ Configuration
![Configuration Panel](docs/images/config.png)
*Easy-to-use configuration management interface*

## 🚀 Performance

Laravel Doctor is designed for **production use** with minimal performance impact:

- ⚡ **Fast Execution** - Optimized diagnostic algorithms
- 🔄 **Async Processing** - Non-blocking real-time updates
- 💾 **Memory Efficient** - Minimal memory footprint
- 📱 **Mobile Optimized** - Lightweight mobile interface
- 🌐 **CDN Ready** - Static assets can be served from CDN

## 🔒 Security

- 🛡️ **Authentication Required** - Web interface requires authentication
- 🔐 **CSRF Protection** - All forms protected against CSRF attacks
- 🚫 **Rate Limiting** - API endpoints have rate limiting
- 🔍 **Input Validation** - All inputs are validated and sanitized
- 📝 **Audit Logging** - All actions are logged for security auditing

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

```bash
git clone https://github.com/laravel-doctor/laravel-doctor.git
cd laravel-doctor
composer install
npm install
npm run dev
```

### Running Tests

```bash
composer test
npm run test
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- **Laravel Framework** - The amazing PHP framework
- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework
- **Chart.js** - Beautiful charts and graphs
- **PHP CS Fixer** - Code style fixing
- **PHPStan/Larastan** - Static analysis
- **All Contributors** - Thank you for making this project better!

## 📞 Support

- 📖 **Documentation**: [https://laravel-doctor.com/docs](https://laravel-doctor.com/docs)
- 💬 **Discord**: [Join our community](https://discord.gg/laravel-doctor)
- 🐛 **Issues**: [GitHub Issues](https://github.com/laravel-doctor/laravel-doctor/issues)
- 📧 **Email**: support@laravel-doctor.com

---

**Laravel Doctor** - Keep your Laravel applications healthy with style! 🩺✨

*Made with ❤️ by Chisolution*

**&copy 2025, Work of Chisolution**
