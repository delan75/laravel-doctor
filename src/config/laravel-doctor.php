<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Doctor Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for Laravel Doctor diagnostics.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Email Alerts
    |--------------------------------------------------------------------------
    |
    | Configure email alerts for critical issues. When enabled, Laravel Doctor
    | will send an email notification when critical issues are detected.
    |
    */
    'email_alerts' => [
        'enabled' => env('LARAVEL_DOCTOR_EMAIL_ALERTS', false),
        'admin_email' => env('ADMIN_ALERT_EMAIL', null),
        'subject_prefix' => env('LARAVEL_DOCTOR_EMAIL_SUBJECT', 'Laravel Doctor Alert'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Paths
    |--------------------------------------------------------------------------
    |
    | Specify paths that should be ignored during certain checks.
    | Useful for excluding vendor directories, test files, etc.
    |
    */
    'ignored_paths' => [
        'vendor',
        'node_modules',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
    ],

    /*
    |--------------------------------------------------------------------------
    | Check Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific diagnostic checks.
    |
    */
    'checks' => [
        'environment' => true,
        'filesystem_permissions' => true,
        'security' => true,
        'database' => true,
        'services' => true,
        'logs' => true,
        'code_quality' => true,
        'composer' => true,
        'schedule_queues' => true,
        'version_consistency' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Quality Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for code quality checks.
    |
    */
    'code_quality' => [
        'max_file_lines' => 500,
        'check_todo_comments' => true,
        'check_debug_statements' => true,
        'php_cs_fixer_enabled' => true,
        'phpstan_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for security-related checks.
    |
    */
    'security' => [
        'check_debug_tools' => true,
        'check_exposed_files' => true,
        'check_backup_files' => true,
        'check_permissions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Analysis Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for log file analysis.
    |
    */
    'log_analysis' => [
        'lines_to_check' => 200,
        'error_threshold' => 5,
        'warning_threshold' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for exporting diagnostic results.
    |
    */
    'export' => [
        'default_format' => 'array',
        'html_template' => null, // Custom HTML template path
        'include_timestamp' => true,
        'include_server_info' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Notifications
    |--------------------------------------------------------------------------
    |
    | Configure webhook notifications for integration with Slack, Discord, etc.
    |
    */
    'webhooks' => [
        'slack' => [
            'enabled' => env('LARAVEL_DOCTOR_SLACK_ENABLED', false),
            'webhook_url' => env('LARAVEL_DOCTOR_SLACK_WEBHOOK', null),
            'channel' => env('LARAVEL_DOCTOR_SLACK_CHANNEL', '#general'),
            'username' => 'Laravel Doctor',
            'icon_emoji' => ':stethoscope:',
        ],
        
        'discord' => [
            'enabled' => env('LARAVEL_DOCTOR_DISCORD_ENABLED', false),
            'webhook_url' => env('LARAVEL_DOCTOR_DISCORD_WEBHOOK', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Checks
    |--------------------------------------------------------------------------
    |
    | Register custom diagnostic checks. Each check should be a callable
    | that receives the LaravelDoctor instance and returns a result array.
    |
    */
    'custom_checks' => [
        // Example:
        // \App\Diagnostics\CustomCheck::class . '@handle',
    ],
];
