<?php

/**
 * Laravel Doctor - Web Interface Example
 * 
 * This example shows how to create a web interface for Laravel Doctor
 * that can be accessed via a browser.
 */

// This would typically be in a Laravel route or controller

use LaravelDoctor\LaravelDoctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Example Controller for Laravel Doctor Web Interface
 */
class LaravelDoctorController
{
    /**
     * Display the Laravel Doctor dashboard
     */
    public function index(Request $request)
    {
        // Check if user has permission (implement your own authorization)
        if (!$this->canAccessDoctor($request)) {
            abort(403, 'Unauthorized access to Laravel Doctor');
        }

        $format = $request->get('format', 'html');
        $export = $request->get('export', false);

        // Configure Laravel Doctor
        $config = [
            'send_email_alerts' => false, // Don't send emails from web interface
            'export_format' => $format,
        ];

        $doctor = new LaravelDoctor($config);
        $results = $doctor->diagnose();
        $summary = $doctor->getSummary();

        // Handle export requests
        if ($export) {
            return $this->handleExport($doctor, $format);
        }

        // Return view with results
        return view('laravel-doctor.dashboard', [
            'results' => $results,
            'summary' => $summary,
            'healthScore' => $this->calculateHealthScore($summary),
            'lastRun' => now(),
        ]);
    }

    /**
     * API endpoint for JSON results
     */
    public function api(Request $request)
    {
        if (!$this->canAccessDoctor($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doctor = new LaravelDoctor();
        $results = $doctor->diagnose();
        $summary = $doctor->getSummary();

        return response()->json([
            'results' => $results,
            'summary' => $summary,
            'health_score' => $this->calculateHealthScore($summary),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Handle export requests
     */
    protected function handleExport(LaravelDoctor $doctor, string $format)
    {
        $content = $doctor->getResults($format);
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
    }

    /**
     * Calculate health score
     */
    protected function calculateHealthScore(array $summary): int
    {
        $totalChecks = $summary['total_checks'];
        $criticalIssues = $summary['critical_issues'];
        $errors = $summary['levels']['error'] ?? 0;
        $warnings = $summary['levels']['warning'] ?? 0;

        $score = 100;
        $score -= ($criticalIssues * 20);
        $score -= ($errors * 10);
        $score -= ($warnings * 5);

        return max(0, min(100, $score));
    }

    /**
     * Check if user can access Laravel Doctor
     * Implement your own authorization logic here
     */
    protected function canAccessDoctor(Request $request): bool
    {
        // Example authorization checks:
        
        // 1. Check if user is authenticated and has admin role
        // return auth()->check() && auth()->user()->hasRole('admin');
        
        // 2. Check if request is from allowed IP addresses
        // $allowedIps = ['127.0.0.1', '::1'];
        // return in_array($request->ip(), $allowedIps);
        
        // 3. Check environment (only allow in development/staging)
        // return in_array(app()->environment(), ['local', 'staging']);
        
        // 4. Check for specific header or token
        // return $request->header('X-Doctor-Token') === config('app.doctor_token');

        // For this example, allow access in non-production environments
        return !app()->environment('production');
    }
}

/**
 * Example Blade Template (resources/views/laravel-doctor/dashboard.blade.php)
 */
$bladeTemplate = '
@extends("layouts.app")

@section("title", "Laravel Doctor Dashboard")

@section("content")
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>🩺 Laravel Doctor Dashboard</h1>
                <div>
                    <a href="{{ url()->current() }}?export=1&format=json" class="btn btn-outline-primary">
                        📄 Export JSON
                    </a>
                    <a href="{{ url()->current() }}?export=1&format=html" class="btn btn-outline-primary">
                        📊 Export HTML
                    </a>
                    <button onclick="location.reload()" class="btn btn-primary">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Health Score Card -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h2 class="card-title">{{ $healthScore }}/100</h2>
                            <p class="card-text">Health Score</p>
                            <div class="progress">
                                <div class="progress-bar 
                                    @if($healthScore >= 90) bg-success
                                    @elseif($healthScore >= 70) bg-warning
                                    @else bg-danger
                                    @endif" 
                                    style="width: {{ $healthScore }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4>{{ $summary["total_checks"] }}</h4>
                                    <small>Total Checks</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-danger text-white">
                                <div class="card-body">
                                    <h4>{{ $summary["critical_issues"] }}</h4>
                                    <small>Critical</small>
                                </div>
                            </div>
                        </div>
                        @foreach($summary["levels"] as $level => $count)
                            @if($count > 0)
                            <div class="col-md-2">
                                <div class="card text-center 
                                    @if($level === "ok") bg-success text-white
                                    @elseif($level === "warning") bg-warning
                                    @elseif($level === "error") bg-danger text-white
                                    @else bg-info text-white
                                    @endif">
                                    <div class="card-body">
                                        <h4>{{ $count }}</h4>
                                        <small>{{ ucfirst($level) }}</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-header">
                    <h5>Diagnostic Results</h5>
                    <small class="text-muted">Last run: {{ $lastRun->format("Y-m-d H:i:s") }}</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Message</th>
                                    <th>Advice</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                <tr class="
                                    @if($result["level"] === "critical") table-danger
                                    @elseif($result["level"] === "error") table-danger
                                    @elseif($result["level"] === "warning") table-warning
                                    @elseif($result["level"] === "ok") table-success
                                    @else table-info
                                    @endif">
                                    <td>
                                        <span class="badge 
                                            @if($result["level"] === "critical") badge-danger
                                            @elseif($result["level"] === "error") badge-danger
                                            @elseif($result["level"] === "warning") badge-warning
                                            @elseif($result["level"] === "ok") badge-success
                                            @else badge-info
                                            @endif">
                                            {{ strtoupper($result["level"]) }}
                                        </span>
                                    </td>
                                    <td>{{ $result["message"] }}</td>
                                    <td>{{ $result["advice"] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($result["timestamp"])->format("H:i:s") }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);

// Add click handlers for result rows
document.querySelectorAll("tbody tr").forEach(function(row) {
    row.style.cursor = "pointer";
    row.addEventListener("click", function() {
        // You could show more details in a modal here
        console.log("Row clicked:", this);
    });
});
</script>
@endsection
';

/**
 * Example Routes (routes/web.php)
 */
$routeExample = '
// Laravel Doctor Web Interface Routes
Route::middleware(["auth", "admin"])->group(function () {
    Route::get("/doctor", [LaravelDoctorController::class, "index"])->name("doctor.dashboard");
    Route::get("/doctor/api", [LaravelDoctorController::class, "api"])->name("doctor.api");
});

// Or for development/staging only:
if (!app()->environment("production")) {
    Route::get("/doctor", [LaravelDoctorController::class, "index"]);
    Route::get("/doctor/api", [LaravelDoctorController::class, "api"]);
}
';

echo "Web interface example files created!\n";
echo "See the code above for implementation details.\n";
