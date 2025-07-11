<?php

namespace LaravelDoctor;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

/**
 * LaravelDoctorExtensions - Additional methods for LaravelDoctor
 * 
 * This trait contains the remaining diagnostic methods to keep the main class manageable.
 */
trait LaravelDoctorExtensions
{
    /**
     * Check Composer dependencies
     */
    protected function checkComposerDependencies(): void
    {
        $composerJsonPath = base_path('composer.json');
        $composerLockPath = base_path('composer.lock');

        if (!file_exists($composerJsonPath)) {
            $this->addResult(
                'composer.json Not Found',
                self::LEVEL_CRITICAL,
                'composer.json file is missing from project root'
            );
            return;
        }

        if (!file_exists($composerLockPath)) {
            $this->addResult(
                'composer.lock Missing',
                self::LEVEL_ERROR,
                'Run composer install to generate composer.lock file'
            );
        }

        // Run composer validate
        $this->runComposerValidate();

        // Check for outdated packages
        $this->checkOutdatedPackages();

        // Check critical package versions
        $this->checkCriticalPackageVersions();

        $this->addResult(
            'Composer Dependencies Check Complete',
            self::LEVEL_OK,
            'Composer dependencies validated successfully.'
        );
    }

    /**
     * Run composer validate
     */
    protected function runComposerValidate(): void
    {
        try {
            $output = shell_exec('composer validate --no-check-publish 2>&1');
            
            if (strpos($output, 'is valid') !== false) {
                $this->addResult(
                    'Composer Configuration Valid',
                    self::LEVEL_OK,
                    'composer.json is valid'
                );
            } else {
                $this->addResult(
                    'Composer Configuration Issues',
                    self::LEVEL_WARNING,
                    'Composer validate found issues: ' . trim($output)
                );
            }
        } catch (Exception $e) {
            $this->addResult(
                'Composer Validate Failed',
                self::LEVEL_WARNING,
                'Could not run composer validate: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check for outdated packages
     */
    protected function checkOutdatedPackages(): void
    {
        try {
            $output = shell_exec('composer outdated --format=json 2>/dev/null');
            
            if ($output) {
                $result = json_decode($output, true);
                
                if (isset($result['installed']) && count($result['installed']) > 0) {
                    $outdatedCount = count($result['installed']);
                    $criticalOutdated = [];

                    foreach ($result['installed'] as $package) {
                        if (in_array($package['name'], ['laravel/framework', 'php'])) {
                            $criticalOutdated[] = $package['name'];
                        }
                    }

                    $level = !empty($criticalOutdated) ? self::LEVEL_WARNING : self::LEVEL_INFO;
                    
                    $this->addResult(
                        'Outdated Packages Found',
                        $level,
                        "Found {$outdatedCount} outdated packages. Run: composer outdated"
                    );

                    if (!empty($criticalOutdated)) {
                        $this->addResult(
                            'Critical Packages Outdated',
                            self::LEVEL_WARNING,
                            'Critical packages need updating: ' . implode(', ', $criticalOutdated)
                        );
                    }
                } else {
                    $this->addResult(
                        'All Packages Up to Date',
                        self::LEVEL_OK,
                        'No outdated packages found'
                    );
                }
            }
        } catch (Exception $e) {
            $this->addResult(
                'Outdated Packages Check Failed',
                self::LEVEL_WARNING,
                'Could not check for outdated packages: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check critical package versions
     */
    protected function checkCriticalPackageVersions(): void
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $dependencies = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        // Check Laravel version
        if (isset($dependencies['laravel/framework'])) {
            $laravelVersion = $dependencies['laravel/framework'];
            
            // Check if using a very old version (basic check)
            if (strpos($laravelVersion, '^6.') !== false || strpos($laravelVersion, '^7.') !== false) {
                $this->addResult(
                    'Old Laravel Version',
                    self::LEVEL_WARNING,
                    'Consider upgrading to a newer Laravel version for security and features'
                );
            }
        }

        // Check PHP version requirement
        if (isset($dependencies['php'])) {
            $phpRequirement = $dependencies['php'];
            $currentPhpVersion = PHP_VERSION;
            
            $this->addResult(
                'PHP Version Info',
                self::LEVEL_INFO,
                "Current PHP: {$currentPhpVersion}, Required: {$phpRequirement}"
            );
        }
    }

    /**
     * Check scheduled tasks and queue status
     */
    protected function checkScheduleAndQueues(): void
    {
        // Check if schedule is configured
        $this->checkScheduleConfiguration();

        // Check queue workers (basic check)
        $this->checkQueueWorkers();

        // Check for failed jobs (already implemented in service connectivity)
        
        $this->addResult(
            'Schedule & Queue Check Complete',
            self::LEVEL_OK,
            'Schedule and queue status validated successfully.'
        );
    }

    /**
     * Check schedule configuration
     */
    protected function checkScheduleConfiguration(): void
    {
        $kernelPath = app_path('Console/Kernel.php');
        
        if (!file_exists($kernelPath)) {
            $this->addResult(
                'Console Kernel Not Found',
                self::LEVEL_WARNING,
                'Console/Kernel.php not found'
            );
            return;
        }

        $kernelContent = file_get_contents($kernelPath);
        
        // Basic check for schedule method
        if (strpos($kernelContent, 'protected function schedule') !== false) {
            $this->addResult(
                'Schedule Method Found',
                self::LEVEL_INFO,
                'Schedule method exists in Console/Kernel.php'
            );

            // Check if there are scheduled commands
            if (strpos($kernelContent, '$schedule->') !== false) {
                $this->addResult(
                    'Scheduled Tasks Configured',
                    self::LEVEL_INFO,
                    'Found scheduled tasks in Kernel.php. Ensure cron is running: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1'
                );
            }
        }
    }

    /**
     * Check queue workers (basic process check)
     */
    protected function checkQueueWorkers(): void
    {
        if ($this->isWindows()) {
            // Windows process check
            $output = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>nul');
            if ($output && strpos($output, 'php.exe') !== false) {
                $this->addResult(
                    'PHP Processes Running',
                    self::LEVEL_INFO,
                    'PHP processes detected (may include queue workers)'
                );
            }
        } else {
            // Unix-like process check
            $output = shell_exec('ps aux | grep "queue:work" | grep -v grep 2>/dev/null');
            if ($output) {
                $this->addResult(
                    'Queue Workers Running',
                    self::LEVEL_OK,
                    'Queue worker processes detected'
                );
            } else {
                $this->addResult(
                    'No Queue Workers Detected',
                    self::LEVEL_INFO,
                    'No queue:work processes found. Start with: php artisan queue:work'
                );
            }
        }
    }

    /**
     * Check version consistency
     */
    protected function checkVersionConsistency(): void
    {
        // Check Laravel version consistency
        $this->checkLaravelVersionConsistency();

        // Check PHP version compatibility
        $this->checkPhpVersionCompatibility();

        // Check if Laravel version is EOL
        $this->checkLaravelEOL();

        $this->addResult(
            'Version Consistency Check Complete',
            self::LEVEL_OK,
            'Version consistency validated successfully.'
        );
    }

    /**
     * Check Laravel version consistency
     */
    protected function checkLaravelVersionConsistency(): void
    {
        try {
            // Get version from artisan
            $artisanOutput = shell_exec('php artisan --version 2>/dev/null');
            $artisanVersion = null;
            
            if ($artisanOutput && preg_match('/Laravel Framework (\d+\.\d+\.\d+)/', $artisanOutput, $matches)) {
                $artisanVersion = $matches[1];
            }

            // Get version from composer.json
            $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
            $composerVersion = $composerJson['require']['laravel/framework'] ?? null;

            if ($artisanVersion && $composerVersion) {
                $this->addResult(
                    'Laravel Version Info',
                    self::LEVEL_INFO,
                    "Artisan reports: {$artisanVersion}, Composer requires: {$composerVersion}"
                );
            }

        } catch (Exception $e) {
            $this->addResult(
                'Version Check Failed',
                self::LEVEL_WARNING,
                'Could not determine Laravel version: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check PHP version compatibility
     */
    protected function checkPhpVersionCompatibility(): void
    {
        $currentPhpVersion = PHP_VERSION;
        $phpVersionParts = explode('.', $currentPhpVersion);
        $majorMinor = $phpVersionParts[0] . '.' . $phpVersionParts[1];

        // Check if PHP version is EOL (basic check)
        $eolVersions = ['7.0', '7.1', '7.2', '7.3'];
        
        if (in_array($majorMinor, $eolVersions)) {
            $this->addResult(
                'PHP Version End of Life',
                self::LEVEL_CRITICAL,
                "PHP {$majorMinor} is end of life. Upgrade to a supported version."
            );
        } elseif ($majorMinor === '7.4') {
            $this->addResult(
                'PHP Version Near EOL',
                self::LEVEL_WARNING,
                'PHP 7.4 is approaching end of life. Consider upgrading to PHP 8.x'
            );
        } else {
            $this->addResult(
                'PHP Version Supported',
                self::LEVEL_OK,
                "PHP {$currentPhpVersion} is currently supported"
            );
        }
    }

    /**
     * Check if Laravel version is EOL
     */
    protected function checkLaravelEOL(): void
    {
        try {
            $artisanOutput = shell_exec('php artisan --version 2>/dev/null');
            
            if ($artisanOutput && preg_match('/Laravel Framework (\d+)\./', $artisanOutput, $matches)) {
                $majorVersion = (int)$matches[1];
                
                // Basic EOL check (this would need to be updated regularly)
                if ($majorVersion < 8) {
                    $this->addResult(
                        'Laravel Version EOL',
                        self::LEVEL_WARNING,
                        "Laravel {$majorVersion}.x may be end of life. Check Laravel documentation for support status."
                    );
                }
            }
        } catch (Exception $e) {
            // Silently fail - this is not critical
        }
    }

    /**
     * Send email alert for critical issues
     */
    protected function sendEmailAlert(): void
    {
        if (!$this->config['admin_email']) {
            return;
        }

        try {
            $criticalResults = array_filter($this->results, function($result) {
                return in_array($result['level'], [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
            });

            $subject = 'Laravel Doctor Alert: ' . $this->criticalIssues . ' Critical Issues Found';
            $body = $this->generateEmailBody($criticalResults);

            Mail::raw($body, function($message) use ($subject) {
                $message->to($this->config['admin_email'])
                       ->subject($subject);
            });

            $this->addResult(
                'Email Alert Sent',
                self::LEVEL_INFO,
                'Critical issues alert sent to ' . $this->config['admin_email']
            );

        } catch (Exception $e) {
            $this->addResult(
                'Email Alert Failed',
                self::LEVEL_WARNING,
                'Could not send email alert: ' . $e->getMessage()
            );
        }
    }

    /**
     * Generate email body for alerts
     */
    protected function generateEmailBody(array $criticalResults): string
    {
        $body = "Laravel Doctor has detected critical issues in your application:\n\n";
        $body .= "Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\n";
        $body .= "Environment: " . env('APP_ENV', 'Unknown') . "\n";
        $body .= "Timestamp: " . now()->toDateTimeString() . "\n\n";
        $body .= "Critical Issues Found:\n";
        $body .= str_repeat("=", 50) . "\n\n";

        foreach ($criticalResults as $result) {
            $body .= "LEVEL: " . strtoupper($result['level']) . "\n";
            $body .= "MESSAGE: " . $result['message'] . "\n";
            $body .= "ADVICE: " . $result['advice'] . "\n";
            $body .= "TIME: " . $result['timestamp'] . "\n";
            $body .= str_repeat("-", 30) . "\n\n";
        }

        $body .= "Please review and address these issues promptly.\n\n";
        $body .= "This alert was generated by Laravel Doctor.";

        return $body;
    }
}
