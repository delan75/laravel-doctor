<?php

namespace LaravelDoctor;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * LaravelDoctor - Comprehensive Laravel Application Diagnostics Tool
 * 
 * Performs thorough diagnostics on Laravel projects to detect misconfigurations,
 * security risks, environment issues, and other potential problems.
 */
class LaravelDoctor
{
    use LaravelDoctorExtensions, LaravelDoctorWebhooks;
    /**
     * Diagnostic result levels
     */
    const LEVEL_OK = 'ok';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Configuration options
     */
    protected array $config = [
        'send_email_alerts' => false,
        'admin_email' => null,
        'ignored_paths' => [],
        'custom_checks' => [],
        'export_format' => 'array',
        'colorized_output' => true,
    ];

    /**
     * Diagnostic results storage
     */
    protected array $results = [];

    /**
     * Critical issues counter
     */
    protected int $criticalIssues = 0;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        
        // Set admin email from environment if not provided
        if (!$this->config['admin_email'] && env('ADMIN_ALERT_EMAIL')) {
            $this->config['admin_email'] = env('ADMIN_ALERT_EMAIL');
            $this->config['send_email_alerts'] = true;
        }
    }

    /**
     * Run all diagnostic checks
     */
    public function diagnose(): array
    {
        $this->results = [];
        $this->criticalIssues = 0;

        $this->addResult('Laravel Doctor Starting Diagnostics', self::LEVEL_INFO, 'Beginning comprehensive system check...');

        try {
            // Core diagnostic checks
            $this->checkEnvironment();
            $this->checkFilesystemPermissions();
            $this->checkSecurity();
            $this->checkDatabaseConnectivity();
            $this->checkServiceConnectivity();
            $this->checkLogFiles();
            $this->checkCodeQuality();
            $this->checkComposerDependencies();
            $this->checkScheduleAndQueues();
            $this->checkVersionConsistency();

            // Run custom checks if any
            $this->runCustomChecks();

            // Send email alerts if configured and critical issues found
            if ($this->config['send_email_alerts'] && $this->criticalIssues > 0) {
                $this->sendEmailAlert();
            }

            // Send webhook notifications if configured and critical issues found
            if ($this->criticalIssues > 0) {
                $this->sendWebhookNotifications();
            }

        } catch (Exception $e) {
            $this->addResult(
                'Diagnostic Process Error',
                self::LEVEL_ERROR,
                'An error occurred during diagnostics: ' . $e->getMessage()
            );
        }

        $this->addResult(
            'Diagnostics Complete',
            $this->criticalIssues > 0 ? self::LEVEL_WARNING : self::LEVEL_OK,
            sprintf('Found %d critical issues, %d total checks performed', 
                $this->criticalIssues, 
                count($this->results)
            )
        );

        return $this->results;
    }

    /**
     * Add a diagnostic result
     */
    protected function addResult(string $message, string $level, string $advice = '', array $details = []): void
    {
        $result = [
            'message' => $message,
            'level' => $level,
            'advice' => $advice,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ];

        $this->results[] = $result;

        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL])) {
            $this->criticalIssues++;
        }
    }

    /**
     * Check environment configuration
     */
    protected function checkEnvironment(): void
    {
        // Check if .env file exists and is readable
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->addResult(
                '.env File Missing',
                self::LEVEL_CRITICAL,
                'Create a .env file in your project root. Copy from .env.example if available.'
            );
            return;
        }

        if (!is_readable($envPath)) {
            $this->addResult(
                '.env File Not Readable',
                self::LEVEL_ERROR,
                'Ensure the .env file has proper read permissions.'
            );
            return;
        }

        // Check critical environment variables
        $criticalVars = [
            'APP_ENV' => 'Application environment not set',
            'APP_KEY' => 'Application key not set - run php artisan key:generate',
            'APP_DEBUG' => 'Debug mode not configured',
            'APP_URL' => 'Application URL not set',
            'DB_CONNECTION' => 'Database connection not configured',
            'DB_HOST' => 'Database host not set',
            'DB_DATABASE' => 'Database name not set',
            'CACHE_DRIVER' => 'Cache driver not configured',
            'SESSION_DRIVER' => 'Session driver not configured',
            'QUEUE_CONNECTION' => 'Queue connection not configured',
        ];

        foreach ($criticalVars as $var => $message) {
            $value = env($var);
            if (empty($value)) {
                $this->addResult(
                    "Missing Environment Variable: {$var}",
                    self::LEVEL_ERROR,
                    $message
                );
            }
        }

        // Check for production security issues
        $this->checkProductionSecurity();

        // Check for sensitive file exposure
        $this->checkSensitiveFileExposure();

        $this->addResult(
            'Environment Configuration Check Complete',
            self::LEVEL_OK,
            'Environment variables validated successfully.'
        );
    }

    /**
     * Check production security configurations
     */
    protected function checkProductionSecurity(): void
    {
        $appEnv = env('APP_ENV');
        $appDebug = env('APP_DEBUG');
        $appKey = env('APP_KEY');

        // Check if debug is enabled in production
        if ($appEnv === 'production' && $appDebug) {
            $this->addResult(
                'Debug Mode Enabled in Production',
                self::LEVEL_CRITICAL,
                'Set APP_DEBUG=false in production environment for security.'
            );
        }

        // Check for default or missing APP_KEY
        if (empty($appKey) || $appKey === 'base64:' || strlen($appKey) < 32) {
            $this->addResult(
                'Weak or Missing Application Key',
                self::LEVEL_CRITICAL,
                'Generate a strong application key using: php artisan key:generate'
            );
        }

        // Check .env file permissions in production
        if ($appEnv === 'production') {
            $envPath = base_path('.env');
            $permissions = substr(sprintf('%o', fileperms($envPath)), -4);

            if ($permissions !== '0600' && $permissions !== '0644') {
                $this->addResult(
                    'Insecure .env File Permissions',
                    self::LEVEL_WARNING,
                    'Set .env file permissions to 600 or 644 for security: chmod 600 .env'
                );
            }

            // Check if .env is writable (security risk in production)
            if (is_writable($envPath)) {
                $this->addResult(
                    '.env File is Writable in Production',
                    self::LEVEL_WARNING,
                    'Consider making .env read-only in production: chmod 444 .env'
                );
            }
        }
    }

    /**
     * Check for sensitive file exposure
     */
    protected function checkSensitiveFileExposure(): void
    {
        $sensitiveFiles = [
            '.env.backup',
            '.env.example',
            '.env.local',
            '.env.testing',
            'database.sqlite',
            'storage/database.sqlite',
        ];

        $publicPath = public_path();
        $basePath = base_path();

        foreach ($sensitiveFiles as $file) {
            // Check in public directory
            if (file_exists($publicPath . '/' . $file)) {
                $this->addResult(
                    "Sensitive File in Public Directory: {$file}",
                    self::LEVEL_CRITICAL,
                    "Remove {$file} from the public directory immediately."
                );
            }

            // Check if accessible via web (basic check)
            $webAccessiblePaths = [
                '.git',
                '.DS_Store',
                'composer.json',
                'composer.lock',
                'package.json',
                'webpack.mix.js',
                'artisan',
            ];

            foreach ($webAccessiblePaths as $path) {
                if (file_exists($publicPath . '/' . $path)) {
                    $this->addResult(
                        "Development File in Public Directory: {$path}",
                        self::LEVEL_WARNING,
                        "Remove {$path} from public directory or block access via .htaccess"
                    );
                }
            }
        }
    }

    /**
     * Check filesystem permissions
     */
    protected function checkFilesystemPermissions(): void
    {
        // Directories that must be writable
        $writableDirectories = [
            'storage',
            'storage/app',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ];

        foreach ($writableDirectories as $dir) {
            $fullPath = base_path($dir);

            if (!file_exists($fullPath)) {
                $this->addResult(
                    "Missing Directory: {$dir}",
                    self::LEVEL_ERROR,
                    "Create the missing directory: mkdir -p {$dir}"
                );
                continue;
            }

            if (!is_writable($fullPath)) {
                $this->addResult(
                    "Directory Not Writable: {$dir}",
                    self::LEVEL_ERROR,
                    "Make directory writable: chmod 755 {$dir} or chmod 775 {$dir}"
                );
            }

            // Check for overly permissive permissions (777)
            $permissions = substr(sprintf('%o', fileperms($fullPath)), -3);
            if ($permissions === '777') {
                $this->addResult(
                    "Insecure Directory Permissions: {$dir}",
                    self::LEVEL_WARNING,
                    "Directory has 777 permissions. Use 755 or 775 instead: chmod 755 {$dir}"
                );
            }
        }

        // Check specific log file writability
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile) && !is_writable($logFile)) {
            $this->addResult(
                'Laravel Log File Not Writable',
                self::LEVEL_ERROR,
                'Make log file writable: chmod 664 storage/logs/laravel.log'
            );
        }

        // Check for proper ownership (if running on Unix-like systems)
        if (function_exists('posix_getpwuid') && !$this->isWindows()) {
            $this->checkFileOwnership();
        }

        $this->addResult(
            'Filesystem Permissions Check Complete',
            self::LEVEL_OK,
            'File system permissions validated successfully.'
        );
    }

    /**
     * Check file ownership for security
     */
    protected function checkFileOwnership(): void
    {
        $criticalPaths = [
            base_path('.env'),
            base_path('composer.json'),
            storage_path(),
            base_path('bootstrap/cache'),
        ];

        $webServerUser = $this->getWebServerUser();

        foreach ($criticalPaths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $fileOwner = posix_getpwuid(fileowner($path));
            $currentUser = posix_getpwuid(posix_geteuid());

            if ($fileOwner && $currentUser && $fileOwner['name'] !== $currentUser['name']) {
                // Only warn if it's not the web server user
                if ($webServerUser && $fileOwner['name'] !== $webServerUser) {
                    $this->addResult(
                        "File Ownership Issue: " . basename($path),
                        self::LEVEL_WARNING,
                        "File owned by {$fileOwner['name']}, consider changing to {$currentUser['name']} or {$webServerUser}"
                    );
                }
            }
        }
    }

    /**
     * Detect web server user
     */
    protected function getWebServerUser(): ?string
    {
        $commonWebUsers = ['www-data', 'apache', 'nginx', 'httpd'];

        foreach ($commonWebUsers as $user) {
            if (posix_getpwnam($user)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Check if running on Windows
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Check security configurations
     */
    protected function checkSecurity(): void
    {
        // Check for debug tools in production
        $this->checkDebugTools();

        // Check for exposed sensitive files
        $this->checkExposedFiles();

        // Check for backup files and database dumps
        $this->checkBackupFiles();

        // Check security headers and configurations
        $this->checkSecurityHeaders();

        // Check for common security misconfigurations
        $this->checkSecurityMisconfigurations();

        $this->addResult(
            'Security Check Complete',
            self::LEVEL_OK,
            'Security configurations validated successfully.'
        );
    }

    /**
     * Check for debug tools that shouldn't be enabled in production
     */
    protected function checkDebugTools(): void
    {
        $appEnv = env('APP_ENV');
        $isProduction = $appEnv === 'production';

        // Check Laravel Debugbar
        if (class_exists('Barryvdh\Debugbar\ServiceProvider')) {
            $debugbarEnabled = config('debugbar.enabled', false);

            if ($isProduction && $debugbarEnabled) {
                $this->addResult(
                    'Laravel Debugbar Enabled in Production',
                    self::LEVEL_CRITICAL,
                    'Disable Debugbar in production: Set DEBUGBAR_ENABLED=false in .env'
                );
            }
        }

        // Check Laravel Telescope
        if (class_exists('Laravel\Telescope\TelescopeServiceProvider')) {
            $telescopeEnabled = config('telescope.enabled', true);

            if ($isProduction && $telescopeEnabled) {
                $this->addResult(
                    'Laravel Telescope Enabled in Production',
                    self::LEVEL_CRITICAL,
                    'Disable Telescope in production or restrict access properly'
                );
            }
        }

        // Check for other debug packages
        $debugPackages = [
            'Laravel\Tinker\TinkerServiceProvider' => 'Tinker',
            'Spatie\LaravelIgnition\IgnitionServiceProvider' => 'Ignition',
            'Facade\Ignition\IgnitionServiceProvider' => 'Ignition (Legacy)',
        ];

        foreach ($debugPackages as $class => $name) {
            if (class_exists($class) && $isProduction) {
                $this->addResult(
                    "{$name} Available in Production",
                    self::LEVEL_WARNING,
                    "Consider removing or securing {$name} in production environment"
                );
            }
        }
    }

    /**
     * Check for exposed sensitive files
     */
    protected function checkExposedFiles(): void
    {
        $publicPath = public_path();
        $sensitiveFiles = [
            '.git' => 'Git repository',
            '.env' => 'Environment file',
            '.DS_Store' => 'macOS system file',
            'Thumbs.db' => 'Windows thumbnail cache',
            'composer.json' => 'Composer configuration',
            'composer.lock' => 'Composer lock file',
            'package.json' => 'NPM configuration',
            'webpack.mix.js' => 'Laravel Mix configuration',
            'artisan' => 'Laravel Artisan command',
            'phpunit.xml' => 'PHPUnit configuration',
            '.phpunit.result.cache' => 'PHPUnit cache',
        ];

        foreach ($sensitiveFiles as $file => $description) {
            if (file_exists($publicPath . '/' . $file)) {
                $this->addResult(
                    "Exposed Sensitive File: {$file}",
                    self::LEVEL_CRITICAL,
                    "Remove {$description} from public directory: rm -rf public/{$file}"
                );
            }
        }

        // Check for common backup file patterns
        $backupPatterns = ['*.bak', '*.backup', '*.old', '*.orig', '*.tmp'];
        foreach ($backupPatterns as $pattern) {
            $files = glob($publicPath . '/' . $pattern);
            foreach ($files as $file) {
                $filename = basename($file);
                $this->addResult(
                    "Backup File in Public Directory: {$filename}",
                    self::LEVEL_WARNING,
                    "Remove backup file from public directory: rm public/{$filename}"
                );
            }
        }
    }

    /**
     * Check for backup files and database dumps
     */
    protected function checkBackupFiles(): void
    {
        $basePath = base_path();
        $publicPath = public_path();

        $dangerousFiles = [
            '*.sql',
            '*.dump',
            '*.zip',
            '*.tar.gz',
            '*.tar',
            'database.sqlite',
            'backup.sql',
            'dump.sql',
        ];

        $searchPaths = [$publicPath, $basePath];

        foreach ($searchPaths as $searchPath) {
            foreach ($dangerousFiles as $pattern) {
                $files = glob($searchPath . '/' . $pattern);
                foreach ($files as $file) {
                    $filename = basename($file);
                    $location = $searchPath === $publicPath ? 'public directory' : 'project root';

                    $level = $searchPath === $publicPath ? self::LEVEL_CRITICAL : self::LEVEL_WARNING;

                    $this->addResult(
                        "Potentially Sensitive File: {$filename}",
                        $level,
                        "Review and secure or remove {$filename} from {$location}"
                    );
                }
            }
        }
    }

    /**
     * Check security headers and configurations
     */
    protected function checkSecurityHeaders(): void
    {
        // Check if HTTPS is enforced
        $appUrl = env('APP_URL');
        if ($appUrl && !str_starts_with($appUrl, 'https://') && env('APP_ENV') === 'production') {
            $this->addResult(
                'HTTPS Not Enforced',
                self::LEVEL_WARNING,
                'Use HTTPS in production: Update APP_URL to use https:// protocol'
            );
        }

        // Check session security settings
        $sessionSecure = config('session.secure', false);
        $sessionHttpOnly = config('session.http_only', true);

        if (env('APP_ENV') === 'production') {
            if (!$sessionSecure) {
                $this->addResult(
                    'Session Cookies Not Secure',
                    self::LEVEL_WARNING,
                    'Enable secure session cookies: Set SESSION_SECURE_COOKIE=true'
                );
            }

            if (!$sessionHttpOnly) {
                $this->addResult(
                    'Session Cookies Not HTTP Only',
                    self::LEVEL_WARNING,
                    'Enable HTTP-only session cookies for XSS protection'
                );
            }
        }
    }

    /**
     * Check for common security misconfigurations
     */
    protected function checkSecurityMisconfigurations(): void
    {
        // Check if error reporting is disabled in production
        if (env('APP_ENV') === 'production') {
            $errorReporting = ini_get('display_errors');
            if ($errorReporting) {
                $this->addResult(
                    'Error Display Enabled in Production',
                    self::LEVEL_WARNING,
                    'Disable error display in production PHP configuration'
                );
            }
        }

        // Check for weak session configuration
        $sessionLifetime = config('session.lifetime', 120);
        if ($sessionLifetime > 1440) { // More than 24 hours
            $this->addResult(
                'Long Session Lifetime',
                self::LEVEL_INFO,
                'Consider shorter session lifetime for better security'
            );
        }

        // Check CSRF protection
        if (!in_array('web', config('app.middleware_groups', []))) {
            $this->addResult(
                'Web Middleware Group Missing',
                self::LEVEL_WARNING,
                'Ensure web middleware group includes CSRF protection'
            );
        }
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabaseConnectivity(): void
    {
        try {
            // Check if database configuration exists
            $defaultConnection = config('database.default');
            if (!$defaultConnection) {
                $this->addResult(
                    'No Default Database Connection',
                    self::LEVEL_ERROR,
                    'Set DB_CONNECTION in .env file'
                );
                return;
            }

            $dbConfig = config("database.connections.{$defaultConnection}");
            if (!$dbConfig) {
                $this->addResult(
                    "Database Connection '{$defaultConnection}' Not Configured",
                    self::LEVEL_ERROR,
                    'Check database configuration in config/database.php'
                );
                return;
            }

            // Test database connection
            $this->testDatabaseConnection($defaultConnection, $dbConfig);

            // Check for SQLite in production
            $this->checkSQLiteInProduction($defaultConnection, $dbConfig);

            // Test basic database operations
            $this->testDatabaseOperations();

            $this->addResult(
                'Database Connectivity Check Complete',
                self::LEVEL_OK,
                'Database connection validated successfully.'
            );

        } catch (Exception $e) {
            $this->addResult(
                'Database Check Failed',
                self::LEVEL_ERROR,
                'Database connectivity check failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Test database connection
     */
    protected function testDatabaseConnection(string $connection, array $config): void
    {
        try {
            // Attempt to connect and run a simple query
            DB::connection($connection)->select('SELECT 1 as test');

            $this->addResult(
                "Database Connection '{$connection}' Working",
                self::LEVEL_OK,
                'Successfully connected to database'
            );

        } catch (Exception $e) {
            $this->addResult(
                "Database Connection '{$connection}' Failed",
                self::LEVEL_CRITICAL,
                'Cannot connect to database: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check for SQLite usage in production
     */
    protected function checkSQLiteInProduction(string $connection, array $config): void
    {
        if (env('APP_ENV') === 'production' && $config['driver'] === 'sqlite') {
            $this->addResult(
                'SQLite Used in Production',
                self::LEVEL_WARNING,
                'Consider using a more robust database system (MySQL, PostgreSQL) for production'
            );
        }
    }

    /**
     * Test basic database operations
     */
    protected function testDatabaseOperations(): void
    {
        try {
            // Test if we can run migrations table query
            $migrationTable = config('database.migrations', 'migrations');

            if (DB::getSchemaBuilder()->hasTable($migrationTable)) {
                $migrationCount = DB::table($migrationTable)->count();

                $this->addResult(
                    'Database Migrations Table Found',
                    self::LEVEL_INFO,
                    "Found {$migrationCount} migration records"
                );
            } else {
                $this->addResult(
                    'No Migrations Table Found',
                    self::LEVEL_WARNING,
                    'Run migrations: php artisan migrate'
                );
            }

            // Check for common Laravel tables
            $commonTables = ['users', 'password_resets', 'failed_jobs'];
            $existingTables = [];

            foreach ($commonTables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $existingTables[] = $table;
                }
            }

            if (!empty($existingTables)) {
                $this->addResult(
                    'Laravel Tables Found',
                    self::LEVEL_INFO,
                    'Found tables: ' . implode(', ', $existingTables)
                );
            }

        } catch (Exception $e) {
            $this->addResult(
                'Database Operations Test Failed',
                self::LEVEL_WARNING,
                'Could not test database operations: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check service connectivity (Redis, Cache, Queue, Mail)
     */
    protected function checkServiceConnectivity(): void
    {
        // Check Redis connectivity
        $this->checkRedisConnectivity();

        // Check cache system
        $this->checkCacheSystem();

        // Check queue system
        $this->checkQueueSystem();

        // Check mail configuration
        $this->checkMailConfiguration();

        $this->addResult(
            'Service Connectivity Check Complete',
            self::LEVEL_OK,
            'Service connectivity validated successfully.'
        );
    }

    /**
     * Check Redis connectivity
     */
    protected function checkRedisConnectivity(): void
    {
        $cacheDriver = config('cache.default');
        $sessionDriver = config('session.driver');
        $queueConnection = config('queue.default');

        $usesRedis = in_array('redis', [$cacheDriver, $sessionDriver]) ||
                    config("queue.connections.{$queueConnection}.driver") === 'redis';

        if (!$usesRedis) {
            $this->addResult(
                'Redis Not Configured',
                self::LEVEL_INFO,
                'Redis is not being used for cache, sessions, or queues'
            );
            return;
        }

        try {
            // Test Redis connection
            $redis = Redis::connection();
            $result = $redis->ping();

            if ($result) {
                $this->addResult(
                    'Redis Connection Working',
                    self::LEVEL_OK,
                    'Successfully connected to Redis server'
                );

                // Get Redis info
                $info = $redis->info();
                if (isset($info['redis_version'])) {
                    $this->addResult(
                        'Redis Server Info',
                        self::LEVEL_INFO,
                        "Redis version: {$info['redis_version']}"
                    );
                }
            }

        } catch (Exception $e) {
            $this->addResult(
                'Redis Connection Failed',
                self::LEVEL_ERROR,
                'Cannot connect to Redis: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check cache system
     */
    protected function checkCacheSystem(): void
    {
        try {
            $cacheDriver = config('cache.default');

            if (!$cacheDriver) {
                $this->addResult(
                    'Cache Driver Not Set',
                    self::LEVEL_WARNING,
                    'Set CACHE_DRIVER in .env file'
                );
                return;
            }

            // Test cache operations
            $testKey = 'laravel_doctor_test_' . time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);

            if ($retrieved === $testValue) {
                Cache::forget($testKey);

                $this->addResult(
                    "Cache System Working ({$cacheDriver})",
                    self::LEVEL_OK,
                    'Cache read/write operations successful'
                );
            } else {
                $this->addResult(
                    'Cache System Not Working',
                    self::LEVEL_ERROR,
                    'Cache read/write test failed'
                );
            }

        } catch (Exception $e) {
            $this->addResult(
                'Cache System Check Failed',
                self::LEVEL_ERROR,
                'Cache system error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check queue system
     */
    protected function checkQueueSystem(): void
    {
        try {
            $queueConnection = config('queue.default');

            if (!$queueConnection) {
                $this->addResult(
                    'Queue Connection Not Set',
                    self::LEVEL_WARNING,
                    'Set QUEUE_CONNECTION in .env file'
                );
                return;
            }

            $queueConfig = config("queue.connections.{$queueConnection}");

            if (!$queueConfig) {
                $this->addResult(
                    "Queue Connection '{$queueConnection}' Not Configured",
                    self::LEVEL_ERROR,
                    'Check queue configuration in config/queue.php'
                );
                return;
            }

            // Test if queue connection can be resolved
            $queue = Queue::connection($queueConnection);

            $this->addResult(
                "Queue Connection Working ({$queueConnection})",
                self::LEVEL_OK,
                'Queue connection resolved successfully'
            );

            // Check for failed jobs
            if ($queueConfig['driver'] === 'database') {
                $this->checkFailedJobs();
            }

        } catch (Exception $e) {
            $this->addResult(
                'Queue System Check Failed',
                self::LEVEL_ERROR,
                'Queue system error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check for failed jobs
     */
    protected function checkFailedJobs(): void
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobsCount = DB::table('failed_jobs')->count();

                if ($failedJobsCount > 0) {
                    $this->addResult(
                        'Failed Jobs Found',
                        self::LEVEL_WARNING,
                        "Found {$failedJobsCount} failed jobs. Review with: php artisan queue:failed"
                    );
                } else {
                    $this->addResult(
                        'No Failed Jobs',
                        self::LEVEL_OK,
                        'Queue is running without failed jobs'
                    );
                }
            }
        } catch (Exception $e) {
            $this->addResult(
                'Failed Jobs Check Error',
                self::LEVEL_WARNING,
                'Could not check failed jobs: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check mail configuration
     */
    protected function checkMailConfiguration(): void
    {
        try {
            $mailMailer = config('mail.default');

            if (!$mailMailer) {
                $this->addResult(
                    'Mail Mailer Not Set',
                    self::LEVEL_WARNING,
                    'Set MAIL_MAILER in .env file'
                );
                return;
            }

            $mailConfig = config("mail.mailers.{$mailMailer}");

            if (!$mailConfig) {
                $this->addResult(
                    "Mail Mailer '{$mailMailer}' Not Configured",
                    self::LEVEL_ERROR,
                    'Check mail configuration in config/mail.php'
                );
                return;
            }

            $this->addResult(
                "Mail Configuration Found ({$mailMailer})",
                self::LEVEL_OK,
                'Mail system is configured'
            );

            // Check for common mail configuration issues
            if ($mailMailer === 'smtp') {
                $this->checkSMTPConfiguration($mailConfig);
            }

        } catch (Exception $e) {
            $this->addResult(
                'Mail Configuration Check Failed',
                self::LEVEL_ERROR,
                'Mail system error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check SMTP configuration
     */
    protected function checkSMTPConfiguration(array $config): void
    {
        $requiredFields = ['host', 'port', 'username', 'password'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $this->addResult(
                'SMTP Configuration Incomplete',
                self::LEVEL_WARNING,
                'Missing SMTP fields: ' . implode(', ', $missingFields)
            );
        }

        // Check for insecure SMTP settings
        if (isset($config['encryption']) && $config['encryption'] === null && $config['port'] != 25) {
            $this->addResult(
                'SMTP Encryption Not Set',
                self::LEVEL_WARNING,
                'Consider using TLS or SSL encryption for SMTP'
            );
        }
    }

    /**
     * Check and parse log files
     */
    protected function checkLogFiles(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $this->addResult(
                'Laravel Log File Not Found',
                self::LEVEL_INFO,
                'No log file found at storage/logs/laravel.log'
            );
            return;
        }

        if (!is_readable($logPath)) {
            $this->addResult(
                'Laravel Log File Not Readable',
                self::LEVEL_ERROR,
                'Cannot read log file. Check file permissions.'
            );
            return;
        }

        // Parse log file
        $this->parseLogFile($logPath);

        $this->addResult(
            'Log File Analysis Complete',
            self::LEVEL_OK,
            'Log file analysis completed successfully.'
        );
    }

    /**
     * Parse log file for errors and issues
     */
    protected function parseLogFile(string $logPath): void
    {
        try {
            // Read last 200 lines of log file
            $lines = $this->getLastLines($logPath, 200);

            $errorCounts = [
                'EMERGENCY' => 0,
                'ALERT' => 0,
                'CRITICAL' => 0,
                'ERROR' => 0,
                'WARNING' => 0,
            ];

            $commonIssues = [
                'database' => 0,
                'redis' => 0,
                'mail' => 0,
                'filesystem' => 0,
                'auth' => 0,
                'csrf' => 0,
                'queue' => 0,
            ];

            $recentErrors = [];

            foreach ($lines as $line) {
                // Count error levels
                foreach ($errorCounts as $level => $count) {
                    if (strpos($line, "local.{$level}:") !== false) {
                        $errorCounts[$level]++;

                        // Collect recent critical errors
                        if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'])) {
                            $recentErrors[] = $this->extractLogEntry($line);
                        }
                    }
                }

                // Detect common issues
                $lineUpper = strtoupper($line);
                if (strpos($lineUpper, 'DATABASE') !== false || strpos($lineUpper, 'SQL') !== false) {
                    $commonIssues['database']++;
                }
                if (strpos($lineUpper, 'REDIS') !== false) {
                    $commonIssues['redis']++;
                }
                if (strpos($lineUpper, 'MAIL') !== false || strpos($lineUpper, 'SMTP') !== false) {
                    $commonIssues['mail']++;
                }
                if (strpos($lineUpper, 'FILE') !== false || strpos($lineUpper, 'PERMISSION') !== false) {
                    $commonIssues['filesystem']++;
                }
                if (strpos($lineUpper, 'AUTH') !== false || strpos($lineUpper, 'LOGIN') !== false) {
                    $commonIssues['auth']++;
                }
                if (strpos($lineUpper, 'CSRF') !== false || strpos($lineUpper, 'TOKEN') !== false) {
                    $commonIssues['csrf']++;
                }
                if (strpos($lineUpper, 'QUEUE') !== false || strpos($lineUpper, 'JOB') !== false) {
                    $commonIssues['queue']++;
                }
            }

            // Report findings
            $this->reportLogAnalysis($errorCounts, $commonIssues, $recentErrors);

        } catch (Exception $e) {
            $this->addResult(
                'Log File Parsing Error',
                self::LEVEL_ERROR,
                'Could not parse log file: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get last N lines from a file
     */
    protected function getLastLines(string $filePath, int $lines): array
    {
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $result = [];

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->current();
            if (trim($line) !== '') {
                $result[] = $line;
            }
            $file->next();
        }

        return $result;
    }

    /**
     * Extract relevant information from log entry
     */
    protected function extractLogEntry(string $line): array
    {
        // Extract timestamp, level, and message
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] local\.(\w+): (.+)/';

        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => substr($matches[3], 0, 200), // Truncate long messages
            ];
        }

        return [
            'timestamp' => 'Unknown',
            'level' => 'Unknown',
            'message' => substr($line, 0, 200),
        ];
    }

    /**
     * Report log analysis results
     */
    protected function reportLogAnalysis(array $errorCounts, array $commonIssues, array $recentErrors): void
    {
        // Report error counts
        $totalErrors = array_sum($errorCounts);

        if ($totalErrors > 0) {
            $errorSummary = [];
            foreach ($errorCounts as $level => $count) {
                if ($count > 0) {
                    $errorSummary[] = "{$level}: {$count}";
                }
            }

            $level = $errorCounts['EMERGENCY'] > 0 || $errorCounts['CRITICAL'] > 0 ?
                     self::LEVEL_CRITICAL :
                     ($errorCounts['ERROR'] > 0 ? self::LEVEL_ERROR : self::LEVEL_WARNING);

            $this->addResult(
                'Log Errors Found',
                $level,
                'Found errors in logs: ' . implode(', ', $errorSummary)
            );
        } else {
            $this->addResult(
                'No Recent Log Errors',
                self::LEVEL_OK,
                'No errors found in recent log entries'
            );
        }

        // Report common issues
        foreach ($commonIssues as $issue => $count) {
            if ($count > 5) { // Only report if there are multiple occurrences
                $this->addResult(
                    "Frequent {$issue} Issues in Logs",
                    self::LEVEL_WARNING,
                    "Found {$count} {$issue}-related log entries. Review logs for details."
                );
            }
        }

        // Report recent critical errors (limit to 3 most recent)
        $criticalErrors = array_slice($recentErrors, -3);
        foreach ($criticalErrors as $error) {
            $this->addResult(
                "Recent Critical Error ({$error['timestamp']})",
                self::LEVEL_ERROR,
                $error['message'],
                ['level' => $error['level'], 'timestamp' => $error['timestamp']]
            );
        }
    }

    /**
     * Check code quality with static analysis tools
     */
    protected function checkCodeQuality(): void
    {
        // Check PHP CS Fixer
        $this->checkPhpCsFixer();

        // Check PHPStan/Larastan
        $this->checkPhpStan();

        // Check for common code quality issues
        $this->checkCommonCodeIssues();

        $this->addResult(
            'Code Quality Check Complete',
            self::LEVEL_OK,
            'Code quality analysis completed successfully.'
        );
    }

    /**
     * Check PHP CS Fixer
     */
    protected function checkPhpCsFixer(): void
    {
        $csFixerPaths = [
            base_path('vendor/bin/php-cs-fixer'),
            base_path('vendor/bin/php-cs-fixer.bat'),
            'php-cs-fixer', // Global installation
        ];

        $csFixerPath = null;
        foreach ($csFixerPaths as $path) {
            if (file_exists($path) || $this->commandExists($path)) {
                $csFixerPath = $path;
                break;
            }
        }

        if (!$csFixerPath) {
            $this->addResult(
                'PHP CS Fixer Not Found',
                self::LEVEL_INFO,
                'Install PHP CS Fixer for code style checking: composer require --dev friendsofphp/php-cs-fixer'
            );
            return;
        }

        try {
            // Run PHP CS Fixer in dry-run mode
            $command = "{$csFixerPath} fix --dry-run --format=json app/ 2>/dev/null";
            $output = shell_exec($command);

            if ($output) {
                $result = json_decode($output, true);

                if (isset($result['files']) && count($result['files']) > 0) {
                    $fileCount = count($result['files']);
                    $this->addResult(
                        'Code Style Issues Found',
                        self::LEVEL_WARNING,
                        "PHP CS Fixer found {$fileCount} files with style issues. Run: php-cs-fixer fix"
                    );
                } else {
                    $this->addResult(
                        'Code Style Check Passed',
                        self::LEVEL_OK,
                        'No code style issues found by PHP CS Fixer'
                    );
                }
            }

        } catch (Exception $e) {
            $this->addResult(
                'PHP CS Fixer Check Failed',
                self::LEVEL_WARNING,
                'Could not run PHP CS Fixer: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check PHPStan/Larastan
     */
    protected function checkPhpStan(): void
    {
        $phpStanPaths = [
            base_path('vendor/bin/phpstan'),
            base_path('vendor/bin/phpstan.bat'),
            'phpstan', // Global installation
        ];

        $phpStanPath = null;
        foreach ($phpStanPaths as $path) {
            if (file_exists($path) || $this->commandExists($path)) {
                $phpStanPath = $path;
                break;
            }
        }

        if (!$phpStanPath) {
            $this->addResult(
                'PHPStan Not Found',
                self::LEVEL_INFO,
                'Install PHPStan for static analysis: composer require --dev phpstan/phpstan or nunomaduro/larastan'
            );
            return;
        }

        try {
            // Run PHPStan analysis
            $command = "{$phpStanPath} analyse --no-progress --error-format=json app/ 2>/dev/null";
            $output = shell_exec($command);

            if ($output) {
                $result = json_decode($output, true);

                if (isset($result['totals']['errors']) && $result['totals']['errors'] > 0) {
                    $errorCount = $result['totals']['errors'];
                    $fileCount = $result['totals']['file_errors'];

                    $this->addResult(
                        'Static Analysis Issues Found',
                        self::LEVEL_WARNING,
                        "PHPStan found {$errorCount} issues in {$fileCount} files. Run: phpstan analyse"
                    );
                } else {
                    $this->addResult(
                        'Static Analysis Check Passed',
                        self::LEVEL_OK,
                        'No static analysis issues found by PHPStan'
                    );
                }
            }

        } catch (Exception $e) {
            $this->addResult(
                'PHPStan Check Failed',
                self::LEVEL_WARNING,
                'Could not run PHPStan: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check for common code quality issues
     */
    protected function checkCommonCodeIssues(): void
    {
        // Check for TODO/FIXME comments
        $this->checkTodoComments();

        // Check for debug statements
        $this->checkDebugStatements();

        // Check for large files
        $this->checkLargeFiles();
    }

    /**
     * Check for TODO/FIXME comments
     */
    protected function checkTodoComments(): void
    {
        $appPath = app_path();
        $todoCount = 0;
        $fixmeCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appPath)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $todoCount += substr_count(strtoupper($content), 'TODO');
                $fixmeCount += substr_count(strtoupper($content), 'FIXME');
            }
        }

        if ($todoCount > 0 || $fixmeCount > 0) {
            $this->addResult(
                'TODO/FIXME Comments Found',
                self::LEVEL_INFO,
                "Found {$todoCount} TODO and {$fixmeCount} FIXME comments in code"
            );
        }
    }

    /**
     * Check for debug statements
     */
    protected function checkDebugStatements(): void
    {
        $appPath = app_path();
        $debugStatements = ['dd(', 'dump(', 'var_dump(', 'print_r(', 'error_log('];
        $foundStatements = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appPath)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());

                foreach ($debugStatements as $statement) {
                    if (strpos($content, $statement) !== false) {
                        $foundStatements[] = $statement;
                    }
                }
            }
        }

        if (!empty($foundStatements)) {
            $this->addResult(
                'Debug Statements Found',
                self::LEVEL_WARNING,
                'Found debug statements in code: ' . implode(', ', array_unique($foundStatements))
            );
        }
    }

    /**
     * Check for large files
     */
    protected function checkLargeFiles(): void
    {
        $appPath = app_path();
        $largeFiles = [];
        $maxLines = 500; // Consider files over 500 lines as large

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appPath)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $lineCount = count(file($file->getPathname()));

                if ($lineCount > $maxLines) {
                    $largeFiles[] = [
                        'file' => str_replace($appPath . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                        'lines' => $lineCount
                    ];
                }
            }
        }

        if (!empty($largeFiles)) {
            $fileList = array_map(function($file) {
                return "{$file['file']} ({$file['lines']} lines)";
            }, array_slice($largeFiles, 0, 5)); // Show only first 5

            $this->addResult(
                'Large Files Found',
                self::LEVEL_INFO,
                'Consider refactoring large files: ' . implode(', ', $fileList)
            );
        }
    }

    /**
     * Check if a command exists
     */
    protected function commandExists(string $command): bool
    {
        $whereIsCommand = $this->isWindows() ? 'where' : 'which';
        $output = shell_exec("{$whereIsCommand} {$command} 2>/dev/null");
        return !empty($output);
    }

    // These methods are implemented in LaravelDoctorExtensions trait

    /**
     * Run custom diagnostic checks
     */
    protected function runCustomChecks(): void
    {
        foreach ($this->config['custom_checks'] as $check) {
            if (is_callable($check)) {
                try {
                    $result = call_user_func($check, $this);
                    if (is_array($result)) {
                        $this->addResult(
                            $result['message'] ?? 'Custom Check',
                            $result['level'] ?? self::LEVEL_INFO,
                            $result['advice'] ?? '',
                            $result['details'] ?? []
                        );
                    }
                } catch (Exception $e) {
                    $this->addResult(
                        'Custom Check Error',
                        self::LEVEL_ERROR,
                        'Custom check failed: ' . $e->getMessage()
                    );
                }
            }
        }
    }

    // sendEmailAlert method is implemented in LaravelDoctorExtensions trait

    /**
     * Get results in specified format
     */
    public function getResults(string $format = null): mixed
    {
        $format = $format ?? $this->config['export_format'];

        switch ($format) {
            case 'json':
                return json_encode($this->results, JSON_PRETTY_PRINT);
            case 'html':
                return $this->generateHtmlReport();
            case 'array':
            default:
                return $this->results;
        }
    }

    /**
     * Generate HTML report
     */
    protected function generateHtmlReport(): string
    {
        $summary = $this->getSummary();
        $timestamp = now()->toDateTimeString();

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Doctor Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .summary-card { padding: 15px; border-radius: 6px; text-align: center; }
        .summary-card h3 { margin: 0 0 10px 0; }
        .summary-card .number { font-size: 2em; font-weight: bold; }
        .level-ok { background-color: #d4edda; color: #155724; }
        .level-info { background-color: #d1ecf1; color: #0c5460; }
        .level-warning { background-color: #fff3cd; color: #856404; }
        .level-error { background-color: #f8d7da; color: #721c24; }
        .level-critical { background-color: #f5c6cb; color: #721c24; }
        .results { margin-top: 20px; }
        .result-item { margin-bottom: 15px; padding: 15px; border-left: 4px solid; border-radius: 4px; }
        .result-item h4 { margin: 0 0 8px 0; }
        .result-item .advice { font-style: italic; color: #666; }
        .result-item .timestamp { font-size: 0.9em; color: #999; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🩺 Laravel Doctor Report</h1>
            <p>Generated on ' . $timestamp . '</p>
        </div>

        <div class="summary">
            <div class="summary-card level-info">
                <h3>Total Checks</h3>
                <div class="number">' . $summary['total_checks'] . '</div>
            </div>
            <div class="summary-card ' . ($summary['critical_issues'] > 0 ? 'level-critical' : 'level-ok') . '">
                <h3>Critical Issues</h3>
                <div class="number">' . $summary['critical_issues'] . '</div>
            </div>';

        foreach ($summary['levels'] as $level => $count) {
            if ($count > 0) {
                $html .= '<div class="summary-card level-' . strtolower($level) . '">
                    <h3>' . ucfirst($level) . '</h3>
                    <div class="number">' . $count . '</div>
                </div>';
            }
        }

        $html .= '</div>

        <div class="results">
            <h2>Detailed Results</h2>';

        foreach ($this->results as $result) {
            $levelClass = 'level-' . strtolower($result['level']);
            $html .= '<div class="result-item ' . $levelClass . '">
                <h4>' . htmlspecialchars($result['message']) . '</h4>';

            if (!empty($result['advice'])) {
                $html .= '<div class="advice">' . htmlspecialchars($result['advice']) . '</div>';
            }

            $html .= '<div class="timestamp">Level: ' . strtoupper($result['level']) . ' | ' . $result['timestamp'] . '</div>
            </div>';
        }

        $html .= '</div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        $summary = [
            'total_checks' => count($this->results),
            'critical_issues' => $this->criticalIssues,
            'levels' => [
                self::LEVEL_OK => 0,
                self::LEVEL_INFO => 0,
                self::LEVEL_WARNING => 0,
                self::LEVEL_ERROR => 0,
                self::LEVEL_CRITICAL => 0,
            ]
        ];

        foreach ($this->results as $result) {
            if (isset($summary['levels'][$result['level']])) {
                $summary['levels'][$result['level']]++;
            }
        }

        return $summary;
    }
}
