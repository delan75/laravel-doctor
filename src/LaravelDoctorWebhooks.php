<?php

namespace LaravelDoctor;

use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Laravel Doctor Webhook Notifications
 * 
 * Handles sending notifications to external services like Slack, Discord, etc.
 */
trait LaravelDoctorWebhooks
{
    /**
     * Send webhook notifications for critical issues
     */
    protected function sendWebhookNotifications(): void
    {
        if ($this->criticalIssues === 0) {
            return;
        }

        $config = config('laravel-doctor.webhooks', []);

        // Send Slack notification
        if ($config['slack']['enabled'] ?? false) {
            $this->sendSlackNotification($config['slack']);
        }

        // Send Discord notification
        if ($config['discord']['enabled'] ?? false) {
            $this->sendDiscordNotification($config['discord']);
        }
    }

    /**
     * Send Slack notification
     */
    protected function sendSlackNotification(array $config): void
    {
        if (empty($config['webhook_url'])) {
            return;
        }

        try {
            $criticalResults = array_filter($this->results, function($result) {
                return in_array($result['level'], [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
            });

            $payload = [
                'channel' => $config['channel'] ?? '#general',
                'username' => $config['username'] ?? 'Laravel Doctor',
                'icon_emoji' => $config['icon_emoji'] ?? ':stethoscope:',
                'text' => $this->generateSlackMessage($criticalResults),
                'attachments' => $this->generateSlackAttachments($criticalResults),
            ];

            $response = Http::post($config['webhook_url'], $payload);

            if ($response->successful()) {
                $this->addResult(
                    'Slack Notification Sent',
                    self::LEVEL_INFO,
                    'Critical issues notification sent to Slack'
                );
            } else {
                $this->addResult(
                    'Slack Notification Failed',
                    self::LEVEL_WARNING,
                    'Failed to send Slack notification: ' . $response->body()
                );
            }

        } catch (Exception $e) {
            $this->addResult(
                'Slack Notification Error',
                self::LEVEL_WARNING,
                'Error sending Slack notification: ' . $e->getMessage()
            );
        }
    }

    /**
     * Generate Slack message
     */
    protected function generateSlackMessage(array $criticalResults): string
    {
        $serverName = $_SERVER['SERVER_NAME'] ?? 'Unknown Server';
        $environment = env('APP_ENV', 'Unknown');
        $issueCount = count($criticalResults);

        return "🚨 *Laravel Doctor Alert*\n" .
               "Server: `{$serverName}`\n" .
               "Environment: `{$environment}`\n" .
               "Critical Issues Found: `{$issueCount}`\n" .
               "Time: `" . now()->toDateTimeString() . "`";
    }

    /**
     * Generate Slack attachments
     */
    protected function generateSlackAttachments(array $criticalResults): array
    {
        $attachments = [];

        foreach (array_slice($criticalResults, 0, 5) as $result) { // Limit to 5 issues
            $color = $result['level'] === self::LEVEL_CRITICAL ? 'danger' : 'warning';
            
            $attachments[] = [
                'color' => $color,
                'title' => $result['message'],
                'text' => $result['advice'],
                'footer' => 'Laravel Doctor',
                'ts' => strtotime($result['timestamp']),
                'fields' => [
                    [
                        'title' => 'Level',
                        'value' => strtoupper($result['level']),
                        'short' => true,
                    ],
                ],
            ];
        }

        return $attachments;
    }

    /**
     * Send Discord notification
     */
    protected function sendDiscordNotification(array $config): void
    {
        if (empty($config['webhook_url'])) {
            return;
        }

        try {
            $criticalResults = array_filter($this->results, function($result) {
                return in_array($result['level'], [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
            });

            $payload = [
                'content' => $this->generateDiscordMessage($criticalResults),
                'embeds' => $this->generateDiscordEmbeds($criticalResults),
            ];

            $response = Http::post($config['webhook_url'], $payload);

            if ($response->successful()) {
                $this->addResult(
                    'Discord Notification Sent',
                    self::LEVEL_INFO,
                    'Critical issues notification sent to Discord'
                );
            } else {
                $this->addResult(
                    'Discord Notification Failed',
                    self::LEVEL_WARNING,
                    'Failed to send Discord notification: ' . $response->body()
                );
            }

        } catch (Exception $e) {
            $this->addResult(
                'Discord Notification Error',
                self::LEVEL_WARNING,
                'Error sending Discord notification: ' . $e->getMessage()
            );
        }
    }

    /**
     * Generate Discord message
     */
    protected function generateDiscordMessage(array $criticalResults): string
    {
        $serverName = $_SERVER['SERVER_NAME'] ?? 'Unknown Server';
        $environment = env('APP_ENV', 'Unknown');
        $issueCount = count($criticalResults);

        return "🚨 **Laravel Doctor Alert**\n" .
               "**Server:** `{$serverName}`\n" .
               "**Environment:** `{$environment}`\n" .
               "**Critical Issues:** `{$issueCount}`";
    }

    /**
     * Generate Discord embeds
     */
    protected function generateDiscordEmbeds(array $criticalResults): array
    {
        $embeds = [];

        foreach (array_slice($criticalResults, 0, 5) as $result) { // Limit to 5 issues
            $color = $result['level'] === self::LEVEL_CRITICAL ? 15158332 : 16776960; // Red or Yellow
            
            $embeds[] = [
                'title' => $result['message'],
                'description' => $result['advice'],
                'color' => $color,
                'footer' => [
                    'text' => 'Laravel Doctor | ' . strtoupper($result['level']),
                ],
                'timestamp' => $result['timestamp'],
            ];
        }

        return $embeds;
    }

    /**
     * Send custom webhook
     */
    protected function sendCustomWebhook(string $url, array $payload): bool
    {
        try {
            $response = Http::post($url, $payload);
            return $response->successful();
        } catch (Exception $e) {
            $this->addResult(
                'Custom Webhook Failed',
                self::LEVEL_WARNING,
                'Custom webhook error: ' . $e->getMessage()
            );
            return false;
        }
    }
}
