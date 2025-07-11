<!-- Interactive Result Viewer Component -->
<div x-data="resultViewer()" class="bg-white card-shadow rounded-xl">
    
    <!-- Header with Controls -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Diagnostic Results</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Showing <span x-text="filteredResults.length"></span> of <span x-text="results.length"></span> results
                </p>
            </div>
            
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                <!-- Search -->
                <div class="relative">
                    <input x-model="searchQuery" 
                           @input="debounceSearch()"
                           placeholder="Search results..." 
                           class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Level Filter -->
                <select x-model="filterLevel" 
                        @change="applyFilters()"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Levels</option>
                    <option value="critical">🚨 Critical</option>
                    <option value="error">❌ Error</option>
                    <option value="warning">⚠️ Warning</option>
                    <option value="info">ℹ️ Info</option>
                    <option value="ok">✅ OK</option>
                </select>
                
                <!-- Category Filter -->
                <select x-model="filterCategory" 
                        @change="applyFilters()"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    <template x-for="category in categories" :key="category">
                        <option :value="category" x-text="category"></option>
                    </template>
                </select>
                
                <!-- Sort Options -->
                <select x-model="sortBy" 
                        @change="applySorting()"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="timestamp">Sort by Time</option>
                    <option value="level">Sort by Level</option>
                    <option value="message">Sort by Message</option>
                    <option value="category">Sort by Category</option>
                </select>
                
                <!-- View Toggle -->
                <div class="flex border border-gray-300 rounded-lg overflow-hidden">
                    <button @click="viewMode = 'list'" 
                            :class="viewMode === 'list' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-2 text-sm font-medium transition-colors">
                        📋 List
                    </button>
                    <button @click="viewMode = 'grid'" 
                            :class="viewMode === 'grid' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-2 text-sm font-medium border-l border-gray-300 transition-colors">
                        🔲 Grid
                    </button>
                    <button @click="viewMode = 'timeline'" 
                            :class="viewMode === 'timeline' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-2 text-sm font-medium border-l border-gray-300 transition-colors">
                        📅 Timeline
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Active Filters -->
        <div x-show="hasActiveFilters()" class="mt-4 flex flex-wrap gap-2">
            <span class="text-sm text-gray-600">Active filters:</span>
            <template x-for="filter in getActiveFilters()" :key="filter.key">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <span x-text="filter.label"></span>
                    <button @click="removeFilter(filter.key)" class="ml-1 text-blue-600 hover:text-blue-800">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </span>
            </template>
            <button @click="clearAllFilters()" class="text-xs text-gray-500 hover:text-gray-700 underline">
                Clear all
            </button>
        </div>
    </div>
    
    <!-- Results Display -->
    <div class="relative">
        
        <!-- Loading State -->
        <div x-show="isLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
                <p class="text-sm text-gray-600">Loading results...</p>
            </div>
        </div>
        
        <!-- Empty State -->
        <div x-show="!isLoading && filteredResults.length === 0" class="p-12 text-center">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No results found</h3>
            <p class="text-gray-600 mb-4">Try adjusting your search criteria or filters</p>
            <button @click="clearAllFilters()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                Clear Filters
            </button>
        </div>
        
        <!-- List View -->
        <div x-show="!isLoading && viewMode === 'list' && filteredResults.length > 0" class="divide-y divide-gray-200">
            <template x-for="(result, index) in paginatedResults" :key="result.timestamp + index">
                <div class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                     @click="selectResult(result)"
                     :class="selectedResult === result ? 'bg-blue-50 border-l-4 border-blue-500' : ''">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <span x-text="getLevelIcon(result.level)" class="text-2xl"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="getLevelClass(result.level)"
                                      x-text="result.level.toUpperCase()"></span>
                                <span class="text-xs text-gray-500" x-text="getCategory(result.message)"></span>
                                <span class="text-xs text-gray-400" x-text="formatTime(result.timestamp)"></span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 mb-1" x-text="result.message"></h4>
                            <p class="text-sm text-gray-600 line-clamp-2" x-text="result.advice"></p>
                            <div x-show="result.details && Object.keys(result.details).length > 0" class="mt-2">
                                <button @click.stop="toggleDetails(result)" 
                                        class="text-xs text-blue-600 hover:text-blue-800">
                                    <span x-show="!result.showDetails">Show details</span>
                                    <span x-show="result.showDetails">Hide details</span>
                                </button>
                                <div x-show="result.showDetails" x-collapse class="mt-2 p-3 bg-gray-100 rounded-lg">
                                    <pre class="text-xs text-gray-700 whitespace-pre-wrap" x-text="JSON.stringify(result.details, null, 2)"></pre>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button @click.stop="toggleBookmark(result)" 
                                    :class="result.bookmarked ? 'text-yellow-500' : 'text-gray-400'"
                                    class="hover:text-yellow-500 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Grid View -->
        <div x-show="!isLoading && viewMode === 'grid' && filteredResults.length > 0" 
             class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="(result, index) in paginatedResults" :key="result.timestamp + index">
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md cursor-pointer transition-all"
                     @click="selectResult(result)"
                     :class="selectedResult === result ? 'ring-2 ring-blue-500 border-blue-500' : ''">
                    <div class="flex items-start justify-between mb-3">
                        <span x-text="getLevelIcon(result.level)" class="text-2xl"></span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                              :class="getLevelClass(result.level)"
                              x-text="result.level.toUpperCase()"></span>
                    </div>
                    <h4 class="font-medium text-gray-900 mb-2 line-clamp-2" x-text="result.message"></h4>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-3" x-text="result.advice"></p>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span x-text="getCategory(result.message)"></span>
                        <span x-text="formatTime(result.timestamp)"></span>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Timeline View -->
        <div x-show="!isLoading && viewMode === 'timeline' && filteredResults.length > 0" class="p-6">
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                <template x-for="(result, index) in paginatedResults" :key="result.timestamp + index">
                    <div class="relative flex items-start space-x-4 pb-8">
                        <div class="relative flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm"
                                 :class="{
                                     'bg-red-500': result.level === 'critical' || result.level === 'error',
                                     'bg-yellow-500': result.level === 'warning',
                                     'bg-blue-500': result.level === 'info',
                                     'bg-green-500': result.level === 'ok'
                                 }">
                                <span x-text="getLevelIcon(result.level)"></span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0 bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="getLevelClass(result.level)"
                                      x-text="result.level.toUpperCase()"></span>
                                <span class="text-sm text-gray-500" x-text="formatTime(result.timestamp)"></span>
                            </div>
                            <h4 class="font-medium text-gray-900 mb-1" x-text="result.message"></h4>
                            <p class="text-sm text-gray-600" x-text="result.advice"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Pagination -->
        <div x-show="!isLoading && filteredResults.length > 0" class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span x-text="((currentPage - 1) * itemsPerPage) + 1"></span> to 
                    <span x-text="Math.min(currentPage * itemsPerPage, filteredResults.length)"></span> of 
                    <span x-text="filteredResults.length"></span> results
                </div>
                <div class="flex space-x-2">
                    <button @click="previousPage()" 
                            :disabled="currentPage === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                        Previous
                    </button>
                    <template x-for="page in getPageNumbers()" :key="page">
                        <button @click="goToPage(page)" 
                                :class="page === currentPage ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                            <span x-text="page"></span>
                        </button>
                    </template>
                    <button @click="nextPage()" 
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resultViewer() {
    return {
        // Data
        results: [],
        filteredResults: [],
        selectedResult: null,
        
        // Filters
        searchQuery: '',
        filterLevel: '',
        filterCategory: '',
        sortBy: 'timestamp',
        sortDirection: 'desc',
        
        // View
        viewMode: 'list',
        isLoading: false,
        
        // Pagination
        currentPage: 1,
        itemsPerPage: 20,
        
        // Computed
        get categories() {
            const cats = new Set();
            this.results.forEach(result => {
                cats.add(this.getCategory(result.message));
            });
            return Array.from(cats).sort();
        },
        
        get paginatedResults() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredResults.slice(start, end);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredResults.length / this.itemsPerPage);
        },
        
        // Methods
        init() {
            this.loadResults();
        },

        async loadResults() {
            this.isLoading = true;
            try {
                const response = await fetch('/doctor/api');
                const data = await response.json();
                this.results = data.results || [];
                this.applyFilters();
            } catch (error) {
                console.error('Failed to load results:', error);
            } finally {
                this.isLoading = false;
            }
        },

        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.applyFilters();
            }, 300);
        },

        applyFilters() {
            let filtered = [...this.results];

            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(result =>
                    result.message.toLowerCase().includes(query) ||
                    result.advice.toLowerCase().includes(query) ||
                    this.getCategory(result.message).toLowerCase().includes(query)
                );
            }

            // Level filter
            if (this.filterLevel) {
                filtered = filtered.filter(result => result.level === this.filterLevel);
            }

            // Category filter
            if (this.filterCategory) {
                filtered = filtered.filter(result =>
                    this.getCategory(result.message) === this.filterCategory
                );
            }

            this.filteredResults = filtered;
            this.applySorting();
            this.currentPage = 1; // Reset to first page
        },

        applySorting() {
            this.filteredResults.sort((a, b) => {
                let aVal, bVal;

                switch (this.sortBy) {
                    case 'level':
                        const levelOrder = { 'critical': 5, 'error': 4, 'warning': 3, 'info': 2, 'ok': 1 };
                        aVal = levelOrder[a.level] || 0;
                        bVal = levelOrder[b.level] || 0;
                        break;
                    case 'message':
                        aVal = a.message.toLowerCase();
                        bVal = b.message.toLowerCase();
                        break;
                    case 'category':
                        aVal = this.getCategory(a.message);
                        bVal = this.getCategory(b.message);
                        break;
                    case 'timestamp':
                    default:
                        aVal = new Date(a.timestamp);
                        bVal = new Date(b.timestamp);
                        break;
                }

                if (this.sortDirection === 'desc') {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                } else {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                }
            });
        },

        selectResult(result) {
            this.selectedResult = this.selectedResult === result ? null : result;
        },

        toggleDetails(result) {
            result.showDetails = !result.showDetails;
        },

        toggleBookmark(result) {
            result.bookmarked = !result.bookmarked;
            // Save to localStorage
            const bookmarks = JSON.parse(localStorage.getItem('doctor-bookmarks') || '[]');
            if (result.bookmarked) {
                bookmarks.push(result.timestamp);
            } else {
                const index = bookmarks.indexOf(result.timestamp);
                if (index > -1) bookmarks.splice(index, 1);
            }
            localStorage.setItem('doctor-bookmarks', JSON.stringify(bookmarks));
        },

        hasActiveFilters() {
            return this.searchQuery || this.filterLevel || this.filterCategory;
        },

        getActiveFilters() {
            const filters = [];
            if (this.searchQuery) {
                filters.push({ key: 'search', label: `Search: "${this.searchQuery}"` });
            }
            if (this.filterLevel) {
                filters.push({ key: 'level', label: `Level: ${this.filterLevel}` });
            }
            if (this.filterCategory) {
                filters.push({ key: 'category', label: `Category: ${this.filterCategory}` });
            }
            return filters;
        },

        removeFilter(key) {
            switch (key) {
                case 'search':
                    this.searchQuery = '';
                    break;
                case 'level':
                    this.filterLevel = '';
                    break;
                case 'category':
                    this.filterCategory = '';
                    break;
            }
            this.applyFilters();
        },

        clearAllFilters() {
            this.searchQuery = '';
            this.filterLevel = '';
            this.filterCategory = '';
            this.applyFilters();
        },

        // Pagination
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },

        goToPage(page) {
            this.currentPage = page;
        },

        getPageNumbers() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        // Utility methods
        getCategory(message) {
            const categories = {
                'Environment': ['env', 'environment', 'config'],
                'Security': ['security', 'debug', 'production', 'exposed'],
                'Database': ['database', 'connection', 'migration', 'sql'],
                'Cache': ['cache', 'redis'],
                'Queue': ['queue', 'job', 'worker'],
                'Mail': ['mail', 'smtp', 'email'],
                'Filesystem': ['permission', 'directory', 'file'],
                'Code Quality': ['cs fixer', 'phpstan', 'todo', 'debug statement'],
                'Dependencies': ['composer', 'package', 'outdated'],
                'System': ['php', 'laravel', 'version']
            };

            const msgLower = message.toLowerCase();
            for (const [category, keywords] of Object.entries(categories)) {
                if (keywords.some(keyword => msgLower.includes(keyword))) {
                    return category;
                }
            }
            return 'General';
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
