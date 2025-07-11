<!-- Notification Center Component -->
<div x-data="notificationCenter()" class="relative">
    
    <!-- Notification Bell Icon -->
    <button @click="toggleNotificationPanel()" 
            class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        <!-- Notification Badge -->
        <span x-show="unreadCount > 0" 
              x-text="unreadCount > 99 ? '99+' : unreadCount"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
        </span>
        
        <!-- Pulse Animation for New Notifications -->
        <span x-show="hasNewNotifications" 
              class="absolute -top-1 -right-1 bg-red-500 rounded-full h-5 w-5 animate-ping">
        </span>
    </button>

    <!-- Notification Panel -->
    <div x-show="showPanel" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showPanel = false"
         class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-lg border border-gray-200 z-50">
        
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">🔔 Notifications</h3>
                <div class="flex items-center space-x-2">
                    <button @click="markAllAsRead()" 
                            x-show="unreadCount > 0"
                            class="text-sm text-blue-600 hover:text-blue-800">
                        Mark all read
                    </button>
                    <button @click="showSettings = !showSettings" 
                            class="p-1 text-gray-400 hover:text-gray-600 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="flex space-x-1 mt-3">
                <template x-for="filter in filters" :key="filter.id">
                    <button @click="activeFilter = filter.id"
                            :class="activeFilter === filter.id ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-800'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                        <span x-text="filter.name"></span>
                        <span x-show="filter.count > 0" 
                              x-text="'(' + filter.count + ')'"
                              class="ml-1 text-xs"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Settings Panel -->
        <div x-show="showSettings" 
             x-collapse
             class="p-4 border-b border-gray-200 bg-gray-50">
            <h4 class="font-medium text-gray-900 mb-3">Notification Preferences</h4>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.browser_notifications" class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Browser notifications</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.sound_alerts" class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Sound alerts</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.email_digest" class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Daily email digest</span>
                </label>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alert Levels</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="level in alertLevels" :key="level.id">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="settings.alert_levels" :value="level.id" class="rounded">
                            <span class="ml-1 text-xs" x-text="level.name"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-for="notification in filteredNotifications" :key="notification.id">
                <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                     :class="!notification.read ? 'bg-blue-50' : ''"
                     @click="markAsRead(notification)">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <span x-text="getNotificationIcon(notification.type)" class="text-xl"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="getNotificationLevelClass(notification.level)"
                                      x-text="notification.level.toUpperCase()"></span>
                                <span class="text-xs text-gray-500" x-text="formatTime(notification.timestamp)"></span>
                                <div x-show="!notification.read" class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 mt-1" x-text="notification.title"></h4>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-2" x-text="notification.message"></p>
                            
                            <!-- Action Buttons -->
                            <div x-show="notification.actions && notification.actions.length > 0" 
                                 class="flex space-x-2 mt-2">
                                <template x-for="action in notification.actions" :key="action.id">
                                    <button @click.stop="executeAction(notification, action)"
                                            class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                                            x-text="action.label">
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button @click.stop="dismissNotification(notification)" 
                                    class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Empty State -->
            <div x-show="filteredNotifications.length === 0" class="p-8 text-center text-gray-500">
                <div class="text-4xl mb-2">🔕</div>
                <p class="text-sm">No notifications</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <button @click="clearAll()" 
                        class="text-sm text-red-600 hover:text-red-800">
                    Clear all
                </button>
                <button @click="viewHistory()" 
                        class="text-sm text-blue-600 hover:text-blue-800">
                    View history
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 space-y-2 z-50">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="max-w-sm bg-white border border-gray-200 rounded-lg shadow-lg p-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <span x-text="getNotificationIcon(toast.type)" class="text-xl"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-900" x-text="toast.title"></h4>
                        <p class="text-sm text-gray-600 mt-1" x-text="toast.message"></p>
                    </div>
                    <button @click="dismissToast(toast)" 
                            class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function notificationCenter() {
    return {
        // State
        showPanel: false,
        showSettings: false,
        activeFilter: 'all',
        notifications: [],
        toasts: [],
        hasNewNotifications: false,
        
        // Settings
        settings: {
            browser_notifications: true,
            sound_alerts: false,
            email_digest: false,
            alert_levels: ['critical', 'error', 'warning']
        },
        
        // Filters
        filters: [
            { id: 'all', name: 'All', count: 0 },
            { id: 'unread', name: 'Unread', count: 0 },
            { id: 'critical', name: 'Critical', count: 0 },
            { id: 'error', name: 'Error', count: 0 },
            { id: 'warning', name: 'Warning', count: 0 }
        ],
        
        alertLevels: [
            { id: 'critical', name: 'Critical' },
            { id: 'error', name: 'Error' },
            { id: 'warning', name: 'Warning' },
            { id: 'info', name: 'Info' }
        ],
        
        // Computed
        get unreadCount() {
            return this.notifications.filter(n => !n.read).length;
        },
        
        get filteredNotifications() {
            let filtered = this.notifications;
            
            switch (this.activeFilter) {
                case 'unread':
                    filtered = filtered.filter(n => !n.read);
                    break;
                case 'critical':
                case 'error':
                case 'warning':
                    filtered = filtered.filter(n => n.level === this.activeFilter);
                    break;
            }
            
            return filtered.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
        },
        
        // Methods
        init() {
            this.loadNotifications();
            this.loadSettings();
            this.updateFilterCounts();
            this.requestNotificationPermission();
            
            // Listen for new notifications from real-time system
            if (window.doctorRealtime) {
                window.doctorRealtime.on('notification', (data) => {
                    this.addNotification(data);
                });
            }
        },
        
        toggleNotificationPanel() {
            this.showPanel = !this.showPanel;
            if (this.showPanel) {
                this.hasNewNotifications = false;
            }
        },
        
        addNotification(data) {
            const notification = {
                id: Date.now() + Math.random(),
                title: data.title || 'Laravel Doctor Alert',
                message: data.message,
                type: data.type || 'info',
                level: data.level || 'info',
                timestamp: new Date().toISOString(),
                read: false,
                actions: data.actions || []
            };
            
            this.notifications.unshift(notification);
            this.hasNewNotifications = true;
            this.updateFilterCounts();
            
            // Show toast notification
            this.showToast(notification);
            
            // Show browser notification if enabled
            if (this.settings.browser_notifications && this.settings.alert_levels.includes(notification.level)) {
                this.showBrowserNotification(notification);
            }
            
            // Play sound if enabled
            if (this.settings.sound_alerts) {
                this.playNotificationSound();
            }
            
            // Save to localStorage
            this.saveNotifications();
        },
        
        markAsRead(notification) {
            notification.read = true;
            this.updateFilterCounts();
            this.saveNotifications();
        },

        markAllAsRead() {
            this.notifications.forEach(n => n.read = true);
            this.updateFilterCounts();
            this.saveNotifications();
        },

        dismissNotification(notification) {
            const index = this.notifications.indexOf(notification);
            if (index > -1) {
                this.notifications.splice(index, 1);
                this.updateFilterCounts();
                this.saveNotifications();
            }
        },

        clearAll() {
            if (confirm('Are you sure you want to clear all notifications?')) {
                this.notifications = [];
                this.updateFilterCounts();
                this.saveNotifications();
            }
        },

        executeAction(notification, action) {
            // Handle notification actions
            switch (action.type) {
                case 'url':
                    window.open(action.url, '_blank');
                    break;
                case 'function':
                    if (window[action.function]) {
                        window[action.function](notification, action);
                    }
                    break;
                case 'api':
                    fetch(action.endpoint, {
                        method: action.method || 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(action.data || {})
                    });
                    break;
            }

            this.markAsRead(notification);
        },

        // Toast notifications
        showToast(notification) {
            const toast = {
                id: Date.now() + Math.random(),
                title: notification.title,
                message: notification.message,
                type: notification.type,
                visible: true
            };

            this.toasts.push(toast);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                this.dismissToast(toast);
            }, 5000);
        },

        dismissToast(toast) {
            toast.visible = false;
            setTimeout(() => {
                const index = this.toasts.indexOf(toast);
                if (index > -1) {
                    this.toasts.splice(index, 1);
                }
            }, 300);
        },

        // Browser notifications
        showBrowserNotification(notification) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const browserNotification = new Notification(notification.title, {
                    body: notification.message,
                    icon: this.getNotificationIconUrl(notification.type),
                    tag: 'laravel-doctor-' + notification.id
                });

                browserNotification.onclick = () => {
                    window.focus();
                    this.showPanel = true;
                    browserNotification.close();
                };

                setTimeout(() => {
                    browserNotification.close();
                }, 10000);
            }
        },

        requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        },

        playNotificationSound() {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Ignore audio play errors
            });
        },

        // Data management
        loadNotifications() {
            const stored = localStorage.getItem('doctor-notifications');
            if (stored) {
                this.notifications = JSON.parse(stored);
            }
        },

        saveNotifications() {
            // Keep only last 100 notifications
            if (this.notifications.length > 100) {
                this.notifications = this.notifications.slice(0, 100);
            }
            localStorage.setItem('doctor-notifications', JSON.stringify(this.notifications));
        },

        loadSettings() {
            const stored = localStorage.getItem('doctor-notification-settings');
            if (stored) {
                this.settings = { ...this.settings, ...JSON.parse(stored) };
            }
        },

        saveSettings() {
            localStorage.setItem('doctor-notification-settings', JSON.stringify(this.settings));
        },

        updateFilterCounts() {
            this.filters.forEach(filter => {
                switch (filter.id) {
                    case 'all':
                        filter.count = this.notifications.length;
                        break;
                    case 'unread':
                        filter.count = this.notifications.filter(n => !n.read).length;
                        break;
                    default:
                        filter.count = this.notifications.filter(n => n.level === filter.id).length;
                }
            });
        },

        viewHistory() {
            // Implement history view
            this.showPanel = false;
            // Trigger history modal or navigate to history page
            if (this.$parent && this.$parent.showHistory) {
                this.$parent.showHistory = true;
            }
        },

        // Utility methods
        getNotificationIcon(type) {
            const icons = {
                'critical': '🚨',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️',
                'success': '✅',
                'security': '🔒',
                'performance': '⚡',
                'maintenance': '🔧'
            };
            return icons[type] || 'ℹ️';
        },

        getNotificationIconUrl(type) {
            const icons = {
                'critical': '/images/icons/critical.png',
                'error': '/images/icons/error.png',
                'warning': '/images/icons/warning.png',
                'info': '/images/icons/info.png',
                'success': '/images/icons/success.png'
            };
            return icons[type] || icons.info;
        },

        getNotificationLevelClass(level) {
            const classes = {
                'critical': 'bg-red-100 text-red-800',
                'error': 'bg-red-100 text-red-800',
                'warning': 'bg-yellow-100 text-yellow-800',
                'info': 'bg-blue-100 text-blue-800',
                'success': 'bg-green-100 text-green-800'
            };
            return classes[level] || 'bg-gray-100 text-gray-800';
        },

        formatTime(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = now - time;

            if (diff < 60000) { // Less than 1 minute
                return 'Just now';
            } else if (diff < 3600000) { // Less than 1 hour
                return Math.floor(diff / 60000) + 'm ago';
            } else if (diff < 86400000) { // Less than 1 day
                return Math.floor(diff / 3600000) + 'h ago';
            } else {
                return time.toLocaleDateString();
            }
        }
    }
}
</script>
