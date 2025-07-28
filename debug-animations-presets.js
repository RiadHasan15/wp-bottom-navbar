// Debug script for testing animations and presets
// Copy and paste this into your browser console on the frontend

console.log('=== WP Bottom Navigation Animations & Presets Debug ===');

// Check if navigation exists
const nav = document.querySelector('.wpbnp-bottom-nav');
console.log('1. Navigation found:', nav ? 'YES' : 'NO');

if (nav) {
    // Check for navigation items
    const items = nav.querySelectorAll('.wpbnp-nav-item');
    console.log('2. Navigation items found:', items.length);
    
    // Check for dynamic CSS
    const dynamicCSS = document.getElementById('wpbnp-dynamic-css');
    console.log('3. Dynamic CSS found:', dynamicCSS ? 'YES' : 'NO');
    
    if (dynamicCSS) {
        const cssContent = dynamicCSS.textContent || dynamicCSS.innerText;
        console.log('4. Dynamic CSS content length:', cssContent.length, 'characters');
        
        // Check for animation keyframes
        const animationTypes = ['bounce', 'zoom', 'pulse', 'fade', 'slide', 'rotate', 'shake', 'heartbeat', 'swing', 'ripple'];
        const foundAnimations = [];
        
        animationTypes.forEach(type => {
            if (cssContent.includes(`wpbnp-hover-${type}`) || cssContent.includes(`wpbnp-click-${type}`)) {
                foundAnimations.push(type);
            }
        });
        
        console.log('5. Animation types found in CSS:', foundAnimations);
        
        // Check for preset mentions
        const presetTypes = ['minimal', 'dark', 'material', 'ios', 'glassmorphism', 'neumorphism', 'cyberpunk', 'vintage', 'gradient', 'floating'];
        const foundPresets = [];
        
        presetTypes.forEach(preset => {
            if (cssContent.includes(preset)) {
                foundPresets.push(preset);
            }
        });
        
        console.log('6. Preset mentions in CSS:', foundPresets);
    }
    
    // Test animation functionality
    if (items.length > 0) {
        const firstItem = items[0];
        
        console.log('Testing animations on first navigation item...');
        
        // Test hover
        const hoverEvent = new MouseEvent('mouseenter', { bubbles: true });
        firstItem.dispatchEvent(hoverEvent);
        console.log('7. Hover event dispatched');
        
        // Test click
        setTimeout(() => {
            const clickEvent = new MouseEvent('click', { bubbles: true });
            firstItem.dispatchEvent(clickEvent);
            console.log('8. Click event dispatched');
        }, 1000);
        
        // Check computed styles
        const computedStyle = window.getComputedStyle(firstItem);
        console.log('9. Item transition duration:', computedStyle.transitionDuration);
        console.log('10. Item color:', computedStyle.color);
        console.log('11. Item background:', computedStyle.backgroundColor);
    }
}

// Function to manually test animations
window.debugTestAnimation = function(type) {
    const items = document.querySelectorAll('.wpbnp-nav-item');
    if (items.length > 0) {
        const item = items[0];
        const icon = item.querySelector('.wpbnp-nav-icon');
        
        // Remove existing animation classes
        item.classList.remove(...item.classList.value.split(' ').filter(c => c.startsWith('wpbnp-')));
        
        // Add test animation class
        if (icon) {
            icon.style.animation = `wpbnp-hover-${type} 0.6s ease`;
            console.log(`Applied ${type} animation to first item`);
            
            setTimeout(() => {
                icon.style.animation = '';
                console.log(`Removed ${type} animation`);
            }, 600);
        }
    }
};

// Function to check what preset is applied
window.debugCheckPreset = function() {
    const nav = document.querySelector('.wpbnp-bottom-nav');
    if (nav) {
        const styles = window.getComputedStyle(nav);
        console.log('Current navigation styles:');
        console.log('- Background:', styles.backgroundColor);
        console.log('- Height:', styles.height);
        console.log('- Border radius:', styles.borderRadius);
        console.log('- Box shadow:', styles.boxShadow);
        
        // Check for preset-specific features
        if (styles.backdropFilter && styles.backdropFilter !== 'none') {
            console.log('- Detected: Glassmorphism preset (backdrop-filter)');
        }
        
        const background = styles.background || styles.backgroundColor;
        if (background.includes('gradient')) {
            console.log('- Detected: Gradient preset');
        }
    }
};

// Function to manually trigger preset styles
window.debugApplyPreset = function(presetName) {
    const nav = document.querySelector('.wpbnp-bottom-nav');
    if (nav) {
        // Remove existing preset classes
        nav.className = nav.className.replace(/wpbnp-preset-\w+/g, '');
        
        // Add new preset class
        nav.classList.add(`wpbnp-preset-${presetName}`);
        console.log(`Applied preset class: wpbnp-preset-${presetName}`);
        
        // Force style refresh
        nav.style.display = 'none';
        nav.offsetHeight; // Trigger reflow
        nav.style.display = '';
        
        debugCheckPreset();
    }
};

console.log('Debug functions available:');
console.log('- debugTestAnimation(type) - Test specific animation (bounce, zoom, pulse, etc.)');
console.log('- debugCheckPreset() - Check current preset styles');
console.log('- debugApplyPreset(name) - Manually apply preset class');
console.log('===========================================');

// Auto-run preset check
debugCheckPreset();