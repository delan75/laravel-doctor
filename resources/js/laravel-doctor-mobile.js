/**
 * Laravel Doctor Mobile Interface
 * 
 * Handles mobile-specific interactions, touch gestures, and responsive behavior
 */

class LaravelDoctorMobile {
    constructor() {
        this.isMobile = window.innerWidth <= 640;
        this.isTablet = window.innerWidth > 640 && window.innerWidth <= 1024;
        this.touchStartY = 0;
        this.touchStartX = 0;
        this.pullThreshold = 100;
        this.swipeThreshold = 50;
        
        this.init();
    }
    
    init() {
        this.setupResponsiveLayout();
        this.setupTouchGestures();
        this.setupPullToRefresh();
        this.setupSwipeActions();
        this.setupMobileNavigation();
        this.setupViewportHandling();
        
        // Listen for orientation changes
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });
        
        // Listen for resize events
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));
    }
    
    setupResponsiveLayout() {
        const body = document.body;
        
        if (this.isMobile) {
            body.classList.add('mobile-layout');
            this.createMobileNavigation();
            this.optimizeForMobile();
        } else if (this.isTablet) {
            body.classList.add('tablet-layout');
            this.optimizeForTablet();
        }
    }
    
    createMobileNavigation() {
        const nav = document.createElement('nav');
        nav.className = 'mobile-nav';
        nav.innerHTML = `
            <div class="flex justify-around">
                <a href="#dashboard" class="mobile-nav-item active" data-tab="dashboard">
                    <div class="mobile-nav-icon">🏠</div>
                    <div>Dashboard</div>
                </a>
                <a href="#results" class="mobile-nav-item" data-tab="results">
                    <div class="mobile-nav-icon">📋</div>
                    <div>Results</div>
                </a>
                <a href="#charts" class="mobile-nav-item" data-tab="charts">
                    <div class="mobile-nav-icon">📊</div>
                    <div>Charts</div>
                </a>
                <a href="#settings" class="mobile-nav-item" data-tab="settings">
                    <div class="mobile-nav-icon">⚙️</div>
                    <div>Settings</div>
                </a>
            </div>
        `;
        
        document.body.appendChild(nav);
        
        // Handle tab switching
        nav.addEventListener('click', (e) => {
            const tabItem = e.target.closest('.mobile-nav-item');
            if (tabItem) {
                e.preventDefault();
                this.switchTab(tabItem.dataset.tab);
                this.updateActiveTab(tabItem);
            }
        });
    }
    
    switchTab(tabName) {
        // Hide all tab content
        document.querySelectorAll('[data-tab-content]').forEach(content => {
            content.style.display = 'none';
        });
        
        // Show selected tab content
        const targetContent = document.querySelector(`[data-tab-content="${tabName}"]`);
        if (targetContent) {
            targetContent.style.display = 'block';
        }
        
        // Trigger tab-specific initialization
        this.initializeTabContent(tabName);
    }
    
    updateActiveTab(activeItem) {
        document.querySelectorAll('.mobile-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        activeItem.classList.add('active');
    }
    
    initializeTabContent(tabName) {
        switch (tabName) {
            case 'charts':
                this.initializeMobileCharts();
                break;
            case 'results':
                this.setupMobileResultsView();
                break;
            case 'settings':
                this.setupMobileSettings();
                break;
        }
    }
    
    setupTouchGestures() {
        let startX, startY, currentX, currentY;
        
        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            this.touchStartX = startX;
            this.touchStartY = startY;
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;
            
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
            
            const diffX = startX - currentX;
            const diffY = startY - currentY;
            
            // Handle horizontal swipes for navigation
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > this.swipeThreshold) {
                if (diffX > 0) {
                    this.handleSwipeLeft();
                } else {
                    this.handleSwipeRight();
                }
            }
        }, { passive: true });
        
        document.addEventListener('touchend', () => {
            startX = null;
            startY = null;
        }, { passive: true });
    }
    
    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let isPulling = false;
        
        const pullIndicator = document.createElement('div');
        pullIndicator.className = 'mobile-pull-indicator';
        pullIndicator.innerHTML = '🔄 Pull to refresh';
        document.body.appendChild(pullIndicator);
        
        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            currentY = e.touches[0].clientY;
            const pullDistance = currentY - startY;
            
            if (pullDistance > 0 && pullDistance < this.pullThreshold * 2) {
                e.preventDefault();
                pullIndicator.style.transform = `translateY(${pullDistance / 2}px)`;
                
                if (pullDistance > this.pullThreshold) {
                    pullIndicator.classList.add('active');
                    pullIndicator.innerHTML = '↻ Release to refresh';
                } else {
                    pullIndicator.classList.remove('active');
                    pullIndicator.innerHTML = '🔄 Pull to refresh';
                }
            }
        });
        
        document.addEventListener('touchend', () => {
            if (isPulling && currentY - startY > this.pullThreshold) {
                this.triggerRefresh();
            }
            
            isPulling = false;
            pullIndicator.style.transform = 'translateY(-100%)';
            pullIndicator.classList.remove('active');
        }, { passive: true });
    }
    
    setupSwipeActions() {
        document.addEventListener('touchstart', (e) => {
            const swipeItem = e.target.closest('.mobile-swipe-item');
            if (swipeItem) {
                this.handleSwipeStart(e, swipeItem);
            }
        });
    }
    
    handleSwipeStart(e, item) {
        let startX = e.touches[0].clientX;
        let currentX = startX;
        
        const handleMove = (e) => {
            currentX = e.touches[0].clientX;
            const diffX = startX - currentX;
            
            if (diffX > this.swipeThreshold) {
                item.classList.add('swiped');
            } else if (diffX < -this.swipeThreshold) {
                item.classList.remove('swiped');
            }
        };
        
        const handleEnd = () => {
            document.removeEventListener('touchmove', handleMove);
            document.removeEventListener('touchend', handleEnd);
        };
        
        document.addEventListener('touchmove', handleMove, { passive: true });
        document.addEventListener('touchend', handleEnd, { passive: true });
    }
    
    setupMobileNavigation() {
        // Handle back button
        window.addEventListener('popstate', (e) => {
            if (this.isMobile) {
                this.handleBackButton(e);
            }
        });
        
        // Handle hardware back button on Android
        document.addEventListener('backbutton', (e) => {
            e.preventDefault();
            this.handleBackButton(e);
        });
    }
    
    setupViewportHandling() {
        // Handle viewport changes for mobile browsers
        const viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport && this.isMobile) {
            const meta = document.createElement('meta');
            meta.name = 'viewport';
            meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            document.head.appendChild(meta);
        }
        
        // Handle iOS Safari viewport issues
        if (this.isIOS()) {
            this.handleIOSViewport();
        }
    }
    
    optimizeForMobile() {
        // Optimize touch targets
        document.querySelectorAll('button, a, input, select').forEach(element => {
            element.classList.add('touch-target');
        });
        
        // Optimize form inputs
        document.querySelectorAll('input').forEach(input => {
            input.classList.add('mobile-input');
        });
        
        // Optimize buttons
        document.querySelectorAll('button').forEach(button => {
            if (!button.classList.contains('mobile-nav-item')) {
                button.classList.add('mobile-button');
            }
        });
    }
    
    optimizeForTablet() {
        // Tablet-specific optimizations
        document.body.classList.add('tablet-optimized');
        
        // Use grid layouts for better space utilization
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('tablet-grid');
        }
    }
    
    initializeMobileCharts() {
        // Optimize charts for mobile viewing
        const charts = document.querySelectorAll('canvas');
        charts.forEach(chart => {
            const container = chart.closest('.chart-container');
            if (container) {
                container.classList.add('mobile-chart-container');
                chart.classList.add('mobile-chart');
            }
        });
    }
    
    setupMobileResultsView() {
        const resultsList = document.querySelector('.results-list');
        if (resultsList) {
            resultsList.classList.add('mobile-results');
            
            // Convert result items to mobile format
            const items = resultsList.querySelectorAll('.result-item');
            items.forEach(item => {
                item.classList.add('mobile-result-item');
                this.convertToMobileResultItem(item);
            });
        }
    }
    
    convertToMobileResultItem(item) {
        // Restructure result item for mobile display
        const icon = item.querySelector('.level-icon');
        const level = item.querySelector('.level-badge');
        const message = item.querySelector('.message');
        const advice = item.querySelector('.advice');
        const timestamp = item.querySelector('.timestamp');
        
        if (icon && level && message) {
            const header = document.createElement('div');
            header.className = 'mobile-result-header';
            header.appendChild(icon);
            header.appendChild(level);
            
            item.insertBefore(header, item.firstChild);
        }
    }
    
    setupMobileSettings() {
        const settingsContainer = document.querySelector('.settings-container');
        if (settingsContainer) {
            // Group settings into mobile-friendly sections
            this.createMobileSettingsSections(settingsContainer);
        }
    }
    
    createMobileSettingsSections(container) {
        const sections = [
            { title: 'General', items: ['auto_refresh', 'notifications', 'theme'] },
            { title: 'Alerts', items: ['email_alerts', 'webhooks'] },
            { title: 'Diagnostics', items: ['checks', 'thresholds'] }
        ];
        
        sections.forEach(section => {
            const sectionElement = document.createElement('div');
            sectionElement.className = 'mobile-settings-section';
            sectionElement.innerHTML = `
                <div class="mobile-settings-header">${section.title}</div>
                <div class="mobile-settings-items"></div>
            `;
            container.appendChild(sectionElement);
        });
    }
    
    // Event Handlers
    handleSwipeLeft() {
        // Navigate to next tab
        const currentTab = document.querySelector('.mobile-nav-item.active');
        const nextTab = currentTab.nextElementSibling;
        if (nextTab) {
            nextTab.click();
        }
    }
    
    handleSwipeRight() {
        // Navigate to previous tab
        const currentTab = document.querySelector('.mobile-nav-item.active');
        const prevTab = currentTab.previousElementSibling;
        if (prevTab) {
            prevTab.click();
        }
    }
    
    handleBackButton(e) {
        // Handle mobile back button navigation
        const activeModal = document.querySelector('.modal:not([style*="display: none"])');
        if (activeModal) {
            activeModal.style.display = 'none';
        } else {
            // Navigate to dashboard
            const dashboardTab = document.querySelector('[data-tab="dashboard"]');
            if (dashboardTab) {
                dashboardTab.click();
            }
        }
    }
    
    handleOrientationChange() {
        // Recalculate layouts after orientation change
        this.isMobile = window.innerWidth <= 640;
        this.isTablet = window.innerWidth > 640 && window.innerWidth <= 1024;
        
        // Trigger chart resize if needed
        if (window.Chart) {
            Object.values(window.Chart.instances).forEach(chart => {
                chart.resize();
            });
        }
        
        // Adjust viewport for iOS
        if (this.isIOS()) {
            this.handleIOSViewport();
        }
    }
    
    handleResize() {
        this.handleOrientationChange();
    }
    
    triggerRefresh() {
        // Trigger diagnostic refresh
        if (window.doctorDashboard && typeof window.doctorDashboard.runDiagnostics === 'function') {
            window.doctorDashboard.runDiagnostics();
        }
        
        // Show refresh feedback
        this.showToast('Refreshing diagnostics...', 'info');
    }
    
    // Utility Methods
    isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent);
    }
    
    handleIOSViewport() {
        // Fix iOS Safari viewport issues
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };
        
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `mobile-toast mobile-toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize mobile interface when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.laravelDoctorMobile = new LaravelDoctorMobile();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LaravelDoctorMobile;
}
