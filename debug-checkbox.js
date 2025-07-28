// Debug script for testing the Enable Bottom Navigation checkbox fix
// Copy and paste this into your browser console while on the admin page

console.log('=== WP Bottom Navigation Checkbox Debug ===');

// Check if localStorage has saved state
const savedState = localStorage.getItem('wpbnp_form_state');
console.log('1. Saved form state in localStorage:', savedState ? 'EXISTS' : 'NONE');

if (savedState) {
    try {
        const formData = JSON.parse(savedState);
        console.log('2. Parsed form data:', formData);
        console.log('3. Enabled checkbox state in localStorage:', formData['settings[enabled]']);
    } catch (e) {
        console.error('2. Error parsing localStorage data:', e);
    }
}

// Check current checkbox state
const checkbox = document.querySelector('input[name="settings[enabled]"]');
console.log('4. Checkbox element found:', checkbox ? 'YES' : 'NO');
if (checkbox) {
    console.log('5. Current checkbox state:', checkbox.checked);
}

// Function to test saving state
window.debugSaveState = function() {
    if (checkbox) {
        const currentState = checkbox.checked;
        const testData = { 'settings[enabled]': currentState };
        localStorage.setItem('wpbnp_form_state', JSON.stringify(testData));
        console.log('Manual save test - saved state:', currentState);
    }
};

// Function to test restoring state
window.debugRestoreState = function() {
    const state = localStorage.getItem('wpbnp_form_state');
    if (state && checkbox) {
        const data = JSON.parse(state);
        checkbox.checked = Boolean(data['settings[enabled]']);
        console.log('Manual restore test - restored to:', checkbox.checked);
    }
};

// Function to toggle and test
window.debugToggleTest = function() {
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('Toggled checkbox to:', checkbox.checked);
    }
};

console.log('Debug functions added:');
console.log('- debugSaveState() - manually save current state');
console.log('- debugRestoreState() - manually restore saved state');
console.log('- debugToggleTest() - toggle checkbox and trigger events');
console.log('===========================================');