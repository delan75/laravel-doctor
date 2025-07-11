<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🩺 Laravel Doctor Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .pulse-animation { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .health-score-excellent { background: linear-gradient(135deg, #10b981, #059669); }
        .health-score-good { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .health-score-poor { background: linear-gradient(135deg, #ef4444, #dc2626); }
    </style>
</head>
<body class="bg-gray-50 font-sans" x-data="doctorDashboard()">
    <!-- Navigation -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <div class="text-2xl">🩺</div>
                    <h1 class="text-xl font-bold">Laravel Doctor</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button @click="runDiagnostics()" 
                            :disabled="isRunning"
                            class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-all duration-200 disabled:opacity-50">
                        <span x-show="!isRunning">🔄 Run Diagnostics</span>
                        <span x-show="isRunning" class="pulse-animation">⏳ Running...</span>
                    </button>
                    <button @click="showSettings = true" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg">
                        ⚙️
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Health Score Section -->
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Main Health Score -->
                <div class="md:col-span-1">
                    <div class="card-shadow rounded-xl p-6 text-white"
                         :class="{
                             'health-score-excellent': healthScore >= 90,
                             'health-score-good': healthScore >= 70 && healthScore < 90,
                             'health-score-poor': healthScore < 70
                         }">
                        <div class="text-center">
                            <div class="text-4xl font-bold mb-2" x-text="healthScore"></div>
                            <div class="text-lg opacity-90">Health Score</div>
                            <div class="mt-4">
                                <div class="bg-white bg-opacity-20 rounded-full h-2">
                                    <div class="bg-white rounded-full h-2 transition-all duration-500"
                                         :style="`width: ${healthScore}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="md:col-span-3">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-white card-shadow rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600" x-text="summary.total_checks"></div>
                            <div class="text-sm text-gray-600">Total Checks</div>
                        </div>
                        <div class="bg-white card-shadow rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-red-600" x-text="summary.critical_issues"></div>
                            <div class="text-sm text-gray-600">Critical</div>
                        </div>
                        <div class="bg-white card-shadow rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-red-500" x-text="summary.levels.error || 0"></div>
                            <div class="text-sm text-gray-600">Errors</div>
                        </div>
                        <div class="bg-white card-shadow rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-yellow-500" x-text="summary.levels.warning || 0"></div>
                            <div class="text-sm text-gray-600">Warnings</div>
                        </div>
                        <div class="bg-white card-shadow rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-green-500" x-text="summary.levels.ok || 0"></div>
                            <div class="text-sm text-gray-600">Passed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="bg-white card-shadow rounded-xl p-6">
                <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <button @click="exportResults('json')" 
                            class="flex items-center justify-center space-x-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg transition-colors">
                        <span>📄</span>
                        <span>Export JSON</span>
                    </button>
                    <button @click="exportResults('html')" 
                            class="flex items-center justify-center space-x-2 bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg transition-colors">
                        <span>📊</span>
                        <span>Export HTML</span>
                    </button>
                    <button @click="sendTestEmail()" 
                            class="flex items-center justify-center space-x-2 bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-lg transition-colors">
                        <span>📧</span>
                        <span>Test Email</span>
                    </button>
                    <button @click="showHistory = true" 
                            class="flex items-center justify-center space-x-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-lg transition-colors">
                        <span>📈</span>
                        <span>View History</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Results List -->
            <div class="lg:col-span-2">
                <div class="bg-white card-shadow rounded-xl">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-bold">Diagnostic Results</h2>
                            <div class="flex space-x-2">
                                <select x-model="filterLevel" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                                    <option value="">All Levels</option>
                                    <option value="critical">Critical</option>
                                    <option value="error">Error</option>
                                    <option value="warning">Warning</option>
                                    <option value="info">Info</option>
                                    <option value="ok">OK</option>
                                </select>
                                <input x-model="searchQuery" 
                                       placeholder="Search results..." 
                                       class="border border-gray-300 rounded-lg px-3 py-1 text-sm w-48">
                            </div>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <template x-for="result in filteredResults" :key="result.timestamp">
                            <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                                 @click="selectedResult = result; showResultDetail = true">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <span x-text="getLevelIcon(result.level)" class="text-xl"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="getLevelClass(result.level)"
                                                  x-text="result.level.toUpperCase()"></span>
                                            <span class="text-sm text-gray-500" x-text="formatTime(result.timestamp)"></span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 mt-1" x-text="result.message"></p>
                                        <p class="text-sm text-gray-600 mt-1" x-text="result.advice"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- System Info -->
                <div class="bg-white card-shadow rounded-xl p-6">
                    <h3 class="text-lg font-bold mb-4">System Information</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Laravel Version:</span>
                            <span class="font-medium" x-text="systemInfo.laravel_version"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">PHP Version:</span>
                            <span class="font-medium" x-text="systemInfo.php_version"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Environment:</span>
                            <span class="font-medium" x-text="systemInfo.environment"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Check:</span>
                            <span class="font-medium" x-text="formatTime(lastRun)"></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white card-shadow rounded-xl p-6">
                    <h3 class="text-lg font-bold mb-4">Recent Activity</h3>
                    <div class="space-y-3">
                        <template x-for="activity in recentActivity" :key="activity.id">
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="w-2 h-2 rounded-full"
                                     :class="{
                                         'bg-green-500': activity.type === 'success',
                                         'bg-red-500': activity.type === 'error',
                                         'bg-yellow-500': activity.type === 'warning',
                                         'bg-blue-500': activity.type === 'info'
                                     }"></div>
                                <div class="flex-1">
                                    <p x-text="activity.message"></p>
                                    <p class="text-gray-500 text-xs" x-text="formatTime(activity.timestamp)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals and overlays will be added in the next file -->
    
    <script>
        function doctorDashboard() {
            return {
                // State
                isRunning: false,
                healthScore: 85,
                summary: {
                    total_checks: 0,
                    critical_issues: 0,
                    levels: {}
                },
                results: [],
                systemInfo: {
                    laravel_version: '10.x',
                    php_version: '8.2',
                    environment: 'production'
                },
                lastRun: new Date().toISOString(),
                recentActivity: [],
                
                // UI State
                showSettings: false,
                showHistory: false,
                showResultDetail: false,
                selectedResult: null,
                filterLevel: '',
                searchQuery: '',
                
                // Computed
                get filteredResults() {
                    let filtered = this.results;
                    
                    if (this.filterLevel) {
                        filtered = filtered.filter(r => r.level === this.filterLevel);
                    }
                    
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(r => 
                            r.message.toLowerCase().includes(query) ||
                            r.advice.toLowerCase().includes(query)
                        );
                    }
                    
                    return filtered;
                },
                
                // Methods
                init() {
                    this.loadInitialData();
                    this.startAutoRefresh();
                },
                
                async loadInitialData() {
                    // Load initial diagnostic data
                    await this.runDiagnostics();
                },
                
                async runDiagnostics() {
                    this.isRunning = true;
                    
                    try {
                        const response = await fetch('/doctor/api', {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        
                        const data = await response.json();
                        
                        this.results = data.results;
                        this.summary = data.summary;
                        this.healthScore = data.health_score;
                        this.lastRun = data.timestamp;
                        
                        this.addActivity('Diagnostics completed successfully', 'success');
                        
                    } catch (error) {
                        console.error('Failed to run diagnostics:', error);
                        this.addActivity('Failed to run diagnostics', 'error');
                    } finally {
                        this.isRunning = false;
                    }
                },
                
                async exportResults(format) {
                    try {
                        const response = await fetch(`/doctor/export?format=${format}`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `laravel-doctor-report.${format}`;
                        a.click();
                        window.URL.revokeObjectURL(url);

                        this.addActivity(`Report exported as ${format.toUpperCase()}`, 'success');
                    } catch (error) {
                        this.addActivity('Export failed', 'error');
                    }
                },

                async sendTestEmail() {
                    try {
                        const response = await fetch('/doctor/test-email', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        });

                        if (response.ok) {
                            this.addActivity('Test email sent successfully', 'success');
                        } else {
                            this.addActivity('Failed to send test email', 'error');
                        }
                    } catch (error) {
                        this.addActivity('Email test failed', 'error');
                    }
                },

                startAutoRefresh() {
                    // Auto-refresh every 5 minutes
                    setInterval(() => {
                        if (!this.isRunning) {
                            this.runDiagnostics();
                        }
                    }, 300000);
                },

                addActivity(message, type) {
                    this.recentActivity.unshift({
                        id: Date.now(),
                        message,
                        type,
                        timestamp: new Date().toISOString()
                    });

                    // Keep only last 10 activities
                    if (this.recentActivity.length > 10) {
                        this.recentActivity = this.recentActivity.slice(0, 10);
                    }
                },

                getLevelIcon(level) {
                    const icons = {
                        'critical': '🚨',
                        'error': '❌',
                        'warning': '⚠️',
                        'info': 'ℹ️',
                        'ok': '✅'
                    };
                    return icons[level] || '•';
                },

                getLevelClass(level) {
                    const classes = {
                        'critical': 'bg-red-100 text-red-800',
                        'error': 'bg-red-100 text-red-800',
                        'warning': 'bg-yellow-100 text-yellow-800',
                        'info': 'bg-blue-100 text-blue-800',
                        'ok': 'bg-green-100 text-green-800'
                    };
                    return classes[level] || 'bg-gray-100 text-gray-800';
                },

                formatTime(timestamp) {
                    return new Date(timestamp).toLocaleString();
                }
            }
        }
    </script>
</body>
</html>
