<!-- Configuration Management Interface -->
<div x-data="configManager()" class="max-w-6xl mx-auto">
    
    <!-- Header -->
    <div class="bg-white card-shadow rounded-xl p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">⚙️ Laravel Doctor Configuration</h1>
                <p class="text-gray-600 mt-1">Manage diagnostic settings, alerts, and custom checks</p>
            </div>
            <div class="flex space-x-3">
                <button @click="resetToDefaults()" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    🔄 Reset to Defaults
                </button>
                <button @click="exportConfig()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    📤 Export Config
                </button>
                <button @click="saveConfiguration()" 
                        :disabled="isSaving"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50">
                    <span x-show="!isSaving">💾 Save Changes</span>
                    <span x-show="isSaving">⏳ Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Configuration Tabs -->
    <div class="bg-white card-shadow rounded-xl overflow-hidden">
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <template x-for="tab in tabs" :key="tab.id">
                    <button @click="activeTab = tab.id"
                            :class="activeTab === tab.id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <span x-text="tab.icon"></span>
                        <span x-text="tab.name" class="ml-2"></span>
                    </button>
                </template>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            
            <!-- General Settings Tab -->
            <div x-show="activeTab === 'general'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Basic Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Basic Settings</h3>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.auto_refresh" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Auto-refresh diagnostics every 5 minutes</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.show_notifications" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Show browser notifications</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.colorized_output" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Use colorized console output</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Export Format</label>
                            <select x-model="config.export_format" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="array">Array</option>
                                <option value="json">JSON</option>
                                <option value="html">HTML</option>
                            </select>
                        </div>
                    </div>

                    <!-- Performance Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Performance Settings</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Log Analysis Lines</label>
                            <input type="number" x-model="config.log_analysis.lines_to_check" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                   min="50" max="1000" step="50">
                            <p class="text-xs text-gray-500 mt-1">Number of log lines to analyze (50-1000)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max File Size for Code Quality (lines)</label>
                            <input type="number" x-model="config.code_quality.max_file_lines" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                   min="100" max="2000" step="100">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ignored Paths</label>
                            <textarea x-model="ignoredPathsText" 
                                      @input="updateIgnoredPaths()"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 h-20"
                                      placeholder="vendor&#10;node_modules&#10;storage/framework"></textarea>
                            <p class="text-xs text-gray-500 mt-1">One path per line</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email & Alerts Tab -->
            <div x-show="activeTab === 'alerts'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Email Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Email Alerts</h3>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.email_alerts.enabled" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Enable email alerts</span>
                            </label>
                        </div>
                        
                        <div x-show="config.email_alerts.enabled">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                            <input type="email" x-model="config.email_alerts.admin_email" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                   placeholder="admin@example.com">
                        </div>
                        
                        <div x-show="config.email_alerts.enabled">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject Prefix</label>
                            <input type="text" x-model="config.email_alerts.subject_prefix" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                   placeholder="Laravel Doctor Alert">
                        </div>
                        
                        <div x-show="config.email_alerts.enabled">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alert Threshold</label>
                            <select x-model="config.alert_threshold" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="critical">Critical issues only</option>
                                <option value="error">Errors and above</option>
                                <option value="warning">Warnings and above</option>
                            </select>
                        </div>
                        
                        <div x-show="config.email_alerts.enabled">
                            <button @click="testEmail()" 
                                    class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                📧 Send Test Email
                            </button>
                        </div>
                    </div>

                    <!-- Webhook Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Webhook Integration</h3>
                        
                        <!-- Slack -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">💬</span>
                                    <span class="font-medium">Slack</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="config.webhooks.slack.enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            <div x-show="config.webhooks.slack.enabled" class="space-y-3">
                                <input type="url" x-model="config.webhooks.slack.webhook_url" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                       placeholder="https://hooks.slack.com/services/...">
                                <input type="text" x-model="config.webhooks.slack.channel" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                       placeholder="#general">
                                <button @click="testWebhook('slack')" 
                                        class="w-full px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                                    Test Slack Webhook
                                </button>
                            </div>
                        </div>
                        
                        <!-- Discord -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">🎮</span>
                                    <span class="font-medium">Discord</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="config.webhooks.discord.enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            <div x-show="config.webhooks.discord.enabled" class="space-y-3">
                                <input type="url" x-model="config.webhooks.discord.webhook_url" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                       placeholder="https://discord.com/api/webhooks/...">
                                <button @click="testWebhook('discord')" 
                                        class="w-full px-3 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors text-sm">
                                    Test Discord Webhook
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnostic Checks Tab -->
            <div x-show="activeTab === 'checks'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Core Checks -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Core Diagnostic Checks</h3>
                        <div class="space-y-3">
                            <template x-for="(enabled, check) in config.checks" :key="check">
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                    <div>
                                        <span class="font-medium text-gray-900" x-text="formatCheckName(check)"></span>
                                        <p class="text-sm text-gray-600" x-text="getCheckDescription(check)"></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="config.checks[check]" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Code Quality Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Code Quality Settings</h3>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.code_quality.check_todo_comments" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Check for TODO/FIXME comments</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.code_quality.check_debug_statements" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Check for debug statements</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.code_quality.php_cs_fixer_enabled" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Enable PHP CS Fixer integration</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="config.code_quality.phpstan_enabled" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Enable PHPStan integration</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Checks Tab -->
            <div x-show="activeTab === 'custom'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Custom Diagnostic Checks</h3>
                    <button @click="addCustomCheck()" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        ➕ Add Custom Check
                    </button>
                </div>
                
                <div class="space-y-4">
                    <template x-for="(check, index) in customChecks" :key="index">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <input type="text" x-model="check.name" 
                                       class="font-medium text-lg border-none p-0 focus:ring-0"
                                       placeholder="Custom Check Name">
                                <button @click="removeCustomCheck(index)" 
                                        class="text-red-500 hover:text-red-700">
                                    🗑️
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea x-model="check.description" 
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 h-20"
                                              placeholder="Describe what this check does..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <input type="text" x-model="check.category" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                           placeholder="e.g., Security, Performance">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">PHP Code</label>
                                <textarea x-model="check.code" 
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 h-32 font-mono text-sm"
                                          placeholder="return [&#10;    'message' => 'Check result',&#10;    'level' => 'ok',&#10;    'advice' => 'Everything looks good'&#10;];"></textarea>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="check.enabled" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Enabled</span>
                                </label>
                                <button @click="testCustomCheck(check)" 
                                        class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors">
                                    🧪 Test Check
                                </button>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="customChecks.length === 0" class="text-center py-12 text-gray-500">
                        <div class="text-4xl mb-4">🔧</div>
                        <p>No custom checks configured</p>
                        <p class="text-sm">Add custom diagnostic checks to extend Laravel Doctor's functionality</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Status Messages -->
    <div x-show="statusMessage" 
         x-transition
         class="fixed bottom-4 right-4 max-w-sm bg-white border border-gray-200 rounded-lg shadow-lg p-4 z-50">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <span x-text="statusIcon" class="text-xl"></span>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900" x-text="statusMessage"></p>
            </div>
            <button @click="statusMessage = ''" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function configManager() {
    return {
        // State
        activeTab: 'general',
        isSaving: false,
        statusMessage: '',
        statusIcon: '',

        // Configuration
        config: {
            auto_refresh: true,
            show_notifications: true,
            colorized_output: true,
            export_format: 'array',
            email_alerts: {
                enabled: false,
                admin_email: '',
                subject_prefix: 'Laravel Doctor Alert'
            },
            alert_threshold: 'critical',
            webhooks: {
                slack: {
                    enabled: false,
                    webhook_url: '',
                    channel: '#general'
                },
                discord: {
                    enabled: false,
                    webhook_url: ''
                }
            },
            checks: {
                environment: true,
                filesystem_permissions: true,
                security: true,
                database: true,
                services: true,
                logs: true,
                code_quality: true,
                composer: true,
                schedule_queues: true,
                version_consistency: true
            },
            code_quality: {
                max_file_lines: 500,
                check_todo_comments: true,
                check_debug_statements: true,
                php_cs_fixer_enabled: true,
                phpstan_enabled: true
            },
            log_analysis: {
                lines_to_check: 200,
                error_threshold: 5,
                warning_threshold: 10
            },
            ignored_paths: ['vendor', 'node_modules', 'storage/framework']
        },

        customChecks: [],
        ignoredPathsText: '',

        tabs: [
            { id: 'general', name: 'General', icon: '⚙️' },
            { id: 'alerts', name: 'Alerts & Notifications', icon: '🔔' },
            { id: 'checks', name: 'Diagnostic Checks', icon: '🔍' },
            { id: 'custom', name: 'Custom Checks', icon: '🔧' }
        ],

        // Initialization
        init() {
            this.loadConfiguration();
            this.updateIgnoredPathsText();
        },

        async loadConfiguration() {
            try {
                const response = await fetch('/doctor/config');
                if (response.ok) {
                    const data = await response.json();
                    this.config = { ...this.config, ...data };
                    this.customChecks = data.custom_checks || [];
                    this.updateIgnoredPathsText();
                }
            } catch (error) {
                console.error('Failed to load configuration:', error);
                this.showStatus('Failed to load configuration', '❌');
            }
        },

        async saveConfiguration() {
            this.isSaving = true;

            try {
                const response = await fetch('/doctor/config', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        config: this.config,
                        custom_checks: this.customChecks
                    })
                });

                if (response.ok) {
                    this.showStatus('Configuration saved successfully', '✅');
                } else {
                    throw new Error('Failed to save configuration');
                }
            } catch (error) {
                console.error('Failed to save configuration:', error);
                this.showStatus('Failed to save configuration', '❌');
            } finally {
                this.isSaving = false;
            }
        },

        resetToDefaults() {
            if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                // Reset to default configuration
                this.config = {
                    auto_refresh: true,
                    show_notifications: true,
                    colorized_output: true,
                    export_format: 'array',
                    email_alerts: {
                        enabled: false,
                        admin_email: '',
                        subject_prefix: 'Laravel Doctor Alert'
                    },
                    alert_threshold: 'critical',
                    webhooks: {
                        slack: { enabled: false, webhook_url: '', channel: '#general' },
                        discord: { enabled: false, webhook_url: '' }
                    },
                    checks: {
                        environment: true,
                        filesystem_permissions: true,
                        security: true,
                        database: true,
                        services: true,
                        logs: true,
                        code_quality: true,
                        composer: true,
                        schedule_queues: true,
                        version_consistency: true
                    },
                    code_quality: {
                        max_file_lines: 500,
                        check_todo_comments: true,
                        check_debug_statements: true,
                        php_cs_fixer_enabled: true,
                        phpstan_enabled: true
                    },
                    log_analysis: {
                        lines_to_check: 200,
                        error_threshold: 5,
                        warning_threshold: 10
                    },
                    ignored_paths: ['vendor', 'node_modules', 'storage/framework']
                };
                this.customChecks = [];
                this.updateIgnoredPathsText();
                this.showStatus('Configuration reset to defaults', '🔄');
            }
        },

        exportConfig() {
            const configData = {
                config: this.config,
                custom_checks: this.customChecks,
                exported_at: new Date().toISOString()
            };

            const blob = new Blob([JSON.stringify(configData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'laravel-doctor-config.json';
            a.click();
            URL.revokeObjectURL(url);

            this.showStatus('Configuration exported', '📤');
        },

        // Custom Checks Management
        addCustomCheck() {
            this.customChecks.push({
                name: 'New Custom Check',
                description: '',
                category: 'Custom',
                code: 'return [\n    "message" => "Custom check result",\n    "level" => "ok",\n    "advice" => "Everything looks good"\n];',
                enabled: true
            });
        },

        removeCustomCheck(index) {
            if (confirm('Are you sure you want to remove this custom check?')) {
                this.customChecks.splice(index, 1);
            }
        },

        async testCustomCheck(check) {
            try {
                const response = await fetch('/doctor/test-custom-check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ check })
                });

                const result = await response.json();

                if (response.ok) {
                    this.showStatus(`Test passed: ${result.message}`, '✅');
                } else {
                    this.showStatus(`Test failed: ${result.error}`, '❌');
                }
            } catch (error) {
                this.showStatus('Test failed: Network error', '❌');
            }
        },

        // Testing Functions
        async testEmail() {
            try {
                const response = await fetch('/doctor/test-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: this.config.email_alerts.admin_email,
                        subject_prefix: this.config.email_alerts.subject_prefix
                    })
                });

                if (response.ok) {
                    this.showStatus('Test email sent successfully', '📧');
                } else {
                    this.showStatus('Failed to send test email', '❌');
                }
            } catch (error) {
                this.showStatus('Email test failed', '❌');
            }
        },

        async testWebhook(type) {
            try {
                const webhookConfig = this.config.webhooks[type];
                const response = await fetch(`/doctor/test-webhook/${type}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(webhookConfig)
                });

                if (response.ok) {
                    this.showStatus(`${type.charAt(0).toUpperCase() + type.slice(1)} webhook test successful`, '✅');
                } else {
                    this.showStatus(`${type.charAt(0).toUpperCase() + type.slice(1)} webhook test failed`, '❌');
                }
            } catch (error) {
                this.showStatus('Webhook test failed', '❌');
            }
        },

        // Utility Functions
        updateIgnoredPaths() {
            this.config.ignored_paths = this.ignoredPathsText
                .split('\n')
                .map(path => path.trim())
                .filter(path => path.length > 0);
        },

        updateIgnoredPathsText() {
            this.ignoredPathsText = this.config.ignored_paths.join('\n');
        },

        formatCheckName(check) {
            return check.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        getCheckDescription(check) {
            const descriptions = {
                environment: 'Validates .env file and environment variables',
                filesystem_permissions: 'Checks directory permissions and file access',
                security: 'Scans for security vulnerabilities and misconfigurations',
                database: 'Tests database connectivity and configuration',
                services: 'Validates Redis, cache, queue, and mail services',
                logs: 'Analyzes Laravel log files for errors and issues',
                code_quality: 'Runs static analysis and code quality checks',
                composer: 'Validates Composer dependencies and packages',
                schedule_queues: 'Monitors scheduled tasks and queue workers',
                version_consistency: 'Checks Laravel and PHP version compatibility'
            };
            return descriptions[check] || 'Custom diagnostic check';
        },

        showStatus(message, icon) {
            this.statusMessage = message;
            this.statusIcon = icon;
            setTimeout(() => {
                this.statusMessage = '';
            }, 5000);
        }
    }
}
</script>
