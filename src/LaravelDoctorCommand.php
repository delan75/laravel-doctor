<?php

namespace LaravelDoctor;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Laravel Doctor Artisan Command
 * 
 * Provides a command-line interface for running Laravel diagnostics
 * Usage: php artisan laravel:doctor
 */
class LaravelDoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laravel:doctor 
                            {--format=console : Output format (console, json, html)}
                            {--export= : Export results to file}
                            {--email : Send email alerts for critical issues}
                            {--no-color : Disable colorized output}';

    /**
     * The console command description.
     */
    protected $description = 'Run comprehensive Laravel application diagnostics';

    /**
     * Color codes for console output
     */
    protected array $colors = [
        'ok' => "\033[32m",      // Green
        'info' => "\033[36m",    // Cyan
        'warning' => "\033[33m", // Yellow
        'error' => "\033[31m",   // Red
        'critical' => "\033[35m", // Magenta
        'reset' => "\033[0m",    // Reset
        'bold' => "\033[1m",     // Bold
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🩺 Laravel Doctor - Starting Diagnostics...');
        $this->newLine();

        // Configure LaravelDoctor
        $config = [
            'send_email_alerts' => $this->option('email'),
            'colorized_output' => !$this->option('no-color'),
        ];

        // Run diagnostics
        $doctor = new LaravelDoctor($config);
        $results = $doctor->diagnose();

        // Display results based on format
        $format = $this->option('format');
        
        switch ($format) {
            case 'json':
                $this->outputJson($results);
                break;
            case 'html':
                $this->outputHtml($doctor);
                break;
            case 'console':
            default:
                $this->outputConsole($results, $doctor->getSummary());
                break;
        }

        // Export results if requested
        if ($exportPath = $this->option('export')) {
            $this->exportResults($doctor, $exportPath, $format);
        }

        // Return appropriate exit code
        $summary = $doctor->getSummary();
        return $summary['critical_issues'] > 0 ? 1 : 0;
    }

    /**
     * Output results to console with colors
     */
    protected function outputConsole(array $results, array $summary): void
    {
        // Display summary
        $this->displaySummary($summary);
        $this->newLine();

        // Display detailed results
        $this->info('📋 Detailed Results:');
        $this->line(str_repeat('=', 60));

        foreach ($results as $result) {
            $this->displayResult($result);
        }

        $this->newLine();
        $this->displayFinalSummary($summary);
    }

    /**
     * Display summary statistics
     */
    protected function displaySummary(array $summary): void
    {
        $this->info('📊 Summary:');
        $this->line(str_repeat('-', 40));

        $this->line("Total Checks: {$summary['total_checks']}");
        $this->line("Critical Issues: " . $this->colorize($summary['critical_issues'], 
            $summary['critical_issues'] > 0 ? 'critical' : 'ok'));

        foreach ($summary['levels'] as $level => $count) {
            if ($count > 0) {
                $this->line(ucfirst($level) . ": " . $this->colorize($count, $level));
            }
        }
    }

    /**
     * Display individual result
     */
    protected function displayResult(array $result): void
    {
        $level = $result['level'];
        $icon = $this->getLevelIcon($level);
        $coloredLevel = $this->colorize(strtoupper($level), $level);
        
        $this->line("{$icon} [{$coloredLevel}] {$result['message']}");
        
        if (!empty($result['advice'])) {
            $this->line("   💡 " . $result['advice']);
        }
        
        $this->line("   🕒 {$result['timestamp']}");
        $this->newLine();
    }

    /**
     * Display final summary
     */
    protected function displayFinalSummary(array $summary): void
    {
        if ($summary['critical_issues'] > 0) {
            $this->error('❌ Diagnostics completed with ' . $summary['critical_issues'] . ' critical issues found.');
            $this->warn('Please review and address the issues above.');
        } else {
            $this->info('✅ All diagnostics passed successfully!');
        }
    }

    /**
     * Get icon for result level
     */
    protected function getLevelIcon(string $level): string
    {
        return match($level) {
            'ok' => '✅',
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🚨',
            default => '•',
        };
    }

    /**
     * Colorize text based on level
     */
    protected function colorize(string $text, string $level): string
    {
        if ($this->option('no-color')) {
            return $text;
        }

        $color = $this->colors[$level] ?? $this->colors['reset'];
        return $color . $text . $this->colors['reset'];
    }

    /**
     * Output results as JSON
     */
    protected function outputJson(array $results): void
    {
        $this->line(json_encode($results, JSON_PRETTY_PRINT));
    }

    /**
     * Output results as HTML
     */
    protected function outputHtml(LaravelDoctor $doctor): void
    {
        $html = $doctor->getResults('html');
        $this->line($html);
    }

    /**
     * Export results to file
     */
    protected function exportResults(LaravelDoctor $doctor, string $path, string $format): void
    {
        try {
            $content = $doctor->getResults($format);
            
            // Ensure directory exists
            $directory = dirname($path);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($path, $content);
            
            $this->info("✅ Results exported to: {$path}");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to export results: " . $e->getMessage());
        }
    }
}
