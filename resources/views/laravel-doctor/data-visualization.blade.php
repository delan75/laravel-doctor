<!-- Data Visualization Dashboard -->
<div x-data="dataVisualization()" class="space-y-6">
    
    <!-- Chart Controls -->
    <div class="bg-white card-shadow rounded-xl p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <div>
                <h2 class="text-xl font-bold text-gray-900">📊 Health Analytics</h2>
                <p class="text-gray-600 mt-1">Visual insights into your Laravel application's health trends</p>
            </div>
            
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                <!-- Time Range Selector -->
                <select x-model="timeRange" @change="updateCharts()" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                </select>
                
                <!-- Chart Type Selector -->
                <select x-model="chartType" @change="updateCharts()" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="line">Line Chart</option>
                    <option value="bar">Bar Chart</option>
                    <option value="area">Area Chart</option>
                </select>
                
                <!-- Refresh Button -->
                <button @click="refreshData()" 
                        :disabled="isLoading"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50">
                    <span x-show="!isLoading">🔄 Refresh</span>
                    <span x-show="isLoading">⏳ Loading...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Health Score Trend -->
        <div class="bg-white card-shadow rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Health Score Trend</h3>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Current:</span>
                    <span class="px-2 py-1 rounded-full text-sm font-medium"
                          :class="{
                              'bg-green-100 text-green-800': currentHealthScore >= 90,
                              'bg-yellow-100 text-yellow-800': currentHealthScore >= 70 && currentHealthScore < 90,
                              'bg-red-100 text-red-800': currentHealthScore < 70
                          }"
                          x-text="currentHealthScore + '/100'">
                    </span>
                </div>
            </div>
            <div class="relative h-64">
                <canvas id="healthScoreChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- Issues Breakdown -->
        <div class="bg-white card-shadow rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Issues Breakdown</h3>
            <div class="relative h-64">
                <canvas id="issuesChart" class="w-full h-full"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span>Critical: <span x-text="issueStats.critical"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                    <span>Error: <span x-text="issueStats.error"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <span>Warning: <span x-text="issueStats.warning"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span>Passed: <span x-text="issueStats.ok"></span></span>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white card-shadow rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h3>
            <div class="relative h-64">
                <canvas id="performanceChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- Category Analysis -->
        <div class="bg-white card-shadow rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Issues by Category</h3>
            <div class="relative h-64">
                <canvas id="categoryChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Average Response Time -->
        <div class="bg-white card-shadow rounded-xl p-6 text-center">
            <div class="text-3xl mb-2">⚡</div>
            <div class="text-2xl font-bold text-gray-900" x-text="metrics.avgResponseTime + 'ms'"></div>
            <div class="text-sm text-gray-600">Avg Response Time</div>
            <div class="mt-2">
                <span class="text-xs px-2 py-1 rounded-full"
                      :class="metrics.responseTimeTrend > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                      x-text="(metrics.responseTimeTrend > 0 ? '+' : '') + metrics.responseTimeTrend + '%'">
                </span>
            </div>
        </div>

        <!-- Uptime -->
        <div class="bg-white card-shadow rounded-xl p-6 text-center">
            <div class="text-3xl mb-2">🔄</div>
            <div class="text-2xl font-bold text-gray-900" x-text="metrics.uptime + '%'"></div>
            <div class="text-sm text-gray-600">Uptime</div>
            <div class="mt-2">
                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                    Excellent
                </span>
            </div>
        </div>

        <!-- Error Rate -->
        <div class="bg-white card-shadow rounded-xl p-6 text-center">
            <div class="text-3xl mb-2">📈</div>
            <div class="text-2xl font-bold text-gray-900" x-text="metrics.errorRate + '%'"></div>
            <div class="text-sm text-gray-600">Error Rate</div>
            <div class="mt-2">
                <span class="text-xs px-2 py-1 rounded-full"
                      :class="metrics.errorRate > 5 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                    <span x-text="metrics.errorRate > 5 ? 'High' : 'Low'"></span>
                </span>
            </div>
        </div>

        <!-- Security Score -->
        <div class="bg-white card-shadow rounded-xl p-6 text-center">
            <div class="text-3xl mb-2">🔒</div>
            <div class="text-2xl font-bold text-gray-900" x-text="metrics.securityScore + '/100'"></div>
            <div class="text-sm text-gray-600">Security Score</div>
            <div class="mt-2">
                <span class="text-xs px-2 py-1 rounded-full"
                      :class="{
                          'bg-green-100 text-green-800': metrics.securityScore >= 90,
                          'bg-yellow-100 text-yellow-800': metrics.securityScore >= 70,
                          'bg-red-100 text-red-800': metrics.securityScore < 70
                      }">
                    <span x-text="metrics.securityScore >= 90 ? 'Excellent' : metrics.securityScore >= 70 ? 'Good' : 'Needs Work'"></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Trend Analysis -->
    <div class="bg-white card-shadow rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Trend Analysis</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Health Trend -->
            <div class="text-center">
                <div class="text-4xl mb-2" x-text="getTrendIcon(trends.health)"></div>
                <div class="text-lg font-semibold text-gray-900">Health Trend</div>
                <div class="text-sm text-gray-600 mt-1" x-text="trends.health.description"></div>
                <div class="mt-2">
                    <span class="text-xs px-2 py-1 rounded-full"
                          :class="getTrendClass(trends.health.direction)"
                          x-text="trends.health.change">
                    </span>
                </div>
            </div>

            <!-- Performance Trend -->
            <div class="text-center">
                <div class="text-4xl mb-2" x-text="getTrendIcon(trends.performance)"></div>
                <div class="text-lg font-semibold text-gray-900">Performance Trend</div>
                <div class="text-sm text-gray-600 mt-1" x-text="trends.performance.description"></div>
                <div class="mt-2">
                    <span class="text-xs px-2 py-1 rounded-full"
                          :class="getTrendClass(trends.performance.direction)"
                          x-text="trends.performance.change">
                    </span>
                </div>
            </div>

            <!-- Security Trend -->
            <div class="text-center">
                <div class="text-4xl mb-2" x-text="getTrendIcon(trends.security)"></div>
                <div class="text-lg font-semibold text-gray-900">Security Trend</div>
                <div class="text-sm text-gray-600 mt-1" x-text="trends.security.description"></div>
                <div class="mt-2">
                    <span class="text-xs px-2 py-1 rounded-full"
                          :class="getTrendClass(trends.security.direction)"
                          x-text="trends.security.change">
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="bg-white card-shadow rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">💡 Recommendations</h3>
        <div class="space-y-4">
            <template x-for="recommendation in recommendations" :key="recommendation.id">
                <div class="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <span x-text="recommendation.icon" class="text-xl"></span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900" x-text="recommendation.title"></h4>
                        <p class="text-sm text-gray-600 mt-1" x-text="recommendation.description"></p>
                        <div class="mt-2">
                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800"
                                  x-text="recommendation.priority + ' Priority'">
                            </span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button @click="implementRecommendation(recommendation)"
                                class="text-sm text-blue-600 hover:text-blue-800">
                            Implement
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function dataVisualization() {
    return {
        // State
        timeRange: '7d',
        chartType: 'line',
        isLoading: false,

        // Data
        currentHealthScore: 85,
        issueStats: {
            critical: 2,
            error: 5,
            warning: 12,
            ok: 156
        },

        metrics: {
            avgResponseTime: 245,
            responseTimeTrend: -5.2,
            uptime: 99.8,
            errorRate: 2.1,
            securityScore: 88
        },

        trends: {
            health: {
                direction: 'up',
                change: '+3.2% this week',
                description: 'Health score improving steadily'
            },
            performance: {
                direction: 'down',
                change: '-1.8% this week',
                description: 'Slight performance degradation'
            },
            security: {
                direction: 'up',
                change: '+2.1% this week',
                description: 'Security posture strengthening'
            }
        },

        recommendations: [
            {
                id: 1,
                icon: '🔧',
                title: 'Optimize Database Queries',
                description: 'Several slow queries detected. Consider adding indexes or optimizing query structure.',
                priority: 'High'
            },
            {
                id: 2,
                icon: '🔒',
                title: 'Update Security Headers',
                description: 'Missing security headers detected. Implement CSP and HSTS headers.',
                priority: 'Medium'
            },
            {
                id: 3,
                icon: '📦',
                title: 'Update Dependencies',
                description: '5 packages have security updates available.',
                priority: 'High'
            }
        ],

        // Chart instances
        charts: {},

        // Initialization
        init() {
            this.loadData();
            this.initializeCharts();
        },

        async loadData() {
            this.isLoading = true;
            try {
                const response = await fetch(`/doctor/analytics?range=${this.timeRange}`);
                const data = await response.json();

                this.currentHealthScore = data.current_health_score || 85;
                this.issueStats = data.issue_stats || this.issueStats;
                this.metrics = { ...this.metrics, ...data.metrics };
                this.trends = { ...this.trends, ...data.trends };
                this.recommendations = data.recommendations || this.recommendations;

                this.updateCharts();
            } catch (error) {
                console.error('Failed to load analytics data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        initializeCharts() {
            this.initHealthScoreChart();
            this.initIssuesChart();
            this.initPerformanceChart();
            this.initCategoryChart();
        },

        initHealthScoreChart() {
            const ctx = document.getElementById('healthScoreChart').getContext('2d');

            this.charts.healthScore = new Chart(ctx, {
                type: this.chartType,
                data: {
                    labels: this.generateTimeLabels(),
                    datasets: [{
                        label: 'Health Score',
                        data: this.generateHealthScoreData(),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: this.chartType === 'area'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        initIssuesChart() {
            const ctx = document.getElementById('issuesChart').getContext('2d');

            this.charts.issues = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Critical', 'Error', 'Warning', 'Passed'],
                    datasets: [{
                        data: [
                            this.issueStats.critical,
                            this.issueStats.error,
                            this.issueStats.warning,
                            this.issueStats.ok
                        ],
                        backgroundColor: [
                            'rgb(239, 68, 68)',
                            'rgb(248, 113, 113)',
                            'rgb(245, 158, 11)',
                            'rgb(34, 197, 94)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        initPerformanceChart() {
            const ctx = document.getElementById('performanceChart').getContext('2d');

            this.charts.performance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.generateTimeLabels(),
                    datasets: [
                        {
                            label: 'Response Time (ms)',
                            data: this.generatePerformanceData('response_time'),
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Memory Usage (%)',
                            data: this.generatePerformanceData('memory'),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Response Time (ms)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Memory Usage (%)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        },

        initCategoryChart() {
            const ctx = document.getElementById('categoryChart').getContext('2d');

            this.charts.category = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Security', 'Performance', 'Database', 'Environment', 'Code Quality'],
                    datasets: [{
                        label: 'Issues',
                        data: [8, 5, 3, 2, 7],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(168, 85, 247, 0.8)'
                        ],
                        borderColor: [
                            'rgb(239, 68, 68)',
                            'rgb(245, 158, 11)',
                            'rgb(59, 130, 246)',
                            'rgb(34, 197, 94)',
                            'rgb(168, 85, 247)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        updateCharts() {
            Object.values(this.charts).forEach(chart => {
                if (chart) {
                    chart.data.labels = this.generateTimeLabels();
                    chart.update();
                }
            });
        },

        async refreshData() {
            await this.loadData();
        },

        // Data generation methods
        generateTimeLabels() {
            const labels = [];
            const now = new Date();

            switch (this.timeRange) {
                case '24h':
                    for (let i = 23; i >= 0; i--) {
                        const time = new Date(now - i * 60 * 60 * 1000);
                        labels.push(time.getHours() + ':00');
                    }
                    break;
                case '7d':
                    for (let i = 6; i >= 0; i--) {
                        const date = new Date(now - i * 24 * 60 * 60 * 1000);
                        labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
                    }
                    break;
                case '30d':
                    for (let i = 29; i >= 0; i--) {
                        const date = new Date(now - i * 24 * 60 * 60 * 1000);
                        labels.push(date.getDate().toString());
                    }
                    break;
                case '90d':
                    for (let i = 89; i >= 0; i -= 7) {
                        const date = new Date(now - i * 24 * 60 * 60 * 1000);
                        labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                    }
                    break;
            }

            return labels;
        },

        generateHealthScoreData() {
            const baseScore = this.currentHealthScore;
            const dataPoints = this.generateTimeLabels().length;
            const data = [];

            for (let i = 0; i < dataPoints; i++) {
                const variation = (Math.random() - 0.5) * 10;
                const score = Math.max(0, Math.min(100, baseScore + variation));
                data.push(score);
            }

            return data;
        },

        generatePerformanceData(type) {
            const dataPoints = this.generateTimeLabels().length;
            const data = [];

            for (let i = 0; i < dataPoints; i++) {
                if (type === 'response_time') {
                    data.push(Math.random() * 200 + 150);
                } else if (type === 'memory') {
                    data.push(Math.random() * 30 + 40);
                }
            }

            return data;
        },

        // Utility methods
        getTrendIcon(trend) {
            const icons = {
                up: '📈',
                down: '📉',
                stable: '➡️'
            };
            return icons[trend.direction] || '➡️';
        },

        getTrendClass(direction) {
            const classes = {
                up: 'bg-green-100 text-green-800',
                down: 'bg-red-100 text-red-800',
                stable: 'bg-gray-100 text-gray-800'
            };
            return classes[direction] || 'bg-gray-100 text-gray-800';
        },

        implementRecommendation(recommendation) {
            // Handle recommendation implementation
            alert(`Implementing: ${recommendation.title}`);
            // You would typically make an API call here
        }
    }
}
</script>
