<?php

namespace LaravelDoctor\Tests;

use LaravelDoctor\LaravelDoctor;
use PHPUnit\Framework\TestCase;

/**
 * Laravel Doctor Test Suite
 * 
 * Basic tests to ensure Laravel Doctor functionality works correctly
 */
class LaravelDoctorTest extends TestCase
{
    protected LaravelDoctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = new LaravelDoctor([
            'send_email_alerts' => false, // Disable for testing
        ]);
    }

    public function testDoctorCanBeInstantiated(): void
    {
        $this->assertInstanceOf(LaravelDoctor::class, $this->doctor);
    }

    public function testDoctorCanRunDiagnostics(): void
    {
        $results = $this->doctor->diagnose();
        
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        
        // Check that each result has required fields
        foreach ($results as $result) {
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('level', $result);
            $this->assertArrayHasKey('advice', $result);
            $this->assertArrayHasKey('timestamp', $result);
        }
    }

    public function testDoctorCanGenerateSummary(): void
    {
        $this->doctor->diagnose();
        $summary = $this->doctor->getSummary();
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_checks', $summary);
        $this->assertArrayHasKey('critical_issues', $summary);
        $this->assertArrayHasKey('levels', $summary);
        
        $this->assertIsInt($summary['total_checks']);
        $this->assertIsInt($summary['critical_issues']);
        $this->assertIsArray($summary['levels']);
    }

    public function testDoctorCanExportResults(): void
    {
        $this->doctor->diagnose();
        
        // Test JSON export
        $jsonResults = $this->doctor->getResults('json');
        $this->assertIsString($jsonResults);
        $this->assertJson($jsonResults);
        
        // Test HTML export
        $htmlResults = $this->doctor->getResults('html');
        $this->assertIsString($htmlResults);
        $this->assertStringContainsString('<html>', $htmlResults);
        $this->assertStringContainsString('Laravel Doctor Report', $htmlResults);
        
        // Test array export (default)
        $arrayResults = $this->doctor->getResults('array');
        $this->assertIsArray($arrayResults);
    }

    public function testDoctorValidatesResultLevels(): void
    {
        $this->doctor->diagnose();
        $results = $this->doctor->getResults('array');
        
        $validLevels = ['ok', 'info', 'warning', 'error', 'critical'];
        
        foreach ($results as $result) {
            $this->assertContains($result['level'], $validLevels);
        }
    }

    public function testDoctorCanHandleCustomChecks(): void
    {
        $customDoctor = new LaravelDoctor([
            'send_email_alerts' => false,
            'custom_checks' => [
                function($doctor) {
                    return [
                        'message' => 'Custom Test Check',
                        'level' => 'ok',
                        'advice' => 'This is a test custom check',
                        'details' => ['test' => true]
                    ];
                }
            ]
        ]);
        
        $results = $customDoctor->diagnose();
        
        // Find our custom check in the results
        $customCheckFound = false;
        foreach ($results as $result) {
            if ($result['message'] === 'Custom Test Check') {
                $customCheckFound = true;
                $this->assertEquals('ok', $result['level']);
                $this->assertEquals('This is a test custom check', $result['advice']);
                break;
            }
        }
        
        $this->assertTrue($customCheckFound, 'Custom check was not found in results');
    }

    public function testDoctorHandlesExceptionsGracefully(): void
    {
        $faultyDoctor = new LaravelDoctor([
            'send_email_alerts' => false,
            'custom_checks' => [
                function($doctor) {
                    throw new \Exception('Test exception');
                }
            ]
        ]);
        
        // Should not throw exception, but handle it gracefully
        $results = $faultyDoctor->diagnose();
        $this->assertIsArray($results);
        
        // Should contain an error about the custom check failure
        $errorFound = false;
        foreach ($results as $result) {
            if (strpos($result['message'], 'Custom Check Error') !== false) {
                $errorFound = true;
                break;
            }
        }
        
        $this->assertTrue($errorFound, 'Custom check error was not handled gracefully');
    }

    public function testDoctorTimestampsAreValid(): void
    {
        $this->doctor->diagnose();
        $results = $this->doctor->getResults('array');
        
        foreach ($results as $result) {
            $this->assertArrayHasKey('timestamp', $result);
            
            // Validate timestamp format (ISO 8601)
            $timestamp = $result['timestamp'];
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
                $timestamp,
                "Invalid timestamp format: {$timestamp}"
            );
        }
    }

    public function testDoctorConfigurationIsApplied(): void
    {
        $config = [
            'send_email_alerts' => true,
            'admin_email' => 'test@example.com',
            'export_format' => 'json',
            'colorized_output' => false,
        ];
        
        $configuredDoctor = new LaravelDoctor($config);
        
        // We can't directly test private properties, but we can test behavior
        // The configuration should be applied without errors
        $this->assertInstanceOf(LaravelDoctor::class, $configuredDoctor);
        
        $results = $configuredDoctor->diagnose();
        $this->assertIsArray($results);
    }

    public function testDoctorLevelConstants(): void
    {
        $this->assertEquals('ok', LaravelDoctor::LEVEL_OK);
        $this->assertEquals('info', LaravelDoctor::LEVEL_INFO);
        $this->assertEquals('warning', LaravelDoctor::LEVEL_WARNING);
        $this->assertEquals('error', LaravelDoctor::LEVEL_ERROR);
        $this->assertEquals('critical', LaravelDoctor::LEVEL_CRITICAL);
    }

    public function testDoctorResultStructure(): void
    {
        $this->doctor->diagnose();
        $results = $this->doctor->getResults('array');
        
        $this->assertNotEmpty($results);
        
        foreach ($results as $result) {
            // Required fields
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('level', $result);
            $this->assertArrayHasKey('advice', $result);
            $this->assertArrayHasKey('details', $result);
            $this->assertArrayHasKey('timestamp', $result);
            
            // Field types
            $this->assertIsString($result['message']);
            $this->assertIsString($result['level']);
            $this->assertIsString($result['advice']);
            $this->assertIsArray($result['details']);
            $this->assertIsString($result['timestamp']);
            
            // Message should not be empty
            $this->assertNotEmpty($result['message']);
        }
    }

    public function testDoctorSummaryCalculation(): void
    {
        $this->doctor->diagnose();
        $results = $this->doctor->getResults('array');
        $summary = $this->doctor->getSummary();
        
        // Total checks should match result count
        $this->assertEquals(count($results), $summary['total_checks']);
        
        // Level counts should add up correctly
        $levelCounts = $summary['levels'];
        $totalFromLevels = array_sum($levelCounts);
        $this->assertEquals($summary['total_checks'], $totalFromLevels);
        
        // Critical issues should be sum of error and critical levels
        $expectedCritical = ($levelCounts['error'] ?? 0) + ($levelCounts['critical'] ?? 0);
        $this->assertEquals($expectedCritical, $summary['critical_issues']);
    }
}
