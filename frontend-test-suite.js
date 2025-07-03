/**
 * Comprehensive Frontend JavaScript and UI Testing Suite
 * For Laravel Taller Sistema Application
 * 
 * Run this script in the browser console after logging in
 * Usage: Copy and paste this entire script into browser console
 */

class FrontendTestSuite {
    constructor() {
        this.results = {
            javascript: [],
            ui: [],
            datatable: [],
            forms: [],
            alerts: [],
            console: []
        };
        this.testCount = 0;
        this.passCount = 0;
        this.failCount = 0;
    }

    log(category, test, status, details = '') {
        const result = {
            test,
            status,
            details,
            timestamp: new Date().toISOString()
        };
        
        this.results[category].push(result);
        this.testCount++;
        
        if (status === 'PASS') {
            this.passCount++;
            console.log(`✅ ${test}: ${details}`);
        } else if (status === 'FAIL') {
            this.failCount++;
            console.error(`❌ ${test}: ${details}`);
        } else {
            console.warn(`⚠️  ${test}: ${details}`);
        }
    }

    // 1. JAVASCRIPT FUNCTIONALITY TESTING
    testJavaScriptLibraries() {
        console.log('🔍 Testing JavaScript Libraries...');
        
        // Test jQuery
        try {
            if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                this.log('javascript', 'jQuery Library', 'PASS', `jQuery version: ${jQuery.fn.jquery}`);
                
                // Test jQuery functionality
                if ($('body').length > 0) {
                    this.log('javascript', 'jQuery DOM Selection', 'PASS', 'jQuery can select DOM elements');
                } else {
                    this.log('javascript', 'jQuery DOM Selection', 'FAIL', 'jQuery cannot select DOM elements');
                }
            } else {
                this.log('javascript', 'jQuery Library', 'FAIL', 'jQuery not loaded');
            }
        } catch (e) {
            this.log('javascript', 'jQuery Library', 'FAIL', e.message);
        }

        // Test Bootstrap JavaScript
        try {
            if (typeof bootstrap !== 'undefined' || (typeof $ !== 'undefined' && $.fn.modal)) {
                this.log('javascript', 'Bootstrap JS', 'PASS', 'Bootstrap JavaScript loaded');
                
                // Test Bootstrap modal functionality
                if (typeof $.fn.modal === 'function') {
                    this.log('javascript', 'Bootstrap Modal', 'PASS', 'Bootstrap modal functionality available');
                }
            } else {
                this.log('javascript', 'Bootstrap JS', 'FAIL', 'Bootstrap JavaScript not loaded');
            }
        } catch (e) {
            this.log('javascript', 'Bootstrap JS', 'FAIL', e.message);
        }

        // Test DataTables
        try {
            if (typeof $.fn.DataTable !== 'undefined') {
                this.log('javascript', 'DataTables Library', 'PASS', 'DataTables library loaded');
            } else {
                this.log('javascript', 'DataTables Library', 'FAIL', 'DataTables library not loaded');
            }
        } catch (e) {
            this.log('javascript', 'DataTables Library', 'FAIL', e.message);
        }

        // Test SweetAlert
        try {
            if (typeof Swal !== 'undefined') {
                this.log('javascript', 'SweetAlert Library', 'PASS', 'SweetAlert library loaded');
            } else if (typeof swal !== 'undefined') {
                this.log('javascript', 'SweetAlert Library', 'PASS', 'SweetAlert (legacy) library loaded');
            } else {
                this.log('javascript', 'SweetAlert Library', 'FAIL', 'SweetAlert library not loaded');
            }
        } catch (e) {
            this.log('javascript', 'SweetAlert Library', 'FAIL', e.message);
        }

        // Test CSRF Token
        try {
            const csrfToken = $('meta[name="csrf-token"]').attr('content') || 
                            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                this.log('javascript', 'CSRF Token', 'PASS', 'CSRF token found in meta tag');
            } else {
                this.log('javascript', 'CSRF Token', 'FAIL', 'CSRF token not found');
            }
        } catch (e) {
            this.log('javascript', 'CSRF Token', 'FAIL', e.message);
        }
    }

    // 2. UI INTERACTION TESTING
    testUIInteractions() {
        console.log('🔍 Testing UI Interactions...');

        // Test buttons
        const buttons = document.querySelectorAll('button, .btn, input[type="button"], input[type="submit"]');
        this.log('ui', 'Button Elements', buttons.length > 0 ? 'PASS' : 'FAIL', 
                `Found ${buttons.length} button elements`);

        // Test dropdown menus
        const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"], .dropdown-toggle');
        this.log('ui', 'Dropdown Menus', dropdowns.length > 0 ? 'PASS' : 'INFO', 
                `Found ${dropdowns.length} dropdown elements`);

        // Test modals
        const modals = document.querySelectorAll('.modal, [data-bs-toggle="modal"]');
        this.log('ui', 'Modal Dialogs', modals.length > 0 ? 'PASS' : 'INFO', 
                `Found ${modals.length} modal elements`);

        // Test forms
        const forms = document.querySelectorAll('form');
        this.log('ui', 'Form Elements', forms.length > 0 ? 'PASS' : 'FAIL', 
                `Found ${forms.length} form elements`);

        // Test select boxes
        const selects = document.querySelectorAll('select');
        this.log('ui', 'Select Boxes', selects.length > 0 ? 'PASS' : 'INFO', 
                `Found ${selects.length} select elements`);

        // Test tabs
        const tabs = document.querySelectorAll('[data-bs-toggle="tab"], .nav-tabs');
        this.log('ui', 'Tab Navigation', tabs.length > 0 ? 'PASS' : 'INFO', 
                `Found ${tabs.length} tab elements`);
    }

    // 3. DATATABLE TESTING
    testDataTables() {
        console.log('🔍 Testing DataTables...');

        try {
            const tables = document.querySelectorAll('table');
            let dataTableCount = 0;
            
            tables.forEach((table, index) => {
                if ($.fn.DataTable.isDataTable(table)) {
                    dataTableCount++;
                    const dt = $(table).DataTable();
                    
                    // Test DataTable API
                    try {
                        const info = dt.page.info();
                        this.log('datatable', `DataTable ${index + 1} - Initialization`, 'PASS', 
                                `Records: ${info.recordsTotal}, Pages: ${Math.ceil(info.recordsTotal / info.length)}`);
                        
                        // Test search functionality
                        if (dt.search) {
                            this.log('datatable', `DataTable ${index + 1} - Search`, 'PASS', 'Search functionality available');
                        }
                        
                        // Test column sorting
                        if (dt.order) {
                            this.log('datatable', `DataTable ${index + 1} - Sorting`, 'PASS', 'Sorting functionality available');
                        }
                        
                        // Test pagination
                        if (dt.page) {
                            this.log('datatable', `DataTable ${index + 1} - Pagination`, 'PASS', 'Pagination functionality available');
                        }
                        
                    } catch (e) {
                        this.log('datatable', `DataTable ${index + 1} - API`, 'FAIL', e.message);
                    }
                }
            });
            
            this.log('datatable', 'DataTable Count', dataTableCount > 0 ? 'PASS' : 'FAIL', 
                    `Found ${dataTableCount} initialized DataTables out of ${tables.length} tables`);
                    
        } catch (e) {
            this.log('datatable', 'DataTable Testing', 'FAIL', e.message);
        }
    }

    // 4. FORM INTERACTION TESTING
    testFormInteractions() {
        console.log('🔍 Testing Form Interactions...');

        const forms = document.querySelectorAll('form');
        
        forms.forEach((form, index) => {
            // Test form validation
            if (form.checkValidity !== undefined) {
                this.log('forms', `Form ${index + 1} - HTML5 Validation`, 'PASS', 'HTML5 validation available');
            }
            
            // Test form elements
            const inputs = form.querySelectorAll('input, textarea, select');
            this.log('forms', `Form ${index + 1} - Input Elements`, inputs.length > 0 ? 'PASS' : 'FAIL', 
                    `Found ${inputs.length} input elements`);
            
            // Test required fields
            const requiredFields = form.querySelectorAll('[required]');
            this.log('forms', `Form ${index + 1} - Required Fields`, requiredFields.length > 0 ? 'PASS' : 'INFO', 
                    `Found ${requiredFields.length} required fields`);
            
            // Test file inputs
            const fileInputs = form.querySelectorAll('input[type="file"]');
            if (fileInputs.length > 0) {
                this.log('forms', `Form ${index + 1} - File Inputs`, 'PASS', 
                        `Found ${fileInputs.length} file input elements`);
            }
        });

        // Test dynamic form dependencies (client → vehicles example)
        const clientSelects = document.querySelectorAll('select[name*="client"], select[id*="client"]');
        const vehicleSelects = document.querySelectorAll('select[name*="vehicle"], select[id*="vehicle"]');
        
        if (clientSelects.length > 0 && vehicleSelects.length > 0) {
            this.log('forms', 'Dynamic Dependencies', 'INFO', 
                    `Found ${clientSelects.length} client selects and ${vehicleSelects.length} vehicle selects`);
        }
    }

    // 5. ALERT AND NOTIFICATION TESTING
    testAlertsAndNotifications() {
        console.log('🔍 Testing Alerts and Notifications...');

        // Test Bootstrap alerts
        const alerts = document.querySelectorAll('.alert');
        this.log('alerts', 'Bootstrap Alerts', alerts.length > 0 ? 'PASS' : 'INFO', 
                `Found ${alerts.length} alert elements`);

        // Test SweetAlert functionality
        if (typeof Swal !== 'undefined') {
            try {
                // Test SweetAlert configuration
                this.log('alerts', 'SweetAlert Configuration', 'PASS', 'SweetAlert ready for use');
                
                // Test a simple alert (commented out to avoid interruption)
                // Swal.fire({title: 'Test', text: 'Testing SweetAlert', timer: 1000, showConfirmButton: false});
                
            } catch (e) {
                this.log('alerts', 'SweetAlert Functionality', 'FAIL', e.message);
            }
        }

        // Test toast notifications
        const toasts = document.querySelectorAll('.toast');
        if (toasts.length > 0) {
            this.log('alerts', 'Toast Notifications', 'PASS', `Found ${toasts.length} toast elements`);
        }
    }

    // 6. CONSOLE ERROR TESTING
    testConsoleErrors() {
        console.log('🔍 Testing Console Errors...');

        // Store original console methods
        const originalError = console.error;
        const originalWarn = console.warn;
        
        let errorCount = 0;
        let warnCount = 0;
        
        // Intercept console errors temporarily
        console.error = function(...args) {
            errorCount++;
            originalError.apply(console, args);
        };
        
        console.warn = function(...args) {
            warnCount++;
            originalWarn.apply(console, args);
        };
        
        // Test for common asset 404 errors
        const scripts = document.querySelectorAll('script[src]');
        const links = document.querySelectorAll('link[href]');
        
        this.log('console', 'Script Resources', 'INFO', `Found ${scripts.length} script resources`);
        this.log('console', 'CSS Resources', 'INFO', `Found ${links.length} CSS resources`);
        
        // Restore original console methods
        setTimeout(() => {
            console.error = originalError;
            console.warn = originalWarn;
            
            this.log('console', 'Console Errors', errorCount === 0 ? 'PASS' : 'FAIL', 
                    `${errorCount} errors detected during testing`);
            this.log('console', 'Console Warnings', warnCount === 0 ? 'PASS' : 'WARN', 
                    `${warnCount} warnings detected during testing`);
        }, 1000);
    }

    // AJAX Testing
    testAjaxFunctionality() {
        console.log('🔍 Testing AJAX Functionality...');

        // Test jQuery AJAX setup
        if (typeof $ !== 'undefined' && $.ajaxSetup) {
            try {
                // Check if CSRF token is set for AJAX
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                if (csrfToken) {
                    this.log('javascript', 'AJAX CSRF Setup', 'PASS', 'CSRF token available for AJAX requests');
                } else {
                    this.log('javascript', 'AJAX CSRF Setup', 'FAIL', 'CSRF token not found for AJAX requests');
                }
            } catch (e) {
                this.log('javascript', 'AJAX Setup', 'FAIL', e.message);
            }
        }

        // Test for AJAX forms
        const ajaxForms = document.querySelectorAll('form[data-ajax="true"], .ajax-form');
        this.log('javascript', 'AJAX Forms', ajaxForms.length > 0 ? 'PASS' : 'INFO', 
                `Found ${ajaxForms.length} AJAX forms`);
    }

    // Run all tests
    async runAllTests() {
        console.log('🚀 Starting Frontend Test Suite...');
        console.log('==========================================');
        
        this.testJavaScriptLibraries();
        this.testUIInteractions();
        this.testDataTables();
        this.testFormInteractions();
        this.testAlertsAndNotifications();
        this.testAjaxFunctionality();
        this.testConsoleErrors();
        
        // Wait a bit for async operations
        setTimeout(() => {
            this.generateReport();
        }, 2000);
    }

    // Generate comprehensive report
    generateReport() {
        console.log('📊 TEST RESULTS SUMMARY');
        console.log('==========================================');
        console.log(`Total Tests: ${this.testCount}`);
        console.log(`Passed: ${this.passCount}`);
        console.log(`Failed: ${this.failCount}`);
        console.log(`Success Rate: ${((this.passCount / this.testCount) * 100).toFixed(1)}%`);
        console.log('==========================================');
        
        // Detailed results
        Object.keys(this.results).forEach(category => {
            if (this.results[category].length > 0) {
                console.log(`\n📋 ${category.toUpperCase()} TESTS:`);
                this.results[category].forEach(result => {
                    const icon = result.status === 'PASS' ? '✅' : result.status === 'FAIL' ? '❌' : '⚠️';
                    console.log(`${icon} ${result.test}: ${result.details}`);
                });
            }
        });
        
        // Recommendations
        console.log('\n💡 RECOMMENDATIONS:');
        
        if (this.results.javascript.some(r => r.status === 'FAIL')) {
            console.log('- Fix JavaScript library loading issues');
        }
        if (this.results.datatable.some(r => r.status === 'FAIL')) {
            console.log('- Review DataTable initialization and configuration');
        }
        if (this.results.forms.some(r => r.status === 'FAIL')) {
            console.log('- Improve form validation and interaction handling');
        }
        if (this.results.console.some(r => r.status === 'FAIL')) {
            console.log('- Address console errors and warnings');
        }
        
        console.log('\n🎯 Test Suite Complete!');
        
        // Return results object for further analysis
        return this.results;
    }
}

// Initialize and run the test suite
const testSuite = new FrontendTestSuite();

// Auto-run tests when script is loaded
console.log('Frontend Test Suite loaded. Running tests...');
testSuite.runAllTests();

// Make testSuite available globally for manual testing
window.frontendTestSuite = testSuite;

// Additional manual testing functions
window.testSpecificFeature = function(featureName) {
    console.log(`🔍 Testing specific feature: ${featureName}`);
    
    switch(featureName.toLowerCase()) {
        case 'datatables':
            testSuite.testDataTables();
            break;
        case 'forms':
            testSuite.testFormInteractions();
            break;
        case 'alerts':
            testSuite.testAlertsAndNotifications();
            break;
        case 'javascript':
            testSuite.testJavaScriptLibraries();
            break;
        case 'ui':
            testSuite.testUIInteractions();
            break;
        default:
            console.log('Available features: datatables, forms, alerts, javascript, ui');
    }
};

// Function to test SweetAlert
window.testSweetAlert = function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Test Alert',
            text: 'This is a test of SweetAlert functionality',
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        console.error('SweetAlert not available');
    }
};

// Function to test Bootstrap modal
window.testBootstrapModal = function() {
    if (typeof $.fn.modal === 'function') {
        const modalHtml = `
            <div class="modal fade" id="testModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Test Modal</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>This is a test modal for Bootstrap functionality.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#testModal').modal('show');
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            $('#testModal').modal('hide');
            setTimeout(() => $('#testModal').remove(), 500);
        }, 5000);
    } else {
        console.error('Bootstrap modal not available');
    }
};

console.log('🔧 Additional testing functions available:');
console.log('- testSpecificFeature("featureName") - Test specific features');
console.log('- testSweetAlert() - Test SweetAlert functionality');
console.log('- testBootstrapModal() - Test Bootstrap modal');
console.log('- frontendTestSuite.runAllTests() - Run all tests again');