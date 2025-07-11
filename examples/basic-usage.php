<?php

/**
 * Laravel Doctor - Basic Usage Examples
 * 
 * This file demonstrates various ways to use Laravel Doctor
 * in your Laravel applications.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaravelDoctor\LaravelDoctor;

// Example 1: Basic Usage
echo "=== Basic Laravel Doctor Usage ===\n";

$doctor = new LaravelDoctor();
$results = $doctor->diagnose();

echo "Total checks performed: " . count($results) . "\n";
echo "Critical issues found: " . $doctor->getSummary()['critical_issues'] . "\n\n";

// Example 2: With Configuration
echo "=== Laravel Doctor with Configuration ===\n";

$doctor = new LaravelDoctor([
    'send_email_alerts' => false, // Disable for this example
    'export_format' => 'json',
    'colorized_output' => true,
]);

$results = $doctor->diagnose();
$summary = $doctor->getSummary();

echo "Configuration applied successfully!\n";
echo "Results summary:\n";
foreach ($summary['levels'] as $level => $count) {
    if ($count > 0) {
        echo "- {$level}: {$count}\n";
    }
}
echo "\n";

// Example 3: Export Results
echo "=== Exporting Results ===\n";

// Export as JSON
$jsonResults = $doctor->getResults('json');
file_put_contents(__DIR__ . '/doctor-report.json', $jsonResults);
echo "JSON report saved to: " . __DIR__ . "/doctor-report.json\n";

// Export as HTML
$htmlResults = $doctor->getResults('html');
file_put_contents(__DIR__ . '/doctor-report.html', $htmlResults);
echo "HTML report saved to: " . __DIR__ . "/doctor-report.html\n\n";

// Example 4: Filter Results by Level
echo "=== Filtering Results by Level ===\n";

$criticalIssues = array_filter($results, function($result) {
    return in_array($result['level'], ['error', 'critical']);
});

echo "Critical issues found: " . count($criticalIssues) . "\n";
foreach ($criticalIssues as $issue) {
    echo "- [{$issue['level']}] {$issue['message']}\n";
}
echo "\n";

// Example 5: Custom Check Integration
echo "=== Custom Check Example ===\n";

$customDoctor = new LaravelDoctor([
    'custom_checks' => [
        function($doctor) {
            // Example custom check
            $diskSpace = disk_free_space('/');
            $totalSpace = disk_total_space('/');
            $usagePercent = (($totalSpace - $diskSpace) / $totalSpace) * 100;
            
            if ($usagePercent > 90) {
                return [
                    'message' => 'Disk Space Critical',
                    'level' => 'critical',
                    'advice' => 'Free up disk space immediately',
                    'details' => ['usage_percent' => round($usagePercent, 2)]
                ];
            } elseif ($usagePercent > 80) {
                return [
                    'message' => 'Disk Space Warning',
                    'level' => 'warning',
                    'advice' => 'Consider freeing up disk space',
                    'details' => ['usage_percent' => round($usagePercent, 2)]
                ];
            } else {
                return [
                    'message' => 'Disk Space OK',
                    'level' => 'ok',
                    'advice' => 'Disk space usage is within normal limits',
                    'details' => ['usage_percent' => round($usagePercent, 2)]
                ];
            }
        }
    ]
]);

$customResults = $customDoctor->diagnose();
echo "Custom check completed with " . count($customResults) . " total checks\n\n";

// Example 6: Programmatic Result Processing
echo "=== Processing Results Programmatically ===\n";

$resultsByLevel = [];
foreach ($results as $result) {
    $level = $result['level'];
    if (!isset($resultsByLevel[$level])) {
        $resultsByLevel[$level] = [];
    }
    $resultsByLevel[$level][] = $result;
}

echo "Results grouped by level:\n";
foreach ($resultsByLevel as $level => $levelResults) {
    echo "- {$level}: " . count($levelResults) . " issues\n";
}
echo "\n";

// Example 7: Health Score Calculation
echo "=== Health Score Calculation ===\n";

function calculateHealthScore(array $summary): int {
    $totalChecks = $summary['total_checks'];
    $criticalIssues = $summary['critical_issues'];
    $errors = $summary['levels']['error'] ?? 0;
    $warnings = $summary['levels']['warning'] ?? 0;
    
    // Calculate score (0-100)
    $score = 100;
    $score -= ($criticalIssues * 20); // Critical issues: -20 points each
    $score -= ($errors * 10);         // Errors: -10 points each
    $score -= ($warnings * 5);        // Warnings: -5 points each
    
    return max(0, min(100, $score));
}

$healthScore = calculateHealthScore($summary);
echo "Application Health Score: {$healthScore}/100\n";

if ($healthScore >= 90) {
    echo "Status: Excellent ✅\n";
} elseif ($healthScore >= 70) {
    echo "Status: Good ⚠️\n";
} elseif ($healthScore >= 50) {
    echo "Status: Needs Attention ⚠️\n";
} else {
    echo "Status: Critical Issues ❌\n";
}

echo "\n=== Laravel Doctor Examples Complete ===\n";
