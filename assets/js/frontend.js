/**
 * WP Bottom Navigation Pro - Frontend JavaScript
 */

(function($) {
    'use strict';

    var WPBNP_Frontend = {
        
        settings: {},
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            // Get settings from localized data
            if (typeof wpbnp_frontend !== 'undefined' && wpbnp_frontend.settings) {
                this.settings = wpbnp_frontend.settings;
            }
            
            // Check if navigation exists
            if ($('.wpbnp-nav-item').length === 0) {
                return;
            }
            
            this.bindEvents();
            this.updateBadges();
            this.addRippleEffect();
            this.initializeAnimations();
            
            // Update badges periodically if WooCommerce is active
            if (typeof wc_cart_fragments_params !== 'undefined') {
                setInterval(this.updateBadges.bind(this), 30000); // Every 30 seconds
            }
            
            // Add touch support improvements
            this.enhanceTouchSupport();
            
            // Initialize visibility based on device settings
            this.handleDeviceVisibility();
        },
        
        /**
         * Initialize animations based on settings
         */
        initializeAnimations: function() {
            if (!this.settings.animations || !this.settings.animations.enabled) {
                return;
            }
            
            const animationType = this.settings.animations.type;
            const duration = this.settings.animations.duration || 300;
            
            // Add animation classes to navigation items
            $('.wpbnp-nav-item').each(function() {
                $(this).addClass('wpbnp-animated').data('animation-type', animationType);
            });
            
            // Apply animation-specific classes
            this.applyAnimationClasses(animationType, duration);
        },
        
        /**
         * Apply animation-specific CSS classes
         */
        applyAnimationClasses: function(type, duration) {
            const $nav = $('.wpbnp-bottom-nav');
            const $items = $('.wpbnp-nav-item');
            
            // Remove existing animation classes
            $nav.removeClass(function(index, className) {
                return (className.match(/(^|\s)wpbnp-anim-\S+/g) || []).join(' ');
            });
            
            // Add animation type class
            $nav.addClass(`wpbnp-anim-${type}`);
            
            // Set animation duration
            $items.css('transition-duration', duration + 'ms');
        },
        
        /**
         * Handle device-specific visibility
         */
        handleDeviceVisibility: function() {
            if (!this.settings.devices) {
                return;
            }
            
            const updateVisibility = () => {
                const windowWidth = $(window).width();
                const devices = this.settings.devices;
                let shouldShow = false;
                
                // Check mobile
                if (windowWidth <= devices.mobile.breakpoint && devices.mobile.enabled) {
                    shouldShow = true;
                }
                // Check tablet
                else if (windowWidth <= devices.tablet.breakpoint && windowWidth > devices.mobile.breakpoint && devices.tablet.enabled) {
                    shouldShow = true;
                }
                // Check desktop
                else if (windowWidth > devices.tablet.breakpoint && devices.desktop.enabled) {
                    shouldShow = true;
                }
                
                $('.wpbnp-bottom-nav').toggle(shouldShow);
            };
            
            // Check on load and resize
            updateVisibility();
            $(window).on('resize', updateVisibility);
        },
        
        /**
         * Setup keyboard navigation
         */
        setupKeyboardNavigation: function() {
            // Make navigation items focusable
            $('.wpbnp-nav-item').attr('tabindex', '0');
            
            // Add keyboard navigation styles
            $('.wpbnp-nav-item').on('focus', function() {
                $(this).addClass('wpbnp-focused');
            }).on('blur', function() {
                $(this).removeClass('wpbnp-focused');
            });
            
            // Handle keyboard events
            $('.wpbnp-nav-item').on('keydown', this.handleKeyDown);
        },
        
        /**
         * Enhanced touch support
         */
        enhanceTouchSupport: function() {
            if (!('ontouchstart' in window)) {
                return;
            }
            
            let touchStartTime = 0;
            
            $('.wpbnp-nav-item').on('touchstart', function(e) {
                touchStartTime = Date.now();
                $(this).addClass('wpbnp-touching');
                
                // Prevent default touch behavior for better UX
                e.preventDefault();
            });
            
            $('.wpbnp-nav-item').on('touchend', function(e) {
                const $item = $(this);
                const touchDuration = Date.now() - touchStartTime;
                
                // Only trigger click if it was a quick touch (not a scroll)
                if (touchDuration < 500) {
                    setTimeout(() => {
                        $item.click();
                    }, 10);
                }
                
                setTimeout(() => {
                    $item.removeClass('wpbnp-touching');
                }, 150);
            });
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Handle navigation item clicks
            $('.wpbnp-nav-item').on('click', this.handleItemClick.bind(this));
            
            // Setup keyboard navigation
            this.setupKeyboardNavigation();
            
            // Update cart badge when WooCommerce fragments update
            $(document.body).on('wc_fragments_refreshed', function() {
                self.updateBadges();
            });
            
            // Handle active state based on current page
            this.setActiveItem();
            
            // Handle orientation change for mobile devices
            $(window).on('orientationchange', function() {
                setTimeout(() => {
                    self.handleDeviceVisibility();
                }, 100);
            });
        },
        
        /**
         * Handle navigation item clicks
         */
        handleItemClick: function(e) {
            var $item = $(this);
            var href = $item.attr('href');
            
            // Add click animation
            this.addClickAnimation($item);
            
            // Handle special URLs
            if (href === '#' || href === '') {
                e.preventDefault();
                return;
            }
            
            // Update active state
            $('.wpbnp-nav-item').removeClass('active');
            $item.addClass('active');
            
            // Add loading state for navigation
            $item.addClass('wpbnp-loading');
            
            // Trigger custom event for developers
            $(document).trigger('wpbnp_item_clicked', [$item, href]);
            
            // Handle external links
            if (href.startsWith('http') && !href.includes(window.location.hostname)) {
                window.open(href, '_blank');
                e.preventDefault();
                $item.removeClass('wpbnp-loading');
            }
        },
        
        /**
         * Add click animation based on animation type
         */
        addClickAnimation: function($item) {
            if (!this.settings.animations || !this.settings.animations.enabled) {
                return;
            }
            
            const animationType = this.settings.animations.type;
            const duration = this.settings.animations.duration || 300;
            
            // Remove existing animation classes
            $item.removeClass(function(index, className) {
                return (className.match(/(^|\s)wpbnp-click-\S+/g) || []).join(' ');
            });
            
            // Add click animation class
            $item.addClass(`wpbnp-click-${animationType}`);
            
            // Remove animation class after duration
            setTimeout(() => {
                $item.removeClass(`wpbnp-click-${animationType}`);
            }, duration);
        },
        
        /**
         * Handle keyboard navigation
         */
        handleKeyDown: function(e) {
            var $current = $(this);
            var $items = $('.wpbnp-nav-item');
            var currentIndex = $items.index($current);
            
            switch (e.keyCode) {
                case 37: // Left arrow
                    e.preventDefault();
                    var prevIndex = currentIndex > 0 ? currentIndex - 1 : $items.length - 1;
                    $items.eq(prevIndex).focus();
                    break;
                    
                case 39: // Right arrow
                    e.preventDefault();
                    var nextIndex = currentIndex < $items.length - 1 ? currentIndex + 1 : 0;
                    $items.eq(nextIndex).focus();
                    break;
                    
                case 13: // Enter
                case 32: // Space
                    e.preventDefault();
                    $current.trigger('click');
                    break;
                    
                case 27: // Escape
                    $current.blur();
                    break;
            }
        },
        
        /**
         * Set active navigation item based on current page
         */
        setActiveItem: function() {
            const currentUrl = window.location.href;
            const currentPath = window.location.pathname;
            
            $('.wpbnp-nav-item').each(function() {
                const $item = $(this);
                const href = $item.attr('href');
                
                if (href && href !== '#') {
                    // Check for exact match or path match
                    if (href === currentUrl || href === currentPath || 
                        (currentPath === '/' && href === window.location.origin)) {
                        $item.addClass('active');
                    }
                }
            });
        },
        
        /**
         * Add ripple effect to navigation items
         */
        addRippleEffect: function() {
            $('.wpbnp-nav-item').on('click touchstart', function(e) {
                var $item = $(this);
                
                // Only add ripple if animations are enabled
                if (!WPBNP_Frontend.settings.animations || !WPBNP_Frontend.settings.animations.enabled) {
                    return;
                }
                
                var $ripple = $('<span class="wpbnp-ripple"></span>');
                
                // Remove existing ripples
                $item.find('.wpbnp-ripple').remove();
                
                // Calculate ripple position
                var rect = this.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height);
                var x, y;
                
                if (e.type === 'touchstart' && e.originalEvent.touches) {
                    x = e.originalEvent.touches[0].clientX - rect.left - size / 2;
                    y = e.originalEvent.touches[0].clientY - rect.top - size / 2;
                } else {
                    x = e.clientX - rect.left - size / 2;
                    y = e.clientY - rect.top - size / 2;
                }
                
                // Set ripple styles
                $ripple.css({
                    width: size + 'px',
                    height: size + 'px',
                    left: x + 'px',
                    top: y + 'px'
                });
                
                // Add ripple to item
                $item.append($ripple);
                
                // Remove ripple after animation
                setTimeout(() => {
                    $ripple.remove();
                }, 600);
            });
        },
        
        /**
         * Update notification badges
         */
        updateBadges: function() {
            if (!this.settings.badges || !this.settings.badges.enabled) {
                return;
            }
            
            $('.wpbnp-nav-item').each(function() {
                const $item = $(this);
                const itemId = $item.data('item-id');
                
                if (itemId) {
                    WPBNP_Frontend.getBadgeCount(itemId, function(count) {
                        const $badge = $item.find('.wpbnp-badge');
                        
                        if (count > 0) {
                            const displayCount = count > 99 ? '99+' : count;
                            if ($badge.length) {
                                $badge.text(displayCount);
                            } else {
                                // Create new badge
                                const badgeHtml = `<span class="wpbnp-badge">${displayCount}</span>`;
                                $item.find('.wpbnp-nav-icon').append(badgeHtml);
                            }
                            $badge.show();
                        } else {
                            $badge.hide();
                        }
                    });
                }
            });
        },
        
        /**
         * Get badge count for specific item
         */
        getBadgeCount: function(itemId, callback) {
            // Handle built-in badge types
            switch (itemId) {
                case 'cart':
                case 'shop':
                    if (typeof wc_cart_fragments_params !== 'undefined' && window.wp && window.wp.hooks) {
                        // Use WooCommerce hooks if available
                        const count = this.getWooCommerceCartCount();
                        callback(count);
                    } else {
                        // Fallback AJAX call
                        this.fetchCartCount(callback);
                    }
                    break;
                    
                default:
                    // Allow custom badge counts via hooks
                    const customCount = $(document).triggerHandler('wpbnp_get_badge_count', [itemId]);
                    callback(customCount || 0);
                    break;
            }
        },
        
        /**
         * Get WooCommerce cart count
         */
        getWooCommerceCartCount: function() {
            try {
                const cartFragments = sessionStorage.getItem('wc_fragments');
                if (cartFragments) {
                    const fragments = JSON.parse(cartFragments);
                    const cartCountElement = fragments['span.woocommerce-cart-contents-count'];
                    if (cartCountElement) {
                        const count = parseInt($(cartCountElement).text()) || 0;
                        return count;
                    }
                }
            } catch (e) {
                console.warn('Error getting cart count from fragments:', e);
            }
            return 0;
        },
        
        /**
         * Fetch cart count via AJAX
         */
        fetchCartCount: function(callback) {
            if (typeof wpbnp_frontend === 'undefined') {
                callback(0);
                return;
            }
            
            $.ajax({
                url: wpbnp_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpbnp_get_cart_count',
                    nonce: wpbnp_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(parseInt(response.data) || 0);
                    } else {
                        callback(0);
                    }
                },
                error: function() {
                    callback(0);
                }
            });
        },
        
        /**
         * Handle scroll behavior (optional)
         */
        handleScrollBehavior: function() {
            if (!this.settings.advanced || !this.settings.advanced.hide_on_scroll) {
                return;
            }
            
            let lastScrollTop = 0;
            let scrollTimeout;
            
            $(window).on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const $nav = $('.wpbnp-bottom-nav');
                
                clearTimeout(scrollTimeout);
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scrolling down
                    $nav.addClass('wpbnp-hidden');
                } else {
                    // Scrolling up
                    $nav.removeClass('wpbnp-hidden');
                }
                
                // Show navigation after scroll stops
                scrollTimeout = setTimeout(() => {
                    $nav.removeClass('wpbnp-hidden');
                }, 1000);
                
                lastScrollTop = scrollTop;
            });
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        WPBNP_Frontend.init();
    });
    
    // Also initialize on window load for better compatibility
    $(window).on('load', function() {
        WPBNP_Frontend.updateBadges();
    });
    
    // Make it globally available
    window.WPBNP_Frontend = WPBNP_Frontend;
    
})(jQuery);
