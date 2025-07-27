/**
 * WP Bottom Navigation Pro - Frontend JavaScript
 */

(function($) {
    'use strict';

    var WPBNP_Frontend = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.bindEvents();
            this.updateBadges();
            this.addRippleEffect();
            this.handleKeyboardNavigation();
            
            // Update badges periodically if WooCommerce is active
            if (typeof wc_cart_fragments_params !== 'undefined') {
                setInterval(this.updateBadges.bind(this), 30000); // Every 30 seconds
            }
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Handle navigation item clicks
            $('.wpbnp-nav-item').on('click', this.handleItemClick);
            
            // Handle keyboard navigation
            $('.wpbnp-nav-item').on('keydown', this.handleKeyDown);
            
            // Update cart badge when WooCommerce fragments update
            $(document.body).on('wc_fragments_refreshed', function() {
                self.updateBadges();
            });
            
            // Handle active state based on current page
            this.setActiveItem();
            
            // Add touch support for mobile devices
            if ('ontouchstart' in window) {
                $('.wpbnp-nav-item').on('touchstart', this.handleTouchStart);
                $('.wpbnp-nav-item').on('touchend', this.handleTouchEnd);
            }
        },
        
        /**
         * Handle navigation item clicks
         */
        handleItemClick: function(e) {
            var $item = $(this);
            var href = $item.attr('href');
            
            // Add click animation
            WPBNP_Frontend.addClickAnimation($item);
            
            // Handle special URLs
            if (href === '#' || href === '') {
                e.preventDefault();
                return;
            }
            
            // Update active state
            $('.wpbnp-nav-item').removeClass('active');
            $item.addClass('active');
            
            // Trigger custom event for developers
            $(document).trigger('wpbnp_item_clicked', [$item, href]);
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
                    $current.click();
                    break;
                    
                case 27: // Escape
                    $current.blur();
                    break;
            }
        },
        
        /**
         * Handle touch start for mobile
         */
        handleTouchStart: function(e) {
            $(this).addClass('wpbnp-touching');
        },
        
        /**
         * Handle touch end for mobile
         */
        handleTouchEnd: function(e) {
            var $item = $(this);
            setTimeout(function() {
                $item.removeClass('wpbnp-touching');
            }, 150);
        },
        
        /**
         * Add ripple effect to navigation items
         */
        addRippleEffect: function() {
            $('.wpbnp-nav-item').on('click', function(e) {
                var $item = $(this);
                var $ripple = $('<span class="wpbnp-ripple"></span>');
                
                // Remove existing ripples
                $item.find('.wpbnp-ripple').remove();
                
                // Calculate ripple position
                var rect = this.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height);
                var x = e.clientX - rect.left - size / 2;
                var y = e.clientY - rect.top - size / 2;
                
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
                setTimeout(function() {
                    $ripple.remove();
                }, 600);
            });
        },
        
        /**
         * Add click animation
         */
        addClickAnimation: function($item) {
            var settings = wpbnp_frontend.settings;
            
            if (!settings.animations || !settings.animations.enabled) {
                return;
            }
            
            var animationType = settings.animations.type;
            var duration = settings.animations.duration || 300;
            
            $item.addClass('wpbnp-animating');
            
            switch (animationType) {
                case 'bounce':
                    $item.addClass('wpbnp-bounce-animation');
                    break;
                case 'zoom':
                    $item.addClass('wpbnp-zoom-animation');
                    break;
                case 'pulse':
                    $item.addClass('wpbnp-pulse-animation');
                    break;
            }
            
            setTimeout(function() {
                $item.removeClass('wpbnp-animating wpbnp-bounce-animation wpbnp-zoom-animation wpbnp-pulse-animation');
            }, duration);
        },
        
        /**
         * Update notification badges
         */
        updateBadges: function() {
            var self = this;
            
            $('.wpbnp-nav-item').each(function() {
                var $item = $(this);
                var itemId = $item.data('item-id');
                var $badge = $item.find('.wpbnp-badge');
                
                // Get badge count for specific items
                var badgeCount = self.getBadgeCount(itemId);
                
                if (badgeCount > 0) {
                    var displayCount = badgeCount > 99 ? '99+' : badgeCount.toString();
                    
                    if ($badge.length) {
                        $badge.text(displayCount);
                    } else {
                        // Create new badge
                        var $newBadge = $('<span class="wpbnp-badge" aria-label="' + badgeCount + ' notifications">' + displayCount + '</span>');
                        $item.find('.wpbnp-nav-icon').append($newBadge);
                    }
                    
                    $badge.show();
                } else {
                    $badge.hide();
                }
            });
            
            // Trigger custom event for developers
            $(document).trigger('wpbnp_badges_updated');
        },
        
        /**
         * Get badge count for specific item
         */
        getBadgeCount: function(itemId) {
            switch (itemId) {
                case 'cart':
                case 'shop':
                    return this.getWooCommerceCartCount();
                    
                case 'notifications':
                    return this.getBuddyPressNotificationCount();
                    
                case 'messages':
                    return this.getBuddyPressMessageCount();
                    
                default:
                    // Allow custom badge counts via filter
                    var customCount = $(document).triggerHandler('wpbnp_get_badge_count', [itemId]);
                    return parseInt(customCount) || 0;
            }
        },
        
        /**
         * Get WooCommerce cart count
         */
        getWooCommerceCartCount: function() {
            var count = 0;
            
            // Try to get from cart fragments
            if (typeof wc_cart_fragments_params !== 'undefined') {
                var cartFragments = sessionStorage.getItem('wc_fragments');
                if (cartFragments) {
                    try {
                        var fragments = JSON.parse(cartFragments);
                        var cartCountFragment = fragments['span.count'] || fragments['.cart-contents-count'];
                        if (cartCountFragment) {
                            var matches = cartCountFragment.match(/\d+/);
                            count = matches ? parseInt(matches[0]) : 0;
                        }
                    } catch (e) {
                        console.warn('Error parsing cart fragments:', e);
                    }
                }
            }
            
            // Fallback: check cart count elements in DOM
            if (count === 0) {
                var $cartCount = $('.cart-contents-count, .cart-count, .woocommerce-cart-count');
                if ($cartCount.length) {
                    count = parseInt($cartCount.first().text()) || 0;
                }
            }
            
            return count;
        },
        
        /**
         * Get BuddyPress notification count
         */
        getBuddyPressNotificationCount: function() {
            var count = 0;
            var $notificationCount = $('#bp-notifications-count, .bp-notification-count');
            
            if ($notificationCount.length) {
                count = parseInt($notificationCount.text()) || 0;
            }
            
            return count;
        },
        
        /**
         * Get BuddyPress message count
         */
        getBuddyPressMessageCount: function() {
            var count = 0;
            var $messageCount = $('#bp-messages-count, .bp-message-count');
            
            if ($messageCount.length) {
                count = parseInt($messageCount.text()) || 0;
            }
            
            return count;
        },
        
        /**
         * Set active navigation item based on current page
         */
        setActiveItem: function() {
            var currentUrl = window.location.href;
            var currentPath = window.location.pathname;
            
            $('.wpbnp-nav-item').each(function() {
                var $item = $(this);
                var itemUrl = $item.attr('href');
                
                if (!itemUrl || itemUrl === '#') {
                    return;
                }
                
                // Exact URL match
                if (itemUrl === currentUrl) {
                    $item.addClass('active');
                    return;
                }
                
                // Path match
                if (itemUrl === currentPath) {
                    $item.addClass('active');
                    return;
                }
                
                // Home page special case
                if (itemUrl === wpbnp_frontend.home_url && (currentPath === '/' || currentUrl === wpbnp_frontend.home_url)) {
                    $item.addClass('active');
                    return;
                }
                
                // Partial match for parent pages
                if (currentPath.indexOf(itemUrl) === 0 && itemUrl.length > 1) {
                    $item.addClass('active');
                }
            });
        },
        
        /**
         * Handle keyboard navigation for accessibility
         */
        handleKeyboardNavigation: function() {
            // Add tab index to navigation items
            $('.wpbnp-nav-item').attr('tabindex', '0');
            
            // Handle focus management
            $('.wpbnp-nav-item').on('focus', function() {
                $(this).addClass('wpbnp-focused');
            }).on('blur', function() {
                $(this).removeClass('wpbnp-focused');
            });
        },
        
        /**
         * Handle device orientation change
         */
        handleOrientationChange: function() {
            // Recalculate positioning if needed
            setTimeout(function() {
                $(window).trigger('resize');
            }, 100);
        },
        
        /**
         * Initialize smooth scrolling for anchor links
         */
        initSmoothScrolling: function() {
            $('.wpbnp-nav-item[href^="#"]').on('click', function(e) {
                var target = $(this.getAttribute('href'));
                
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
        },
        
        /**
         * Add support for swipe gestures on mobile
         */
        addSwipeSupport: function() {
            var startX, startY, endX, endY;
            var $nav = $('.wpbnp-bottom-nav');
            
            $nav.on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
                startY = e.originalEvent.touches[0].clientY;
            });
            
            $nav.on('touchend', function(e) {
                endX = e.originalEvent.changedTouches[0].clientX;
                endY = e.originalEvent.changedTouches[0].clientY;
                
                var deltaX = Math.abs(endX - startX);
                var deltaY = Math.abs(endY - startY);
                
                // Horizontal swipe
                if (deltaX > 50 && deltaY < 30) {
                    var direction = endX > startX ? 'right' : 'left';
                    $(document).trigger('wpbnp_swipe', [direction]);
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WPBNP_Frontend.init();
    });
    
    // Handle orientation change
    $(window).on('orientationchange', function() {
        WPBNP_Frontend.handleOrientationChange();
    });
    
    // Expose to global scope for developers
    window.WPBNP_Frontend = WPBNP_Frontend;
    
})(jQuery);
