<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaravelDoctorController;

/*
|--------------------------------------------------------------------------
| Laravel Doctor Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Laravel Doctor web interface.
| These routes provide access to the diagnostic dashboard, API endpoints,
| and configuration management.
|
*/

// Public health check endpoint (no authentication required)
Route::get('/health', [LaravelDoctorController::class, 'healthCheck'])
    ->name('doctor.health');

// Protected routes (require authentication)
Route::middleware(['auth', 'can:access-laravel-doctor'])->group(function () {
    
    // Main dashboard
    Route::get('/doctor', [LaravelDoctorController::class, 'dashboard'])
        ->name('doctor.dashboard');
    
    // API endpoints
    Route::prefix('doctor')->name('doctor.')->group(function () {
        
        // Diagnostic API
        Route::get('/api', [LaravelDoctorController::class, 'api'])
            ->name('api');
        
        // Export functionality
        Route::get('/export', [LaravelDoctorController::class, 'export'])
            ->name('export');
        
        // Configuration management
        Route::match(['GET', 'POST'], '/config', [LaravelDoctorController::class, 'config'])
            ->name('config');
        
        // Testing endpoints
        Route::post('/test-email', [LaravelDoctorController::class, 'testEmail'])
            ->name('test-email');
        
        Route::post('/test-webhook/{type}', [LaravelDoctorController::class, 'testWebhook'])
            ->name('test-webhook')
            ->where('type', 'slack|discord');
        
        Route::post('/test-custom-check', [LaravelDoctorController::class, 'testCustomCheck'])
            ->name('test-custom-check');
        
        // Analytics and reporting
        Route::get('/analytics', [LaravelDoctorController::class, 'analytics'])
            ->name('analytics');
        
        // Real-time WebSocket endpoint (if using Laravel WebSockets)
        Route::get('/ws', function () {
            return response()->json([
                'message' => 'WebSocket endpoint - implement with Laravel WebSockets or Pusher'
            ]);
        })->name('websocket');
        
    });
});

// Development/staging only routes
if (!app()->environment('production')) {
    Route::prefix('doctor-dev')->name('doctor.dev.')->group(function () {
        
        // Demo data endpoints
        Route::get('/demo-data', function () {
            return response()->json([
                'results' => [
                    [
                        'message' => 'Demo Critical Issue',
                        'level' => 'critical',
                        'advice' => 'This is a demo critical issue for testing',
                        'details' => ['demo' => true],
                        'timestamp' => now()->toISOString()
                    ],
                    [
                        'message' => 'Demo Warning',
                        'level' => 'warning',
                        'advice' => 'This is a demo warning for testing',
                        'details' => ['demo' => true],
                        'timestamp' => now()->toISOString()
                    ],
                    [
                        'message' => 'Demo Success Check',
                        'level' => 'ok',
                        'advice' => 'This check passed successfully',
                        'details' => ['demo' => true],
                        'timestamp' => now()->toISOString()
                    ]
                ],
                'summary' => [
                    'total_checks' => 25,
                    'critical_issues' => 1,
                    'levels' => [
                        'critical' => 1,
                        'error' => 2,
                        'warning' => 5,
                        'info' => 3,
                        'ok' => 14
                    ]
                ]
            ]);
        })->name('demo-data');
        
        // Test notification endpoint
        Route::post('/test-notification', function () {
            return response()->json([
                'message' => 'Test notification sent',
                'notification' => [
                    'title' => 'Test Notification',
                    'message' => 'This is a test notification from Laravel Doctor',
                    'type' => 'info',
                    'level' => 'info',
                    'timestamp' => now()->toISOString()
                ]
            ]);
        })->name('test-notification');
        
        // Reset demo data
        Route::post('/reset-demo', function () {
            // Clear any demo data or reset to defaults
            return response()->json(['message' => 'Demo data reset']);
        })->name('reset-demo');
        
    });
}

// API routes for mobile app or external integrations
Route::prefix('api/doctor')->name('api.doctor.')->middleware(['auth:sanctum'])->group(function () {
    
    // Mobile-optimized endpoints
    Route::get('/mobile/dashboard', function () {
        $controller = new LaravelDoctorController();
        $data = $controller->api(request());
        
        // Add mobile-specific optimizations
        $response = $data->getData(true);
        $response['mobile_optimized'] = true;
        $response['reduced_payload'] = true;
        
        return response()->json($response);
    })->name('mobile.dashboard');
    
    // Lightweight health check for mobile
    Route::get('/mobile/health', function () {
        $controller = new LaravelDoctorController();
        return $controller->healthCheck();
    })->name('mobile.health');
    
    // Quick stats for widgets
    Route::get('/stats', function () {
        $doctor = new \LaravelDoctor\LaravelDoctor();
        $summary = $doctor->getSummary();
        
        return response()->json([
            'health_score' => max(0, 100 - ($summary['critical_issues'] * 20)),
            'critical_issues' => $summary['critical_issues'],
            'total_checks' => $summary['total_checks'],
            'status' => $summary['critical_issues'] > 0 ? 'warning' : 'healthy',
            'last_check' => now()->toISOString()
        ]);
    })->name('stats');
    
});

// Webhook endpoints for external services
Route::prefix('webhooks/doctor')->name('webhooks.doctor.')->group(function () {
    
    // GitHub webhook for triggering checks on deployment
    Route::post('/github', function () {
        // Implement GitHub webhook handler
        return response()->json(['message' => 'GitHub webhook received']);
    })->name('github');
    
    // Generic webhook endpoint
    Route::post('/trigger', function () {
        // Trigger diagnostic check via webhook
        $doctor = new \LaravelDoctor\LaravelDoctor();
        $results = $doctor->diagnose();
        
        return response()->json([
            'message' => 'Diagnostics triggered via webhook',
            'summary' => $doctor->getSummary(),
            'timestamp' => now()->toISOString()
        ]);
    })->name('trigger');
    
});

// CLI-accessible routes (for monitoring systems)
Route::prefix('cli/doctor')->name('cli.doctor.')->group(function () {
    
    // Simple status endpoint for monitoring
    Route::get('/status', function () {
        try {
            $doctor = new \LaravelDoctor\LaravelDoctor();
            $summary = $doctor->getSummary();
            $healthScore = max(0, 100 - ($summary['critical_issues'] * 20));
            
            $status = 'healthy';
            $httpCode = 200;
            
            if ($summary['critical_issues'] > 0) {
                $status = 'critical';
                $httpCode = 503; // Service Unavailable
            } elseif (($summary['levels']['error'] ?? 0) > 0) {
                $status = 'warning';
                $httpCode = 200; // OK but with warnings
            }
            
            return response()->json([
                'status' => $status,
                'health_score' => $healthScore,
                'critical_issues' => $summary['critical_issues'],
                'errors' => $summary['levels']['error'] ?? 0,
                'warnings' => $summary['levels']['warning'] ?? 0,
                'timestamp' => now()->toISOString(),
                'uptime' => 'healthy' // You can implement actual uptime calculation
            ], $httpCode);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diagnostic check failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    })->name('status');
    
    // Prometheus metrics endpoint
    Route::get('/metrics', function () {
        $doctor = new \LaravelDoctor\LaravelDoctor();
        $summary = $doctor->getSummary();
        $healthScore = max(0, 100 - ($summary['critical_issues'] * 20));
        
        $metrics = [
            "# HELP laravel_doctor_health_score Application health score (0-100)",
            "# TYPE laravel_doctor_health_score gauge",
            "laravel_doctor_health_score {$healthScore}",
            "",
            "# HELP laravel_doctor_critical_issues Number of critical issues",
            "# TYPE laravel_doctor_critical_issues gauge", 
            "laravel_doctor_critical_issues {$summary['critical_issues']}",
            "",
            "# HELP laravel_doctor_total_checks Total number of diagnostic checks",
            "# TYPE laravel_doctor_total_checks gauge",
            "laravel_doctor_total_checks {$summary['total_checks']}",
            ""
        ];
        
        foreach ($summary['levels'] as $level => $count) {
            $metrics[] = "# HELP laravel_doctor_issues_{$level} Number of {$level} level issues";
            $metrics[] = "# TYPE laravel_doctor_issues_{$level} gauge";
            $metrics[] = "laravel_doctor_issues_{$level} {$count}";
            $metrics[] = "";
        }
        
        return response(implode("\n", $metrics))
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    })->name('metrics');
    
});

// Fallback route for SPA behavior
Route::get('/doctor/{any}', [LaravelDoctorController::class, 'dashboard'])
    ->where('any', '.*')
    ->name('doctor.spa');
