/**
 * Laravel Doctor Real-time Monitoring
 * 
 * Handles WebSocket connections, real-time updates, and progress tracking
 */

class LaravelDoctorRealtime {
    constructor(options = {}) {
        this.options = {
            wsUrl: options.wsUrl || `ws://${window.location.host}/doctor/ws`,
            reconnectInterval: options.reconnectInterval || 5000,
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            ...options
        };
        
        this.ws = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.eventListeners = {};
        this.diagnosticProgress = 0;
        this.currentCheck = '';
        
        this.init();
    }
    
    init() {
        this.connect();
        this.setupHeartbeat();
    }
    
    connect() {
        try {
            this.ws = new WebSocket(this.options.wsUrl);
            
            this.ws.onopen = (event) => {
                console.log('Laravel Doctor WebSocket connected');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.emit('connected', event);
            };
            
            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleMessage(data);
                } catch (error) {
                    console.error('Failed to parse WebSocket message:', error);
                }
            };
            
            this.ws.onclose = (event) => {
                console.log('Laravel Doctor WebSocket disconnected');
                this.isConnected = false;
                this.emit('disconnected', event);
                this.attemptReconnect();
            };
            
            this.ws.onerror = (error) => {
                console.error('Laravel Doctor WebSocket error:', error);
                this.emit('error', error);
            };
            
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.attemptReconnect();
        }
    }
    
    handleMessage(data) {
        switch (data.type) {
            case 'diagnostic_started':
                this.diagnosticProgress = 0;
                this.currentCheck = '';
                this.emit('diagnostic_started', data);
                break;
                
            case 'diagnostic_progress':
                this.diagnosticProgress = data.progress;
                this.currentCheck = data.current_check;
                this.emit('diagnostic_progress', data);
                break;
                
            case 'diagnostic_result':
                this.emit('diagnostic_result', data.result);
                break;
                
            case 'diagnostic_completed':
                this.diagnosticProgress = 100;
                this.emit('diagnostic_completed', data);
                break;
                
            case 'health_score_update':
                this.emit('health_score_update', data);
                break;
                
            case 'critical_issue_detected':
                this.emit('critical_issue_detected', data);
                this.showNotification('Critical Issue Detected', data.message, 'error');
                break;
                
            case 'system_status_change':
                this.emit('system_status_change', data);
                break;
                
            case 'heartbeat':
                this.emit('heartbeat', data);
                break;
                
            default:
                console.log('Unknown message type:', data.type);
        }
    }
    
    attemptReconnect() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.emit('max_reconnect_attempts_reached');
            return;
        }
        
        this.reconnectAttempts++;
        console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.options.maxReconnectAttempts})...`);
        
        setTimeout(() => {
            this.connect();
        }, this.options.reconnectInterval);
    }
    
    setupHeartbeat() {
        setInterval(() => {
            if (this.isConnected) {
                this.send({
                    type: 'heartbeat',
                    timestamp: new Date().toISOString()
                });
            }
        }, 30000); // Send heartbeat every 30 seconds
    }
    
    send(data) {
        if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.warn('WebSocket not connected, cannot send message');
        }
    }
    
    // Event system
    on(event, callback) {
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }
    
    off(event, callback) {
        if (this.eventListeners[event]) {
            this.eventListeners[event] = this.eventListeners[event].filter(cb => cb !== callback);
        }
    }
    
    emit(event, data) {
        if (this.eventListeners[event]) {
            this.eventListeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    }
    
    // Public methods
    startDiagnostics() {
        this.send({
            type: 'start_diagnostics',
            timestamp: new Date().toISOString()
        });
    }
    
    stopDiagnostics() {
        this.send({
            type: 'stop_diagnostics',
            timestamp: new Date().toISOString()
        });
    }
    
    requestSystemStatus() {
        this.send({
            type: 'get_system_status',
            timestamp: new Date().toISOString()
        });
    }
    
    updateSettings(settings) {
        this.send({
            type: 'update_settings',
            settings: settings,
            timestamp: new Date().toISOString()
        });
    }
    
    // Notification system
    showNotification(title, message, type = 'info') {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(title, {
                body: message,
                icon: this.getNotificationIcon(type),
                tag: 'laravel-doctor'
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
            
            setTimeout(() => {
                notification.close();
            }, 5000);
        }
        
        // Also emit as custom event for in-app notifications
        this.emit('notification', { title, message, type });
    }
    
    getNotificationIcon(type) {
        const icons = {
            'error': '/images/error-icon.png',
            'warning': '/images/warning-icon.png',
            'success': '/images/success-icon.png',
            'info': '/images/info-icon.png'
        };
        return icons[type] || icons.info;
    }
    
    // Request notification permission
    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                console.log('Notification permission:', permission);
            });
        }
    }
    
    // Cleanup
    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
    
    // Utility methods
    getConnectionStatus() {
        return {
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            diagnosticProgress: this.diagnosticProgress,
            currentCheck: this.currentCheck
        };
    }
}

// Alpine.js integration
document.addEventListener('alpine:init', () => {
    Alpine.data('realtimeMonitoring', () => ({
        realtime: null,
        connectionStatus: 'disconnected',
        progress: 0,
        currentCheck: '',
        notifications: [],
        
        init() {
            this.initRealtime();
        },
        
        initRealtime() {
            this.realtime = new LaravelDoctorRealtime({
                wsUrl: `ws://${window.location.host}/doctor/ws`
            });
            
            // Connection events
            this.realtime.on('connected', () => {
                this.connectionStatus = 'connected';
                this.addNotification('Connected to real-time monitoring', 'success');
            });
            
            this.realtime.on('disconnected', () => {
                this.connectionStatus = 'disconnected';
                this.addNotification('Disconnected from real-time monitoring', 'warning');
            });
            
            this.realtime.on('error', (error) => {
                this.connectionStatus = 'error';
                this.addNotification('Connection error occurred', 'error');
            });
            
            // Diagnostic events
            this.realtime.on('diagnostic_started', (data) => {
                this.progress = 0;
                this.currentCheck = 'Starting diagnostics...';
                this.addNotification('Diagnostics started', 'info');
            });
            
            this.realtime.on('diagnostic_progress', (data) => {
                this.progress = data.progress;
                this.currentCheck = data.current_check;
            });
            
            this.realtime.on('diagnostic_result', (result) => {
                // Update results in real-time
                if (this.$parent && this.$parent.results) {
                    this.$parent.results.push(result);
                }
            });
            
            this.realtime.on('diagnostic_completed', (data) => {
                this.progress = 100;
                this.currentCheck = 'Completed';
                this.addNotification('Diagnostics completed', 'success');
                
                // Update parent component data
                if (this.$parent) {
                    this.$parent.summary = data.summary;
                    this.$parent.healthScore = data.health_score;
                    this.$parent.isRunning = false;
                }
            });
            
            this.realtime.on('critical_issue_detected', (data) => {
                this.addNotification(`Critical Issue: ${data.message}`, 'error');
            });
            
            this.realtime.on('notification', (notification) => {
                this.addNotification(notification.message, notification.type);
            });
            
            // Request notification permission
            this.realtime.requestNotificationPermission();
        },
        
        addNotification(message, type) {
            const notification = {
                id: Date.now(),
                message,
                type,
                timestamp: new Date().toISOString()
            };
            
            this.notifications.unshift(notification);
            
            // Keep only last 50 notifications
            if (this.notifications.length > 50) {
                this.notifications = this.notifications.slice(0, 50);
            }
            
            // Auto-remove after 5 seconds for non-error notifications
            if (type !== 'error') {
                setTimeout(() => {
                    this.removeNotification(notification.id);
                }, 5000);
            }
        },
        
        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        },
        
        startRealTimeDiagnostics() {
            if (this.realtime) {
                this.realtime.startDiagnostics();
            }
        },
        
        getConnectionStatusClass() {
            const classes = {
                'connected': 'bg-green-500',
                'disconnected': 'bg-red-500',
                'error': 'bg-red-600'
            };
            return classes[this.connectionStatus] || 'bg-gray-500';
        }
    }));
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LaravelDoctorRealtime;
}
