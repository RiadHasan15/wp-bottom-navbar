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

// Check current checkbox/hidden field state
const checkbox = document.querySelector('input[name="settings[enabled]"][type="checkbox"]');
const hiddenField = document.querySelector('input[name="settings[enabled]"][type="hidden"]');

console.log('4. Visible checkbox found:', checkbox ? 'YES' : 'NO');
console.log('5. Hidden field found:', hiddenField ? 'YES' : 'NO');

if (checkbox) {
    console.log('6. Current visible checkbox state:', checkbox.checked);
}
if (hiddenField) {
    console.log('7. Current hidden field value:', hiddenField.value);
}

// Function to test saving state
window.debugSaveState = function() {
    let currentState = false;
    if (checkbox) {
        currentState = checkbox.checked;
    } else if (hiddenField) {
        currentState = hiddenField.value === '1';
    }
    
    const testData = { 'settings[enabled]': currentState };
    localStorage.setItem('wpbnp_form_state', JSON.stringify(testData));
    console.log('Manual save test - saved state:', currentState);
};

// Function to test restoring state
window.debugRestoreState = function() {
    const state = localStorage.getItem('wpbnp_form_state');
    if (state) {
        const data = JSON.parse(state);
        const shouldBeChecked = Boolean(data['settings[enabled]']);
        
        if (checkbox) {
            checkbox.checked = shouldBeChecked;
            console.log('Manual restore test - restored visible checkbox to:', checkbox.checked);
        }
        if (hiddenField) {
            hiddenField.value = shouldBeChecked ? '1' : '0';
            console.log('Manual restore test - restored hidden field to:', hiddenField.value);
        }
    }
};

// Function to toggle and test
window.debugToggleTest = function() {
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('Toggled visible checkbox to:', checkbox.checked);
    } else if (hiddenField) {
        hiddenField.value = hiddenField.value === '1' ? '0' : '1';
        hiddenField.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('Toggled hidden field to:', hiddenField.value);
    }
};

// Function to check what tab we're on
window.debugCurrentTab = function() {
    const url = new URL(window.location.href);
    const tab = url.searchParams.get('tab') || 'items';
    console.log('Current tab:', tab);
    console.log('Should have visible checkbox:', tab === 'items');
    console.log('Should have hidden field:', tab !== 'items');
};

console.log('Debug functions added:');
console.log('- debugSaveState() - manually save current state');
console.log('- debugRestoreState() - manually restore saved state'); 
console.log('- debugToggleTest() - toggle checkbox/field and trigger events');
console.log('- debugCurrentTab() - check current tab and expected elements');
console.log('===========================================');

// Auto-run current tab check
debugCurrentTab();