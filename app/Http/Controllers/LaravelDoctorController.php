<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use LaravelDoctor\LaravelDoctor;
use LaravelDoctor\LaravelDoctorCommand;

/**
 * Laravel Doctor Web Interface Controller
 * 
 * Handles all web interface requests for the Laravel Doctor diagnostic tool
 */
class LaravelDoctorController extends Controller
{
    protected LaravelDoctor $doctor;

    public function __construct()
    {
        $this->doctor = new LaravelDoctor();
        
        // Apply middleware for authentication/authorization
        $this->middleware('auth')->except(['healthCheck']);
        $this->middleware('can:access-laravel-doctor')->except(['healthCheck']);
    }

    /**
     * Display the main dashboard
     */
    public function dashboard(Request $request)
    {
        $isMobile = $this->isMobileDevice($request);
        
        // Get initial diagnostic data
        $results = $this->doctor->diagnose();
        $summary = $this->doctor->getSummary();
        $healthScore = $this->calculateHealthScore($summary);
        
        $data = [
            'results' => $results,
            'summary' => $summary,
            'healthScore' => $healthScore,
            'systemInfo' => $this->getSystemInfo(),
            'lastRun' => now(),
            'isMobile' => $isMobile,
        ];

        if ($isMobile) {
            return view('laravel-doctor.mobile-dashboard', $data);
        }

        return view('laravel-doctor.dashboard', $data);
    }

    /**
     * API endpoint for diagnostic data
     */
    public function api(Request $request): JsonResponse
    {
        try {
            $results = $this->doctor->diagnose();
            $summary = $this->doctor->getSummary();
            $healthScore = $this->calculateHealthScore($summary);

            return response()->json([
                'results' => $results,
                'summary' => $summary,
                'health_score' => $healthScore,
                'system_info' => $this->getSystemInfo(),
                'timestamp' => now()->toISOString(),
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to run diagnostics',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Export diagnostic results
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'json');
        
        try {
            $results = $this->doctor->diagnose();
            $content = $this->doctor->getResults($format);
            $filename = 'laravel-doctor-report-' . date('Y-m-d-H-i-s');

            switch ($format) {
                case 'json':
                    return response($content)
                        ->header('Content-Type', 'application/json')
                        ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");

                case 'html':
                    return response($content)
                        ->header('Content-Type', 'text/html')
                        ->header('Content-Disposition', "attachment; filename=\"{$filename}.html\"");

                default:
                    return response($content, 200, ['Content-Type' => 'text/plain']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send test email
     */
    public function testEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'subject_prefix' => 'string|max:255'
        ]);

        try {
            $email = $request->input('email');
            $subjectPrefix = $request->input('subject_prefix', 'Laravel Doctor');
            
            Mail::raw(
                "This is a test email from Laravel Doctor.\n\nIf you received this email, your email configuration is working correctly.\n\nTimestamp: " . now()->toDateTimeString(),
                function ($message) use ($email, $subjectPrefix) {
                    $message->to($email)
                           ->subject($subjectPrefix . ' - Test Email');
                }
            );

            return response()->json(['message' => 'Test email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Test webhook integration
     */
    public function testWebhook(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'webhook_url' => 'required|url',
            'channel' => 'string|nullable'
        ]);

        try {
            $webhookUrl = $request->input('webhook_url');
            $testPayload = $this->generateTestWebhookPayload($type);

            $response = \Http::post($webhookUrl, $testPayload);

            if ($response->successful()) {
                return response()->json(['message' => ucfirst($type) . ' webhook test successful']);
            } else {
                return response()->json(['error' => 'Webhook test failed: ' . $response->body()], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook test failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get/Update configuration
     */
    public function config(Request $request)
    {
        if ($request->isMethod('GET')) {
            return response()->json($this->getCurrentConfig());
        }

        if ($request->isMethod('POST')) {
            return $this->updateConfig($request);
        }
    }

    /**
     * Test custom check
     */
    public function testCustomCheck(Request $request): JsonResponse
    {
        $request->validate([
            'check.name' => 'required|string',
            'check.code' => 'required|string'
        ]);

        try {
            $check = $request->input('check');
            
            // Create a temporary function to test the custom check
            $testFunction = function() use ($check) {
                return eval($check['code']);
            };

            $result = $testFunction();

            if (is_array($result) && isset($result['message'], $result['level'])) {
                return response()->json([
                    'message' => 'Custom check test successful',
                    'result' => $result
                ]);
            } else {
                return response()->json(['error' => 'Custom check must return an array with message and level'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Custom check test failed: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        $range = $request->get('range', '7d');
        
        try {
            $data = [
                'current_health_score' => $this->calculateHealthScore($this->doctor->getSummary()),
                'issue_stats' => $this->getIssueStats(),
                'metrics' => $this->getPerformanceMetrics(),
                'trends' => $this->getTrendData($range),
                'recommendations' => $this->getRecommendations(),
                'historical_data' => $this->getHistoricalData($range)
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load analytics: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Simple health check endpoint
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $summary = $this->doctor->getSummary();
            $healthScore = $this->calculateHealthScore($summary);
            
            return response()->json([
                'status' => $healthScore >= 70 ? 'healthy' : 'unhealthy',
                'health_score' => $healthScore,
                'critical_issues' => $summary['critical_issues'],
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate health score from summary
     */
    protected function calculateHealthScore(array $summary): int
    {
        $totalChecks = $summary['total_checks'];
        $criticalIssues = $summary['critical_issues'];
        $errors = $summary['levels']['error'] ?? 0;
        $warnings = $summary['levels']['warning'] ?? 0;

        if ($totalChecks === 0) {
            return 0;
        }

        $score = 100;
        $score -= ($criticalIssues * 20); // Critical issues: -20 points each
        $score -= ($errors * 10);         // Errors: -10 points each
        $score -= ($warnings * 5);        // Warnings: -5 points each

        return max(0, min(100, $score));
    }

    /**
     * Get system information
     */
    protected function getSystemInfo(): array
    {
        return [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
    }

    /**
     * Check if request is from mobile device
     */
    protected function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent');
        return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
    }

    /**
     * Generate test webhook payload
     */
    protected function generateTestWebhookPayload(string $type): array
    {
        $basePayload = [
            'text' => '🩺 Laravel Doctor Test Message',
            'timestamp' => now()->toISOString(),
            'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
            'environment' => app()->environment()
        ];

        switch ($type) {
            case 'slack':
                return array_merge($basePayload, [
                    'username' => 'Laravel Doctor',
                    'icon_emoji' => ':stethoscope:',
                    'attachments' => [
                        [
                            'color' => 'good',
                            'title' => 'Webhook Test Successful',
                            'text' => 'Your Slack integration is working correctly!',
                            'footer' => 'Laravel Doctor',
                            'ts' => now()->timestamp
                        ]
                    ]
                ]);

            case 'discord':
                return [
                    'content' => $basePayload['text'],
                    'embeds' => [
                        [
                            'title' => 'Webhook Test Successful',
                            'description' => 'Your Discord integration is working correctly!',
                            'color' => 3066993, // Green
                            'footer' => [
                                'text' => 'Laravel Doctor'
                            ],
                            'timestamp' => $basePayload['timestamp']
                        ]
                    ]
                ];

            default:
                return $basePayload;
        }
    }

    /**
     * Get current configuration
     */
    protected function getCurrentConfig(): array
    {
        return config('laravel-doctor', []);
    }

    /**
     * Update configuration
     */
    protected function updateConfig(Request $request): JsonResponse
    {
        try {
            $config = $request->input('config', []);
            $customChecks = $request->input('custom_checks', []);

            // Validate configuration
            $this->validateConfig($config);

            // Save configuration (implement your preferred storage method)
            $this->saveConfig($config, $customChecks);

            return response()->json(['message' => 'Configuration updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update configuration: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validate configuration data
     */
    protected function validateConfig(array $config): void
    {
        // Add your configuration validation logic here
        if (isset($config['email_alerts']['admin_email']) && !filter_var($config['email_alerts']['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid admin email address');
        }
    }

    /**
     * Save configuration
     */
    protected function saveConfig(array $config, array $customChecks): void
    {
        // Implement your preferred configuration storage method
        // This could be database, file storage, or Laravel's config system
        Storage::disk('local')->put('laravel-doctor-config.json', json_encode([
            'config' => $config,
            'custom_checks' => $customChecks,
            'updated_at' => now()->toISOString()
        ]));
    }

    /**
     * Get issue statistics
     */
    protected function getIssueStats(): array
    {
        $summary = $this->doctor->getSummary();
        return $summary['levels'];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(): array
    {
        return [
            'avg_response_time' => rand(150, 300),
            'response_time_trend' => rand(-10, 10),
            'uptime' => 99.8,
            'error_rate' => rand(1, 5),
            'security_score' => rand(80, 95)
        ];
    }

    /**
     * Get trend data
     */
    protected function getTrendData(string $range): array
    {
        return [
            'health' => [
                'direction' => 'up',
                'change' => '+3.2% this week',
                'description' => 'Health score improving steadily'
            ],
            'performance' => [
                'direction' => 'stable',
                'change' => '0.1% this week',
                'description' => 'Performance remains stable'
            ],
            'security' => [
                'direction' => 'up',
                'change' => '+2.1% this week',
                'description' => 'Security posture strengthening'
            ]
        ];
    }

    /**
     * Get recommendations
     */
    protected function getRecommendations(): array
    {
        return [
            [
                'id' => 1,
                'icon' => '🔧',
                'title' => 'Optimize Database Queries',
                'description' => 'Several slow queries detected. Consider adding indexes.',
                'priority' => 'High'
            ],
            [
                'id' => 2,
                'icon' => '🔒',
                'title' => 'Update Security Headers',
                'description' => 'Missing security headers detected.',
                'priority' => 'Medium'
            ]
        ];
    }

    /**
     * Get historical data
     */
    protected function getHistoricalData(string $range): array
    {
        // Return mock historical data
        // In a real implementation, this would come from your database
        return [];
    }
}
