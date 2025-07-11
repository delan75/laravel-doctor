<!-- Result Detail Modal -->
<div x-show="showResultDetail" 
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     @click.self="showResultDetail = false">
    <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-96 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-start">
                <div class="flex items-center space-x-3">
                    <span x-text="selectedResult ? getLevelIcon(selectedResult.level) : ''" class="text-2xl"></span>
                    <div>
                        <h3 class="text-lg font-bold" x-text="selectedResult ? selectedResult.message : ''"></h3>
                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                              :class="selectedResult ? getLevelClass(selectedResult.level) : ''"
                              x-text="selectedResult ? selectedResult.level.toUpperCase() : ''"></span>
                    </div>
                </div>
                <button @click="showResultDetail = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-80">
            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Advice</h4>
                    <p class="text-gray-700" x-text="selectedResult ? selectedResult.advice : ''"></p>
                </div>
                <div x-show="selectedResult && selectedResult.details && Object.keys(selectedResult.details).length > 0">
                    <h4 class="font-medium text-gray-900 mb-2">Details</h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <pre class="text-sm text-gray-700" x-text="selectedResult ? JSON.stringify(selectedResult.details, null, 2) : ''"></pre>
                    </div>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Timestamp</h4>
                    <p class="text-gray-700" x-text="selectedResult ? formatTime(selectedResult.timestamp) : ''"></p>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-end space-x-3">
                <button @click="copyResultToClipboard()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    📋 Copy Details
                </button>
                <button @click="showResultDetail = false" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div x-show="showSettings" 
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     @click.self="showSettings = false">
    <div class="bg-white rounded-xl max-w-4xl w-full mx-4 max-h-96 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold">⚙️ Laravel Doctor Settings</h3>
                <button @click="showSettings = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-80">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- General Settings -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-4">General Settings</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.auto_refresh" class="rounded">
                                <span class="ml-2 text-sm">Auto-refresh every 5 minutes</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.show_notifications" class="rounded">
                                <span class="ml-2 text-sm">Show browser notifications</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.dark_mode" class="rounded">
                                <span class="ml-2 text-sm">Dark mode (coming soon)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-4">Email Alerts</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.email_alerts" class="rounded">
                                <span class="ml-2 text-sm">Enable email alerts</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                            <input type="email" 
                                   x-model="settings.admin_email" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   placeholder="admin@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alert Threshold</label>
                            <select x-model="settings.alert_threshold" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="critical">Critical issues only</option>
                                <option value="error">Errors and above</option>
                                <option value="warning">Warnings and above</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Webhook Settings -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-4">Webhook Integration</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.slack_enabled" class="rounded">
                                <span class="ml-2 text-sm">Enable Slack notifications</span>
                            </label>
                        </div>
                        <div x-show="settings.slack_enabled">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slack Webhook URL</label>
                            <input type="url" 
                                   x-model="settings.slack_webhook" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   placeholder="https://hooks.slack.com/services/...">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.discord_enabled" class="rounded">
                                <span class="ml-2 text-sm">Enable Discord notifications</span>
                            </label>
                        </div>
                        <div x-show="settings.discord_enabled">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discord Webhook URL</label>
                            <input type="url" 
                                   x-model="settings.discord_webhook" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   placeholder="https://discord.com/api/webhooks/...">
                        </div>
                    </div>
                </div>

                <!-- Check Configuration -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-4">Diagnostic Checks</h4>
                    <div class="space-y-2">
                        <template x-for="(enabled, check) in settings.checks" :key="check">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="settings.checks[check]" class="rounded">
                                <span class="ml-2 text-sm capitalize" x-text="check.replace('_', ' ')"></span>
                            </label>
                        </template>
                    </div>
                </div>

            </div>
        </div>
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-end space-x-3">
                <button @click="testWebhooks()" 
                        class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                    🧪 Test Webhooks
                </button>
                <button @click="saveSettings()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    💾 Save Settings
                </button>
                <button @click="showSettings = false" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div x-show="showHistory" 
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
     @click.self="showHistory = false">
    <div class="bg-white rounded-xl max-w-6xl w-full mx-4 max-h-96 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold">📈 Diagnostic History</h3>
                <button @click="showHistory = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-80">
            <div class="mb-6">
                <canvas id="historyChart" width="400" height="200"></canvas>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Health Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Critical Issues</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Checks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="record in historyRecords" :key="record.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatTime(record.timestamp)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-800': record.health_score >= 90,
                                              'bg-yellow-100 text-yellow-800': record.health_score >= 70 && record.health_score < 90,
                                              'bg-red-100 text-red-800': record.health_score < 70
                                          }"
                                          x-text="record.health_score"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="record.critical_issues"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="record.total_checks"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="viewHistoryDetails(record)" class="text-blue-600 hover:text-blue-900">View Details</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div x-show="isRunning" 
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-40">
    <div class="bg-white rounded-xl p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Running Diagnostics...</h3>
        <p class="text-gray-600">Please wait while we analyze your Laravel application</p>
        <div class="mt-4">
            <div class="bg-gray-200 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                     :style="`width: ${progress}%`"></div>
            </div>
            <p class="text-sm text-gray-500 mt-2" x-text="`${progress}% complete`"></p>
        </div>
    </div>
</div>
