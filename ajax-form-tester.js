/**
 * AJAX and Dynamic Form Testing Script
 * Specifically for Laravel Taller Sistema Application
 * 
 * This script tests common AJAX patterns in workshop management systems:
 */ 

class AjaxFormTester {
    constructor() {
        this.originalAjax = $.ajax;
        this.requests = [];
        this.setupAjaxInterceptor();
    }

    setupAjaxInterceptor() {
        const self = this;
        
        $.ajax = function(options) {
            // Log all AJAX requests
            self.requests.push({
                url: options.url,
                method: options.method || options.type || 'GET',
                data: options.data,
                timestamp: new Date(),
                headers: options.headers
            });
            
            console.log('🔍 AJAX Request:', {
                url: options.url,
                method: options.method || options.type || 'GET',
                data: options.data
            });
            
            return self.originalAjax.call(this, options);
        };
    }

    // Test Client → Vehicle Dependency
    testClientVehicleDependency() {
        console.log('🚗 Testing Client → Vehicle Dependency...');
        
        const clientSelect = document.querySelector('select[name*="client"], select[id*="client"]');
        const vehicleSelect = document.querySelector('select[name*="vehicle"], select[id*="vehicle"]');
        
        if (!clientSelect) {
            console.warn('⚠️ Client select not found');
            return false;
        }
        
        if (!vehicleSelect) {
            console.warn('⚠️ Vehicle select not found');
            return false;
        }
        
        console.log('✅ Found client and vehicle selects');
        
        // Get initial vehicle count
        const initialVehicleCount = vehicleSelect.options.length;
        console.log(`📊 Initial vehicle options: ${initialVehicleCount}`);
        
        // Simulate client selection
        if (clientSelect.options.length > 1) {
            const testClientValue = clientSelect.options[1].value;
            console.log(`🔄 Selecting client: ${testClientValue}`);
            
            // Trigger change event
            clientSelect.value = testClientValue;
            clientSelect.dispatchEvent(new Event('change'));
            
            // Wait for AJAX response
            setTimeout(() => {
                const newVehicleCount = vehicleSelect.options.length;
                console.log(`📊 Vehicle options after client selection: ${newVehicleCount}`);
                
                if (newVehicleCount !== initialVehicleCount) {
                    console.log('✅ Client → Vehicle dependency working');
                    return true;
                } else {
                    console.log('❌ Client → Vehicle dependency may not be working');
                    return false;
                }
            }, 2000);
        }
    }

    // Test Form AJAX Submission
    testFormAjaxSubmission() {
        console.log('📝 Testing Form AJAX Submission...');
        
        const forms = document.querySelectorAll('form');
        let ajaxFormsFound = 0;
        
        forms.forEach((form, index) => {
            // Check if form has AJAX handling
            const hasAjaxClass = form.classList.contains('ajax-form');
            const hasAjaxData = form.hasAttribute('data-ajax');
            const hasOnSubmit = form.onsubmit !== null;
            
            if (hasAjaxClass || hasAjaxData || hasOnSubmit) {
                ajaxFormsFound++;
                console.log(`📋 Form ${index + 1}: AJAX-enabled`);
                
                // Test form validation
                const requiredFields = form.querySelectorAll('[required]');
                console.log(`   Required fields: ${requiredFields.length}`);
                
                // Test CSRF token
                const csrfToken = form.querySelector('input[name="_token"]');
                if (csrfToken) {
                    console.log(`   ✅ CSRF token present: ${csrfToken.value.substring(0, 10)}...`);
                } else {
                    console.log(`   ❌ CSRF token missing`);
                }
            }
        });
        
        console.log(`📊 Total AJAX forms found: ${ajaxFormsFound}`);
        return ajaxFormsFound;
    }

    // Test DataTable AJAX Loading
    testDataTableAjax() {
        console.log('📊 Testing DataTable AJAX...');
        
        const tables = document.querySelectorAll('table');
        let dataTableAjaxCount = 0;
        
        tables.forEach((table, index) => {
            if ($.fn.DataTable.isDataTable(table)) {
                const dt = $(table).DataTable();
                const settings = dt.settings()[0];
                
                if (settings.ajax) {
                    dataTableAjaxCount++;
                    console.log(`📋 Table ${index + 1}: AJAX-enabled DataTable`);
                    console.log(`   AJAX URL: ${typeof settings.ajax === 'string' ? settings.ajax : settings.ajax.url}`);
                    
                    // Test DataTable reload
                    console.log('🔄 Testing DataTable reload...');
                    dt.ajax.reload(null, false);
                }
            }
        });
        
        console.log(`📊 AJAX DataTables found: ${dataTableAjaxCount}`);
        return dataTableAjaxCount;
    }

    // Test Modal AJAX Loading
    testModalAjax() {
        console.log('🔲 Testing Modal AJAX Loading...');
        
        const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-remote], [data-toggle="modal"][data-remote]');
        
        modalTriggers.forEach((trigger, index) => {
            const remoteUrl = trigger.getAttribute('data-bs-remote') || trigger.getAttribute('data-remote');
            if (remoteUrl) {
                console.log(`🔗 Modal trigger ${index + 1}: ${remoteUrl}`);
            }
        });
        
        console.log(`📊 Modal AJAX triggers found: ${modalTriggers.length}`);
        return modalTriggers.length;
    }

    // Test API Endpoints
    async testApiEndpoints() {
        console.log('🔌 Testing API Endpoints...');
        
        const commonEndpoints = [
            '/api/clients',
            '/api/vehicles',
            '/api/services',
            '/clients/api',
            '/vehicles/api',
            '/services/api'
        ];
        
        for (const endpoint of commonEndpoints) {
            try {
                const response = await fetch(endpoint, {
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    console.log(`✅ ${endpoint}: Available`);
                } else if (response.status === 404) {
                    console.log(`❌ ${endpoint}: Not found (404)`);
                } else {
                    console.log(`⚠️ ${endpoint}: ${response.status} ${response.statusText}`);
                }
            } catch (error) {
                console.log(`❌ ${endpoint}: ${error.message}`);
            }
        }
    }

    // Test CSRF Token Handling
    testCSRFHandling() {
        console.log('🔐 Testing CSRF Token Handling...');
        
        const metaToken = $('meta[name="csrf-token"]').attr('content');
        const formTokens = document.querySelectorAll('input[name="_token"]');
        
        console.log(`Meta CSRF token: ${metaToken ? 'Present' : 'Missing'}`);
        console.log(`Form CSRF tokens: ${formTokens.length} found`);
        
        // Check if jQuery AJAX setup includes CSRF token
        if (typeof $.ajaxSetup === 'function') {
            console.log('✅ jQuery AJAX setup available');
        }
        
        // Test CSRF token in AJAX headers
        const testAjaxHeaders = {
            'X-CSRF-TOKEN': metaToken,
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        console.log('🔍 AJAX Headers for testing:', testAjaxHeaders);
        
        return {
            metaToken: !!metaToken,
            formTokens: formTokens.length,
            ajaxSetup: typeof $.ajaxSetup === 'function'
        };
    }

    // Test Error Handling
    testErrorHandling() {
        console.log('⚠️ Testing Error Handling...');
        
        // Test 404 request
        $.ajax({
            url: '/nonexistent-endpoint',
            method: 'GET',
            success: function() {
                console.log('❌ 404 test: Unexpected success');
            },
            error: function(xhr, status, error) {
                console.log('✅ 404 Error handling works:', status);
            }
        });
        
        // Test 422 validation error (if possible)
        const testForm = document.querySelector('form');
        if (testForm) {
            const formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            // Intentionally missing required data
            
            $.ajax({
                url: testForm.action || window.location.href,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    console.log('⚠️ Validation test: Unexpected success');
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 422) {
                        console.log('✅ Validation error handling works');
                    } else {
                        console.log(`⚠️ Validation test: ${xhr.status} ${error}`);
                    }
                }
            });
        }
    }

    // Generate AJAX Report
    generateAjaxReport() {
        console.log('📋 AJAX TEST REPORT');
        console.log('==========================================');
        
        console.log(`🔍 Total AJAX requests intercepted: ${this.requests.length}`);
        
        // Group requests by URL
        const requestGroups = {};
        this.requests.forEach(req => {
            const key = `${req.method} ${req.url}`;
            if (!requestGroups[key]) {
                requestGroups[key] = [];
            }
            requestGroups[key].push(req);
        });
        
        console.log('\n📊 Request Summary:');
        Object.keys(requestGroups).forEach(key => {
            console.log(`   ${key}: ${requestGroups[key].length} requests`);
        });
        
        // Recent requests
        console.log('\n🕒 Recent Requests:');
        this.requests.slice(-5).forEach(req => {
            console.log(`   ${req.timestamp.toLocaleTimeString()}: ${req.method} ${req.url}`);
        });
        
        return {
            totalRequests: this.requests.length,
            uniqueEndpoints: Object.keys(requestGroups).length,
            requests: this.requests
        };
    }

    // Run all AJAX tests
    async runAllAjaxTests() {
        console.log('🚀 Starting AJAX Testing Suite...');
        console.log('==========================================');
        
        // Wait a moment for page to load
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        this.testCSRFHandling();
        this.testFormAjaxSubmission();
        this.testDataTableAjax();
        this.testModalAjax();
        this.testClientVehicleDependency();
        
        await this.testApiEndpoints();
        
        // Wait for AJAX requests to complete
        setTimeout(() => {
            this.testErrorHandling();
            
            setTimeout(() => {
                this.generateAjaxReport();
            }, 3000);
        }, 2000);
    }

    // Restore original AJAX function
    restore() {
        $.ajax = this.originalAjax;
        console.log('🔧 AJAX interceptor removed');
    }
}

// Initialize AJAX tester
const ajaxTester = new AjaxFormTester();

// Make available globally
window.ajaxTester = ajaxTester;

// Auto-run tests
console.log('AJAX Form Tester loaded. Running tests...');
ajaxTester.runAllAjaxTests();

// Additional helper functions
window.testSpecificAjax = function(endpoint) {
    console.log(`🔍 Testing specific endpoint: ${endpoint}`);
    
    $.ajax({
        url: endpoint,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(data) {
            console.log('✅ Endpoint response:', data);
        },
        error: function(xhr, status, error) {
            console.log('❌ Endpoint error:', status, error);
        }
    });
};

// Function to simulate form interactions
window.simulateFormInteraction = function(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) {
        console.error('Form not found:', formSelector);
        return;
    }
    
    console.log('🔄 Simulating form interaction...');
    
    // Fill out form fields with test data
    const inputs = form.querySelectorAll('input[type="text"], input[type="email"], textarea');
    inputs.forEach((input, index) => {
        input.value = `Test Value ${index + 1}`;
        input.dispatchEvent(new Event('input'));
    });
    
    // Select first option in select boxes
    const selects = form.querySelectorAll('select');
    selects.forEach(select => {
        if (select.options.length > 1) {
            select.selectedIndex = 1;
            select.dispatchEvent(new Event('change'));
        }
    });
    
    console.log('✅ Form filled with test data');
};

console.log('🔧 Additional AJAX testing functions available:');
console.log('- testSpecificAjax(endpoint) - Test specific API endpoint');
console.log('- simulateFormInteraction(selector) - Fill form with test data');
console.log('- ajaxTester.restore() - Remove AJAX interceptor');