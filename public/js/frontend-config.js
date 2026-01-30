/**
 * Frontend Configuration Manager
 * Loads and manages dynamic configurations from backend API
 *
 * Usage:
 *   await window.FrontendConfig.load();
 *   if (window.FrontendConfig.isEnabled('show_banner')) { ... }
 *   const text = window.FrontendConfig.get('header_text', 'Default');
 *
 * @version 1.0.0
 */
class FrontendConfigManager {
    constructor() {
        this.configs = {};
        this.loaded = false;
        this.loading = false;
        this.apiUrl = '/api/frontend-config';
        this.cacheKey = 'frontend_configs_cache';
        this.cacheTimeKey = 'frontend_configs_cache_time';
        this.cacheDuration = 3600000; // 1 hour in milliseconds
    }

    /**
     * Load configurations from API or cache
     * @returns {Promise<Object>} Configuration object
     */
    async load() {
        // Prevent multiple simultaneous loads
        if (this.loading) {
            console.log('⏳ Config load already in progress...');
            return this.waitForLoad();
        }

        // Return if already loaded
        if (this.loaded) {
            console.log('✅ Configs already loaded');
            return this.configs;
        }

        this.loading = true;

        try {
            // Try loading from cache first
            const cached = this.loadFromCache();
            if (cached) {
                console.log('💾 Using cached configs');
                this.configs = cached;
                this.loaded = true;
                this.loading = false;
                this.triggerLoadedEvent();

                // Fetch fresh data in background
                this.refreshInBackground();

                return this.configs;
            }

            // Load from API
            console.log('🌐 Fetching configs from API...');
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'public, max-age=3600'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            this.configs = await response.json();
            this.loaded = true;
            this.loading = false;

            // Save to cache
            this.saveToCache(this.configs);

            console.log('✅ Frontend configs loaded:', this.configs);
            this.triggerLoadedEvent();

            return this.configs;
        } catch (error) {
            console.error('❌ Error loading frontend configs:', error);
            this.configs = this.getDefaultConfigs();
            this.loaded = true;
            this.loading = false;
            return this.configs;
        }
    }

    /**
     * Refresh configs in background (non-blocking)
     */
    async refreshInBackground() {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const freshConfigs = await response.json();
                if (JSON.stringify(freshConfigs) !== JSON.stringify(this.configs)) {
                    console.log('🔄 Configs updated in background');
                    this.configs = freshConfigs;
                    this.saveToCache(freshConfigs);
                    this.triggerLoadedEvent();
                }
            }
        } catch (error) {
            console.warn('Background refresh failed:', error);
        }
    }

    /**
     * Wait for ongoing load to complete
     */
    async waitForLoad() {
        return new Promise((resolve) => {
            const checkInterval = setInterval(() => {
                if (!this.loading) {
                    clearInterval(checkInterval);
                    resolve(this.configs);
                }
            }, 100);
        });
    }

    /**
     * Get config value by key
     * @param {string} key - Config key
     * @param {*} defaultValue - Default value if not found
     * @returns {*} Config value
     */
    get(key, defaultValue = null) {
        if (!this.loaded) {
            console.warn('⚠️ Configs not loaded yet. Call load() first or use event listener.');
        }
        return this.configs[key] ?? defaultValue;
    }

    /**
     * Check if a boolean config is enabled
     * @param {string} key - Config key
     * @returns {boolean}
     */
    isEnabled(key) {
        return this.get(key) === true;
    }

    /**
     * Check if a feature flag is enabled
     * @param {string} feature - Feature name
     * @returns {boolean}
     */
    hasFeature(feature) {
        const features = this.get('feature_flags', {});
        return features[feature] === true;
    }

    /**
     * Get all configurations
     * @returns {Object}
     */
    getAll() {
        return { ...this.configs };
    }

    /**
     * Force reload configs from API (bypass cache)
     * @returns {Promise<Object>}
     */
    async reload() {
        this.clearCache();
        this.loaded = false;
        this.loading = false;
        return this.load();
    }

    /**
     * Load configs from localStorage cache
     * @returns {Object|null}
     */
    loadFromCache() {
        try {
            const cachedData = localStorage.getItem(this.cacheKey);
            const cacheTime = localStorage.getItem(this.cacheTimeKey);

            if (!cachedData || !cacheTime) {
                return null;
            }

            const age = Date.now() - parseInt(cacheTime);
            if (age > this.cacheDuration) {
                this.clearCache();
                return null;
            }

            return JSON.parse(cachedData);
        } catch (error) {
            console.warn('Cache read error:', error);
            this.clearCache();
            return null;
        }
    }

    /**
     * Save configs to localStorage cache
     * @param {Object} data
     */
    saveToCache(data) {
        try {
            localStorage.setItem(this.cacheKey, JSON.stringify(data));
            localStorage.setItem(this.cacheTimeKey, Date.now().toString());
        } catch (error) {
            console.warn('Cache write error:', error);
        }
    }

    /**
     * Clear localStorage cache
     */
    clearCache() {
        try {
            localStorage.removeItem(this.cacheKey);
            localStorage.removeItem(this.cacheTimeKey);
        } catch (error) {
            console.warn('Cache clear error:', error);
        }
    }

    /**
     * Trigger custom event when configs are loaded
     */
    triggerLoadedEvent() {
        window.dispatchEvent(new CustomEvent('frontend-config-loaded', {
            detail: this.configs
        }));
    }

    /**
     * Default configurations (fallback)
     * @returns {Object}
     */
    getDefaultConfigs() {
        return {
            maintenance_mode: false,
            show_banner: false,
            enable_new_chat_ui: true,
            header_text: 'Welcome',
            banner_message: '',
            maintenance_message: 'System is under maintenance',
            feature_flags: {
                voice_input: true,
                pdf_upload: true,
                image_analysis: true,
                share_chat: true
            },
            theme_colors: {
                primary: '#0df259',
                secondary: '#06b6d4',
                accent: '#0df259'
            }
        };
    }
}

// Create global instance
window.FrontendConfig = new FrontendConfigManager();

// Auto-load on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
        await window.FrontendConfig.load();
    });
} else {
    // DOM already loaded
    window.FrontendConfig.load();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FrontendConfigManager;
}
