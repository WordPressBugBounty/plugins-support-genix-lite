/**
 * Support Genix - Modern Documentation JavaScript
 * Version: 1.0.0
 * Description: Modern interactions and functionality for documentation system
 */

(function () {
    'use strict';

    /**
     * SGKBModernDocs - Main class for modern documentation functionality
     */
    class SGKBModernDocs {
        constructor() {
            this.config = {
                ajaxUrl: sgkb_docs_config?.ajax_url || '',
                ajaxNonce: sgkb_docs_config?.ajax_nonce || '',
                searchDelay: 300,
                animationDuration: 300,
                scrollOffset: 100
            };

            this.elements = {};
            this.searchDebounce = null;
            this.currentSearchQuery = '';
            this.observer = null;
            this.isSearching = false; // Flag to prevent duplicate searches
            this.isPopularSearchClick = false; // Flag to prevent input event during popular search

            // Bind event handlers to maintain references
            this.boundHandlers = {
                handleSearch: this.handleSearch.bind(this)
            };

            this.init();
        }

        /**
         * Initialize all components
         */
        init() {
            this.cacheElements();
            this.bindEvents();
            this.initSearch();
            this.initAnimations();
            this.initTooltips();
            // this.initAnalytics(); // REMOVED: Orphaned code - no backend handler exists
            this.initThemeToggle();
            this.initScrollEffects();
            this.initCategoryFilters();
            this.initSidebarCategories();
            this.initImageLightbox();
        }

        /**
         * Cache DOM elements
         */
        cacheElements() {
            this.elements = {
                // Search elements
                searchInput: document.querySelector('.sgkb-search-input-modern'),
                searchWrapper: document.querySelector('.sgkb-search-wrapper'),
                searchResults: document.querySelector('.sgkb-search-results-modern'),
                searchIconWrapper: document.querySelector('.sgkb-search-icon-wrapper'),

                // Category elements
                categoryCards: document.querySelectorAll('.sgkb-category-card-modern'),
                categoryGrid: document.querySelector('.sgkb-grid'),

                // Navigation
                breadcrumbs: document.querySelector('.sgkb-breadcrumbs'),
                sidebar: document.querySelector('.sgkb-sidebar'),

                // Theme
                themeToggle: document.querySelector('.sgkb-theme-toggle'),

                // Filters
                filterButtons: document.querySelectorAll('.sgkb-filter-btn'),
                sortDropdown: document.querySelector('.sgkb-sort-dropdown')
            };
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Search events
            if (this.elements.searchInput) {
                this.elements.searchInput.addEventListener('input', this.boundHandlers.handleSearch);
                this.elements.searchInput.addEventListener('focus', this.handleSearchFocus.bind(this));
                this.elements.searchInput.addEventListener('blur', this.handleSearchBlur.bind(this));
            }

            // Click outside to close search results
            document.addEventListener('click', this.handleDocumentClick.bind(this));

            // Category card interactions
            this.elements.categoryCards.forEach(card => {
                card.addEventListener('mouseenter', this.handleCardHover.bind(this));
                card.addEventListener('mouseleave', this.handleCardLeave.bind(this));
            });

            // Filter buttons
            this.elements.filterButtons.forEach(button => {
                button.addEventListener('click', this.handleFilterClick.bind(this));
            });

            // Window resize
            window.addEventListener('resize', this.debounce(this.handleResize.bind(this), 250));

            // Escape key to close modals/search
            document.addEventListener('keydown', this.handleEscapeKey.bind(this));
        }

        /**
         * Initialize search functionality
         */
        initSearch() {
            if (!this.elements.searchInput) return;

            // Add search suggestions container
            const suggestionsHTML = `
                <div class="sgkb-search-suggestions" style="display: none;">
                    <div class="sgkb-search-suggestions-header">
                        <span class="sgkb-search-suggestions-title">Popular Searches</span>
                    </div>
                    <div class="sgkb-search-suggestions-list"></div>
                </div>
            `;

            if (this.elements.searchWrapper) {
                this.elements.searchWrapper.insertAdjacentHTML('afterend', suggestionsHTML);
                this.elements.searchSuggestions = document.querySelector('.sgkb-search-suggestions');
            }
        }

        /**
         * Handle search input
         */
        handleSearch(e) {
            // Skip if this is from a popular search click
            if (this.isPopularSearchClick) {
                return;
            }

            const query = e.target.value.trim();

            // Clear previous timeout
            if (this.searchDebounce) {
                clearTimeout(this.searchDebounce);
            }

            // Remove previous results if query is empty
            if (!query) {
                this.hideSearchResults();
                this.currentSearchQuery = '';
                return;
            }

            // Show loading state
            this.showSearchLoading();

            // Debounce search request
            this.searchDebounce = setTimeout(() => {
                this.performSearch(query);
            }, this.config.searchDelay);
        }

        /**
         * Perform AJAX search
         */
        performSearch(query) {
            // Prevent duplicate searches
            if (query === this.currentSearchQuery || this.isSearching) {
                return;
            }

            this.currentSearchQuery = query;
            this.isSearching = true; // Set flag to prevent duplicate requests

            // Get category if searching within a specific category
            const categorySlug = this.elements.searchInput ? this.elements.searchInput.getAttribute('data-category') : '';

            // Create FormData for POST request
            const formData = new FormData();
            formData.append('action', 'sgkb_search');
            formData.append('_ajax_nonce', this.config.ajaxNonce);
            formData.append('query', query);
            formData.append('apbd_ajax_action', 'knowledge_base_search');
            if (categorySlug) {
                formData.append('category', categorySlug);
            }

            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.displaySearchResults(data.data);
                    } else {
                        this.displayNoResults();
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    this.displaySearchError();
                })
                .finally(() => {
                    this.isSearching = false; // Reset flag after search completes
                    this.hideSearchLoading();
                });
        }

        /**
         * Display search results
         */
        displaySearchResults(html) {
            if (!this.elements.searchResults) return;

            this.elements.searchResults.innerHTML = html;
            this.showSearchResults();
            this.hideSearchLoading(); // Make sure to hide loading

            // Animate results
            const resultItems = this.elements.searchResults.querySelectorAll('.sgkb-search-result-item');
            this.animateElements(resultItems, 'sgkb-animate-fadeInUp', 50);
        }

        /**
         * Display no results message
         */
        displayNoResults() {
            if (!this.elements.searchResults) return;

            const noResultsHTML = `
                <div class="sgkb-search-no-results">
                    <svg class="sgkb-search-no-results-icon" width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M11 6C13.7614 6 16 8.23858 16 11M16.6588 16.6549L21 21M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="sgkb-search-no-results-text">No results found for "<strong>${this.escapeHtml(this.currentSearchQuery)}</strong>"</p>
                    <p class="sgkb-search-no-results-hint">Try searching with different keywords</p>
                </div>
            `;

            this.elements.searchResults.innerHTML = noResultsHTML;
            this.showSearchResults();
            this.hideSearchLoading(); // Make sure to hide loading
        }

        /**
         * Display search error
         */
        displaySearchError() {
            if (!this.elements.searchResults) return;

            const errorHTML = `
                <div class="sgkb-search-error">
                    <p>An error occurred while searching. Please try again.</p>
                </div>
            `;

            this.elements.searchResults.innerHTML = errorHTML;
            this.showSearchResults();
            this.hideSearchLoading(); // Make sure to hide loading
        }

        /**
         * Show search results container
         */
        showSearchResults() {
            if (this.elements.searchResults) {
                this.elements.searchResults.classList.add('sgkb-active');
            }
        }

        /**
         * Hide search results container
         */
        hideSearchResults() {
            if (this.elements.searchResults) {
                this.elements.searchResults.classList.remove('sgkb-active');
            }
        }

        /**
         * Show search loading state
         */
        showSearchLoading() {
            if (this.elements.searchIconWrapper) {
                this.elements.searchIconWrapper.innerHTML = '<div class="sgkb-spinner sgkb-spinner-sm"></div>';
            }
        }

        /**
         * Hide search loading state
         */
        hideSearchLoading() {
            if (this.elements.searchIconWrapper) {
                this.elements.searchIconWrapper.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                `;
            }
        }

        /**
         * Handle search input focus
         */
        handleSearchFocus(e) {
            if (this.elements.searchWrapper) {
                this.elements.searchWrapper.classList.add('sgkb-focused');
            }

            // Show suggestions if available
            if (this.elements.searchSuggestions && !this.currentSearchQuery) {
                this.showSearchSuggestions();
            }
        }

        /**
         * Handle search input blur
         */
        handleSearchBlur(e) {
            setTimeout(() => {
                if (this.elements.searchWrapper) {
                    this.elements.searchWrapper.classList.remove('sgkb-focused');
                }
                this.hideSearchSuggestions();
            }, 200);
        }

        /**
         * Show search suggestions
         */
        showSearchSuggestions() {
            // Load popular searches
            this.loadPopularSearches();
        }

        /**
         * Hide search suggestions
         */
        hideSearchSuggestions() {
            if (this.elements.searchSuggestions) {
                this.elements.searchSuggestions.style.display = 'none';
            }
        }

        /**
         * Load popular searches
         */
        async loadPopularSearches() {
            if (!this.elements.searchSuggestions) return;

            const listElement = this.elements.searchSuggestions.querySelector('.sgkb-search-suggestions-list');
            if (!listElement) return;

            // Show loading state
            listElement.innerHTML = '<div class="sgkb-search-loading">Loading popular searches...</div>';

            try {
                // Fetch popular searches from server
                let popularSearches = [];

                if (typeof sgkb_docs_config !== 'undefined' && sgkb_docs_config.ajax_url) {
                    const response = await fetch(sgkb_docs_config.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'sgkb_get_popular_searches'
                        })
                    });

                    const data = await response.json();
                    if (data.success && Array.isArray(data.data)) {
                        popularSearches = data.data;
                    }
                }

                // Fallback to defaults if no searches fetched
                if (popularSearches.length === 0) {
                    popularSearches = [
                        'Getting started',
                        'Installation',
                        'API documentation',
                        'Troubleshooting'
                    ];
                }

                // Display the searches
                listElement.innerHTML = popularSearches.map(search => `
                    <a href="#" class="sgkb-search-suggestion-item" data-search="${this.escapeHtml(search)}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        ${this.escapeHtml(search)}
                    </a>
                `).join('');

                // Bind click events to suggestions
                listElement.querySelectorAll('.sgkb-search-suggestion-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation(); // Prevent any other handlers

                        const searchTerm = item.getAttribute('data-search');
                        if (this.elements.searchInput && searchTerm && !this.isSearching) {
                            // Hide suggestions immediately
                            this.hideSearchSuggestions();

                            // Clear any existing search debounce first
                            if (this.searchDebounce) {
                                clearTimeout(this.searchDebounce);
                                this.searchDebounce = null;
                            }

                            // Set flag to prevent input event handler from running
                            this.isPopularSearchClick = true;

                            // Set the value
                            this.elements.searchInput.value = searchTerm;

                            // Reset flag after a brief delay to allow normal input events again
                            setTimeout(() => {
                                this.isPopularSearchClick = false;
                            }, 100);

                            // Show loading and perform search immediately
                            this.showSearchLoading();
                            this.performSearch(searchTerm);
                        }

                        // Note: We don't need to track the popular search click separately
                        // because the search itself will be tracked when performSearch is called
                    });
                });

                this.elements.searchSuggestions.style.display = 'block';

            } catch (error) {
                console.error('Error fetching popular searches:', error);
                // Show fallback on error
                listElement.innerHTML = '<div class="sgkb-search-error">Unable to load popular searches</div>';
            }
        }

        /**
         * Initialize animations with Intersection Observer
         */
        initAnimations() {
            const animatedElements = document.querySelectorAll('[data-animate]');

            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const animationClass = entry.target.getAttribute('data-animate');
                            entry.target.classList.add(animationClass);
                            this.observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                animatedElements.forEach(element => {
                    this.observer.observe(element);
                });
            } else {
                // Fallback for browsers without Intersection Observer
                animatedElements.forEach(element => {
                    const animationClass = element.getAttribute('data-animate');
                    element.classList.add(animationClass);
                });
            }
        }

        /**
         * Initialize tooltips
         */
        initTooltips() {
            const tooltipElements = document.querySelectorAll('[data-tooltip]');

            tooltipElements.forEach(element => {
                const tooltipText = element.getAttribute('data-tooltip');
                const tooltipHTML = `<span class="sgkb-tooltip-content">${this.escapeHtml(tooltipText)}</span>`;

                element.classList.add('sgkb-tooltip');
                element.insertAdjacentHTML('beforeend', tooltipHTML);
            });
        }

        /**
         * REMOVED: initAnalytics() and trackEvent() methods
         *
         * These methods were orphaned code - the backend handler 'sgkb_track_event'
         * was never implemented. All analytics are already tracked server-side via:
         * - Article views: update_analytics_data() in wp_head hook
         * - Search queries: update_searches_data() via search action
         * - Reactions: sgkb_reaction AJAX handler
         *
         * Removing this code eliminates failed AJAX requests and improves performance.
         */

        /**
         * Initialize theme toggle
         */
        initThemeToggle() {
            if (!this.elements.themeToggle) return;

            // Check for saved theme preference
            const savedTheme = localStorage.getItem('sgkb-theme') || 'light';
            document.documentElement.setAttribute('data-sgkb-theme', savedTheme);

            this.elements.themeToggle.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-sgkb-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                document.documentElement.setAttribute('data-sgkb-theme', newTheme);
                localStorage.setItem('sgkb-theme', newTheme);

                // Update toggle button icon
                this.updateThemeToggleIcon(newTheme);
            });
        }

        /**
         * Update theme toggle icon
         */
        updateThemeToggleIcon(theme) {
            if (!this.elements.themeToggle) return;

            const iconHTML = theme === 'dark' ?
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3V4M12 20V21M4 12H3M6.31412 6.31412L5.5 5.5M17.6859 6.31412L18.5 5.5M6.31412 17.69L5.5 18.5001M17.6859 17.69L18.5 18.5001M21 12H20M16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' :
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            this.elements.themeToggle.innerHTML = iconHTML;
        }

        /**
         * Initialize scroll effects
         */
        initScrollEffects() {
            let lastScrollTop = 0;
            const header = document.querySelector('.sgkb-header');

            window.addEventListener('scroll', () => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                // Hide/show header on scroll
                if (header) {
                    if (scrollTop > lastScrollTop && scrollTop > this.config.scrollOffset) {
                        header.classList.add('sgkb-header-hidden');
                    } else {
                        header.classList.remove('sgkb-header-hidden');
                    }
                }

                // Update progress bar (if on single article page)
                this.updateReadingProgress(scrollTop);

                lastScrollTop = scrollTop;
            });
        }

        /**
         * Update reading progress bar
         */
        updateReadingProgress(scrollTop) {
            const progressBar = document.querySelector('.sgkb-reading-progress');
            if (!progressBar) return;

            const articleContent = document.querySelector('.sgkb-article-content');
            if (!articleContent) return;

            const articleHeight = articleContent.offsetHeight;
            const windowHeight = window.innerHeight;
            const articleTop = articleContent.offsetTop;

            const progress = Math.min(100, Math.max(0,
                ((scrollTop - articleTop + windowHeight) / articleHeight) * 100
            ));

            progressBar.style.width = `${progress}%`;
        }

        /**
         * Initialize category filters
         */
        initCategoryFilters() {
            this.elements.filterButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleFilterClick(e);
                });
            });

            // Sort dropdown
            if (this.elements.sortDropdown) {
                this.elements.sortDropdown.addEventListener('change', (e) => {
                    this.handleSortChange(e.target.value);
                });
            }
        }

        /**
         * Initialize expandable sidebar categories (accordion behavior)
         */
        initSidebarCategories() {
            const categoryHeaders = document.querySelectorAll('.sgkb-sidebar-category-header');

            categoryHeaders.forEach(header => {
                header.addEventListener('click', (e) => {
                    e.preventDefault();
                    const category = header.closest('.sgkb-sidebar-category');
                    const content = category.querySelector('.sgkb-sidebar-category-content');
                    const isExpanded = category.classList.contains('sgkb-expanded');

                    // Collapse all other categories first (accordion behavior)
                    document.querySelectorAll('.sgkb-sidebar-category.sgkb-expanded').forEach(openCat => {
                        if (openCat !== category) {
                            openCat.classList.remove('sgkb-expanded');
                            openCat.querySelector('.sgkb-sidebar-category-header').setAttribute('aria-expanded', 'false');
                            openCat.querySelector('.sgkb-sidebar-category-content').style.display = 'none';
                        }
                    });

                    // Toggle clicked category
                    if (isExpanded) {
                        // Collapse
                        category.classList.remove('sgkb-expanded');
                        header.setAttribute('aria-expanded', 'false');
                        content.style.display = 'none';
                    } else {
                        // Expand
                        category.classList.add('sgkb-expanded');
                        header.setAttribute('aria-expanded', 'true');
                        content.style.display = 'flex';
                    }
                });
            });
        }

        /**
         * Initialize image lightbox for article content using GLightbox
         */
        initImageLightbox() {
            const contentWrapper = document.querySelector('.sgkb-content-wrapper[data-lightbox="true"]');
            if (!contentWrapper) return;

            // Check if GLightbox is available
            if (typeof GLightbox === 'undefined') return;

            // Add glightbox class to all images in content wrapper
            const images = contentWrapper.querySelectorAll('img');
            images.forEach((img, index) => {
                const parent = img.closest('figure');

                // Skip if inside a figure with class wp-lightbox-container
                if (parent && parent.classList.contains('wp-lightbox-container')) {
                    return;
                }

                // Skip if image is already wrapped in a link
                if (img.parentElement.tagName === 'A') return;

                // Create a wrapper link for GLightbox
                const link = document.createElement('a');
                link.href = img.src;
                link.className = 'glightbox';
                link.setAttribute('data-gallery', `image-${index}`);
                link.setAttribute('data-glightbox', '');

                // Wrap image with link
                img.parentNode.insertBefore(link, img);
                link.appendChild(img);
            });

            // Initialize GLightbox
            GLightbox({
                selector: '.glightbox',
                touchNavigation: false,
                loop: false,
                zoomable: true,
                draggable: false,
                openEffect: 'zoom',
                closeEffect: 'zoom',
                cssEfects: {
                    fade: { in: 'fadeIn', out: 'fadeOut' },
                    zoom: { in: 'zoomIn', out: 'zoomOut' }
                }
            });
        }

        /**
         * Handle filter button click
         */
        handleFilterClick(e) {
            const button = e.currentTarget;
            const filter = button.getAttribute('data-filter');

            // Update active state
            this.elements.filterButtons.forEach(btn => {
                btn.classList.remove('sgkb-active');
            });
            button.classList.add('sgkb-active');

            // Apply filter
            this.filterCategories(filter);
        }

        /**
         * Filter categories
         */
        filterCategories(filter) {
            const cards = document.querySelectorAll('.sgkb-category-card-modern');

            cards.forEach((card, index) => {
                const category = card.getAttribute('data-category-type') || 'all';

                if (filter === 'all' || category === filter) {
                    card.style.display = '';
                    setTimeout(() => {
                        card.classList.add('sgkb-animate-fadeInUp');
                    }, index * 50);
                } else {
                    card.style.display = 'none';
                    card.classList.remove('sgkb-animate-fadeInUp');
                }
            });
        }

        /**
         * Handle sort change
         */
        handleSortChange(sortBy) {
            const grid = this.elements.categoryGrid;
            if (!grid) return;

            const cards = Array.from(grid.querySelectorAll('.sgkb-category-card-modern'));

            cards.sort((a, b) => {
                switch (sortBy) {
                    case 'name':
                        const nameA = a.querySelector('.sgkb-category-title').textContent;
                        const nameB = b.querySelector('.sgkb-category-title').textContent;
                        return nameA.localeCompare(nameB);

                    case 'count':
                        const countA = parseInt(a.getAttribute('data-count') || 0);
                        const countB = parseInt(b.getAttribute('data-count') || 0);
                        return countB - countA;

                    case 'recent':
                        const dateA = new Date(a.getAttribute('data-updated') || 0);
                        const dateB = new Date(b.getAttribute('data-updated') || 0);
                        return dateB - dateA;

                    default:
                        return 0;
                }
            });

            // Re-append sorted cards
            cards.forEach(card => {
                grid.appendChild(card);
            });

            // Animate
            this.animateElements(cards, 'sgkb-animate-fadeInUp', 50);
        }

        /**
         * Handle card hover
         */
        handleCardHover(e) {
            const card = e.currentTarget;
            card.classList.add('sgkb-hovering');
        }

        /**
         * Handle card leave
         */
        handleCardLeave(e) {
            const card = e.currentTarget;
            card.classList.remove('sgkb-hovering');
        }

        /**
         * Handle document click
         */
        handleDocumentClick(e) {
            // Close search results if clicked outside
            if (this.elements.searchWrapper && !this.elements.searchWrapper.contains(e.target)) {
                this.hideSearchResults();
            }
        }

        /**
         * Handle escape key
         */
        handleEscapeKey(e) {
            if (e.key === 'Escape') {
                this.hideSearchResults();
                this.hideSearchSuggestions();

                // Clear search input
                if (this.elements.searchInput) {
                    this.elements.searchInput.value = '';
                    this.currentSearchQuery = '';
                }
            }
        }

        /**
         * Handle window resize
         */
        handleResize() {
            // Recalculate grid layouts if needed
            this.updateGridLayout();
        }

        /**
         * Update grid layout
         */
        updateGridLayout() {
            // Skip updating grid layout - let CSS handle it
            // The grid layout is now controlled by CSS media queries and custom classes
            // This prevents JavaScript from overriding our custom grid classes
            return;
        }

        /**
         * Animate elements sequentially
         */
        animateElements(elements, animationClass, delay = 100) {
            elements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add(animationClass);
                }, index * delay);
            });
        }

        /**
         * Debounce function
         */
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

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    }

    /**
     * Article Page Handler Class
     */
    class SGKBArticlePage {
        constructor() {
            this.init();
        }

        init() {
            this.initTableOfContents();
            this.initArticleActions();
            this.initFeedbackButtons();
        }

        /**
         * Initialize Table of Contents
         */
        initTableOfContents() {
            const tocLinks = document.querySelectorAll('.sgkb-toc-link');
            if (!tocLinks.length) return;

            // Smooth scroll to sections
            tocLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Highlight active section on scroll
            const observerOptions = {
                rootMargin: '-20% 0px -70% 0px'
            };

            const observerCallback = (entries) => {
                entries.forEach(entry => {
                    const id = entry.target.getAttribute('id');
                    const tocLink = document.querySelector(`.sgkb-toc-link[href="#${id}"]`);

                    if (tocLink) {
                        if (entry.isIntersecting) {
                            // Remove active from all links
                            tocLinks.forEach(link => link.classList.remove('active'));
                            // Add active to current link
                            tocLink.classList.add('active');
                        }
                    }
                });
            };

            const observer = new IntersectionObserver(observerCallback, observerOptions);

            // Observe all sections with IDs
            tocLinks.forEach(link => {
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    observer.observe(targetElement);
                }
            });
        }

        /**
         * Initialize Article Actions (Print, Share)
         */
        initArticleActions() {
            // Print button
            const printBtn = document.querySelector('.sgkb-print-btn');
            if (printBtn) {
                printBtn.addEventListener('click', () => {
                    window.print();
                });
            }

            // Share button
            const shareBtn = document.querySelector('.sgkb-share-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', async () => {
                    if (navigator.share) {
                        try {
                            await navigator.share({
                                title: document.title,
                                url: window.location.href
                            });
                        } catch (err) {
                            // User cancelled or error
                            this.copyToClipboard(window.location.href);
                        }
                    } else {
                        // Fallback: copy to clipboard
                        this.copyToClipboard(window.location.href);
                    }
                });
            }
        }

        /**
         * Initialize Feedback Buttons
         */
        initFeedbackButtons() {
            const feedbackBtns = document.querySelectorAll('.sgkb-feedback-btn');

            feedbackBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const articleId = btn.dataset.article;
                    const feedbackType = btn.dataset.type;

                    // Remove active from all buttons
                    feedbackBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    btn.classList.add('active');

                    // Send feedback to server
                    this.sendFeedback(articleId, feedbackType);
                });
            });
        }

        /**
         * Send feedback to server
         */
        sendFeedback(articleId, type) {
            const formData = new FormData();
            formData.append('action', 'sgkb_article_feedback');
            formData.append('article_id', articleId);
            formData.append('feedback_type', type);
            formData.append('nonce', sgkb_docs_config.ajax_nonce);

            fetch(sgkb_docs_config.ajax_url, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message - disabled
                        // this.showFeedbackMessage('Thank you for your feedback!');
                    }
                })
                .catch(error => {
                    console.error('Feedback error:', error);
                });
        }

        /**
         * Show feedback message - DISABLED
         */
        // showFeedbackMessage(message) {
        //     const feedbackContainer = document.querySelector('.sgkb-article-feedback');
        //     if (!feedbackContainer) return;

        //     const messageEl = document.createElement('div');
        //     messageEl.className = 'sgkb-feedback-message';
        //     messageEl.textContent = message;

        //     feedbackContainer.appendChild(messageEl);

        //     setTimeout(() => {
        //         messageEl.remove();
        //     }, 3000);
        // }

        /**
         * Copy text to clipboard
         */
        copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            // Show copied message
            this.showCopiedMessage();
        }

        /**
         * Show copied message
         */
        showCopiedMessage() {
            const shareBtn = document.querySelector('.sgkb-share-btn');
            if (!shareBtn) return;

            const originalText = shareBtn.querySelector('span').textContent;
            shareBtn.querySelector('span').textContent = 'Link Copied!';

            setTimeout(() => {
                shareBtn.querySelector('span').textContent = originalText;
            }, 2000);
        }
    }

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.sgkbModernDocs = new SGKBModernDocs();

            // Initialize article page functionality if on article page
            if (document.querySelector('.sgkb-article-modern')) {
                window.sgkbArticlePage = new SGKBArticlePage();
            }
        });
    } else {
        window.sgkbModernDocs = new SGKBModernDocs();

        // Initialize article page functionality if on article page
        if (document.querySelector('.sgkb-article-modern')) {
            window.sgkbArticlePage = new SGKBArticlePage();
        }
    }

})();