/**
 * DataTable Comprehensive Testing Script
 * For Laravel Taller Sistema Application
 * 
 * Tests all DataTable functionality including:
 * - Initialization and configuration
 * - Sorting, searching, pagination
 * - AJAX loading and server-side processing
 * - Export functionality
 * - Responsive behavior
 */

class DataTableTester {
    constructor() {
        this.tables = [];
        this.testResults = {
            initialization: [],
            functionality: [],
            performance: [],
            ajax: [],
            export: []
        };
    }

    // Discover all DataTables on the page
    discoverDataTables() {
        console.log('🔍 Discovering DataTables...');
        
        const allTables = document.querySelectorAll('table');
        this.tables = [];
        
        allTables.forEach((table, index) => {
            if ($.fn.DataTable.isDataTable(table)) {
                const dt = $(table).DataTable();
                const settings = dt.settings()[0];
                
                const tableInfo = {
                    index: index,
                    element: table,
                    id: table.id || `table-${index}`,
                    className: table.className,
                    dataTable: dt,
                    settings: settings,
                    hasAjax: !!settings.ajax,
                    isServerSide: settings.oFeatures.bServerSide,
                    columns: settings.aoColumns.length,
                    rows: dt.data().length
                };
                
                this.tables.push(tableInfo);
                
                console.log(`📊 Table ${index + 1} (${tableInfo.id}):`);
                console.log(`   Columns: ${tableInfo.columns}`);
                console.log(`   Rows: ${tableInfo.rows}`);
                console.log(`   AJAX: ${tableInfo.hasAjax ? 'Yes' : 'No'}`);
                console.log(`   Server-side: ${tableInfo.isServerSide ? 'Yes' : 'No'}`);
            }
        });
        
        console.log(`✅ Found ${this.tables.length} DataTables`);
        return this.tables;
    }

    // Test DataTable initialization
    testInitialization() {
        console.log('🚀 Testing DataTable Initialization...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, settings, id } = tableInfo;
            
            try {
                // Test basic API availability
                const info = dataTable.page.info();
                this.logResult('initialization', `${id} - API Access`, 'PASS', 
                    `Page info: ${info.page + 1}/${Math.ceil(info.recordsTotal / info.length)}`);
                
                // Test settings
                if (settings) {
                    this.logResult('initialization', `${id} - Settings`, 'PASS', 
                        `Features: ${Object.keys(settings.oFeatures).filter(f => settings.oFeatures[f]).length}`);
                } else {
                    this.logResult('initialization', `${id} - Settings`, 'FAIL', 'Settings not accessible');
                }
                
                // Test DOM elements
                const wrapper = $(tableInfo.element).closest('.dataTables_wrapper');
                if (wrapper.length > 0) {
                    this.logResult('initialization', `${id} - DOM Structure`, 'PASS', 
                        `Wrapper classes: ${wrapper.attr('class')}`);
                } else {
                    this.logResult('initialization', `${id} - DOM Structure`, 'FAIL', 'DataTable wrapper not found');
                }
                
            } catch (error) {
                this.logResult('initialization', `${id} - Initialization`, 'FAIL', error.message);
            }
        });
    }

    // Test sorting functionality
    testSorting() {
        console.log('🔄 Testing DataTable Sorting...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id } = tableInfo;
            
            try {
                // Get current order
                const currentOrder = dataTable.order();
                this.logResult('functionality', `${id} - Current Sort`, 'INFO', 
                    `Column ${currentOrder[0][0]}, Direction: ${currentOrder[0][1]}`);
                
                // Test sorting on different columns
                const columnCount = dataTable.columns().count();
                
                for (let col = 0; col < Math.min(columnCount, 3); col++) {
                    try {
                        // Sort ascending
                        dataTable.order([col, 'asc']).draw();
                        
                        // Verify sort applied
                        const newOrder = dataTable.order();
                        if (newOrder[0][0] === col && newOrder[0][1] === 'asc') {
                            this.logResult('functionality', `${id} - Sort Column ${col} ASC`, 'PASS', 
                                'Sort applied successfully');
                        } else {
                            this.logResult('functionality', `${id} - Sort Column ${col} ASC`, 'FAIL', 
                                'Sort not applied correctly');
                        }
                        
                        // Sort descending
                        dataTable.order([col, 'desc']).draw();
                        
                        const descOrder = dataTable.order();
                        if (descOrder[0][0] === col && descOrder[0][1] === 'desc') {
                            this.logResult('functionality', `${id} - Sort Column ${col} DESC`, 'PASS', 
                                'Descending sort applied successfully');
                        }
                        
                    } catch (sortError) {
                        this.logResult('functionality', `${id} - Sort Column ${col}`, 'FAIL', sortError.message);
                    }
                }
                
                // Restore original order
                dataTable.order(currentOrder).draw();
                
            } catch (error) {
                this.logResult('functionality', `${id} - Sorting Test`, 'FAIL', error.message);
            }
        });
    }

    // Test search functionality
    testSearch() {
        console.log('🔍 Testing DataTable Search...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id } = tableInfo;
            
            try {
                // Store original search
                const originalSearch = dataTable.search();
                const originalRows = dataTable.rows().count();
                
                // Test global search
                const testSearchTerm = 'test';
                dataTable.search(testSearchTerm).draw();
                
                const searchRows = dataTable.rows({ search: 'applied' }).count();
                this.logResult('functionality', `${id} - Global Search`, 'PASS', 
                    `Search '${testSearchTerm}': ${originalRows} → ${searchRows} rows`);
                
                // Clear search
                dataTable.search('').draw();
                const clearedRows = dataTable.rows().count();
                
                if (clearedRows === originalRows) {
                    this.logResult('functionality', `${id} - Search Clear`, 'PASS', 
                        'Search cleared successfully');
                } else {
                    this.logResult('functionality', `${id} - Search Clear`, 'FAIL', 
                        `Expected ${originalRows}, got ${clearedRows}`);
                }
                
                // Test column search if available
                const columns = dataTable.columns();
                columns.every(function(index) {
                    const column = this;
                    const header = $(column.header());
                    
                    if (header.find('input').length > 0) {
                        // Column has search input
                        try {
                            column.search('test').draw();
                            const columnSearchRows = dataTable.rows({ search: 'applied' }).count();
                            
                            console.log(`   Column ${index} search: ${originalRows} → ${columnSearchRows} rows`);
                            
                            // Clear column search
                            column.search('').draw();
                        } catch (columnSearchError) {
                            console.warn(`   Column ${index} search error:`, columnSearchError.message);
                        }
                    }
                });
                
                // Restore original search
                dataTable.search(originalSearch).draw();
                
            } catch (error) {
                this.logResult('functionality', `${id} - Search Test`, 'FAIL', error.message);
            }
        });
    }

    // Test pagination
    testPagination() {
        console.log('📄 Testing DataTable Pagination...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id } = tableInfo;
            
            try {
                const info = dataTable.page.info();
                
                this.logResult('functionality', `${id} - Pagination Info`, 'PASS', 
                    `Page ${info.page + 1}/${info.pages}, Records: ${info.recordsDisplay}/${info.recordsTotal}`);
                
                // Test page navigation if multiple pages
                if (info.pages > 1) {
                    const currentPage = info.page;
                    
                    // Go to next page
                    dataTable.page('next').draw();
                    const nextPageInfo = dataTable.page.info();
                    
                    if (nextPageInfo.page === currentPage + 1) {
                        this.logResult('functionality', `${id} - Next Page`, 'PASS', 
                            `Navigated to page ${nextPageInfo.page + 1}`);
                    } else {
                        this.logResult('functionality', `${id} - Next Page`, 'FAIL', 
                            'Next page navigation failed');
                    }
                    
                    // Go back to original page
                    dataTable.page(currentPage).draw();
                    
                    // Test first and last page
                    dataTable.page('first').draw();
                    const firstPageInfo = dataTable.page.info();
                    if (firstPageInfo.page === 0) {
                        this.logResult('functionality', `${id} - First Page`, 'PASS', 'First page navigation works');
                    }
                    
                    dataTable.page('last').draw();
                    const lastPageInfo = dataTable.page.info();
                    if (lastPageInfo.page === info.pages - 1) {
                        this.logResult('functionality', `${id} - Last Page`, 'PASS', 'Last page navigation works');
                    }
                    
                    // Restore original page
                    dataTable.page(currentPage).draw();
                    
                } else {
                    this.logResult('functionality', `${id} - Pagination`, 'INFO', 'Single page table');
                }
                
            } catch (error) {
                this.logResult('functionality', `${id} - Pagination Test`, 'FAIL', error.message);
            }
        });
    }

    // Test AJAX functionality
    testAjaxFunctionality() {
        console.log('🔌 Testing DataTable AJAX...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id, hasAjax } = tableInfo;
            
            if (!hasAjax) {
                this.logResult('ajax', `${id} - AJAX`, 'INFO', 'Not AJAX-enabled');
                return;
            }
            
            try {
                // Test AJAX reload
                console.log(`🔄 Testing AJAX reload for ${id}...`);
                
                const startTime = performance.now();
                
                dataTable.ajax.reload(function(json) {
                    const endTime = performance.now();
                    const loadTime = Math.round(endTime - startTime);
                    
                    this.logResult('ajax', `${id} - AJAX Reload`, 'PASS', 
                        `Loaded in ${loadTime}ms, Records: ${json.recordsTotal || 'N/A'}`);
                    
                    // Test data structure
                    if (json.data && Array.isArray(json.data)) {
                        this.logResult('ajax', `${id} - Data Structure`, 'PASS', 
                            `${json.data.length} records in response`);
                    } else {
                        this.logResult('ajax', `${id} - Data Structure`, 'WARN', 
                            'Unexpected data structure');
                    }
                }.bind(this), false);
                
            } catch (error) {
                this.logResult('ajax', `${id} - AJAX Test`, 'FAIL', error.message);
            }
        });
    }

    // Test export functionality
    testExportFunctionality() {
        console.log('📊 Testing DataTable Export...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id } = tableInfo;
            
            try {
                // Check for Buttons extension
                if (dataTable.buttons) {
                    const buttons = dataTable.buttons();
                    if (buttons.length > 0) {
                        this.logResult('export', `${id} - Export Buttons`, 'PASS', 
                            `${buttons.length} export buttons available`);
                        
                        // List available buttons
                        buttons.each(function(index) {
                            const button = this;
                            const buttonText = $(button.node()).text().trim();
                            console.log(`   Button ${index + 1}: ${buttonText}`);
                        });
                        
                    } else {
                        this.logResult('export', `${id} - Export Buttons`, 'INFO', 
                            'No export buttons configured');
                    }
                } else {
                    this.logResult('export', `${id} - Buttons Extension`, 'INFO', 
                        'Buttons extension not loaded');
                }
                
            } catch (error) {
                this.logResult('export', `${id} - Export Test`, 'FAIL', error.message);
            }
        });
    }

    // Test responsive behavior
    testResponsiveBehavior() {
        console.log('📱 Testing DataTable Responsive Behavior...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id, element } = tableInfo;
            
            try {
                // Check for Responsive extension
                if (dataTable.responsive) {
                    this.logResult('functionality', `${id} - Responsive Extension`, 'PASS', 
                        'Responsive extension loaded');
                    
                    // Test responsive recalc
                    dataTable.responsive.recalc();
                    this.logResult('functionality', `${id} - Responsive Recalc`, 'PASS', 
                        'Responsive recalculation executed');
                        
                } else {
                    this.logResult('functionality', `${id} - Responsive Extension`, 'INFO', 
                        'Responsive extension not loaded');
                }
                
                // Check table overflow handling
                const wrapper = $(element).closest('.dataTables_wrapper');
                const scrollX = wrapper.find('.dataTables_scrollX');
                
                if (scrollX.length > 0) {
                    this.logResult('functionality', `${id} - Horizontal Scroll`, 'PASS', 
                        'Horizontal scrolling enabled');
                } else {
                    this.logResult('functionality', `${id} - Horizontal Scroll`, 'INFO', 
                        'No horizontal scrolling detected');
                }
                
            } catch (error) {
                this.logResult('functionality', `${id} - Responsive Test`, 'FAIL', error.message);
            }
        });
    }

    // Performance testing
    testPerformance() {
        console.log('⚡ Testing DataTable Performance...');
        
        this.tables.forEach((tableInfo, index) => {
            const { dataTable, id, rows } = tableInfo;
            
            try {
                // Measure draw time
                const startTime = performance.now();
                
                dataTable.draw();
                
                const endTime = performance.now();
                const drawTime = Math.round(endTime - startTime);
                
                const status = drawTime < 100 ? 'PASS' : drawTime < 500 ? 'WARN' : 'FAIL';
                this.logResult('performance', `${id} - Draw Time`, status, 
                    `${drawTime}ms for ${rows} rows`);
                
                // Memory usage estimate
                const settings = dataTable.settings()[0];
                const memoryEstimate = JSON.stringify(settings.aoData).length;
                
                this.logResult('performance', `${id} - Memory Usage`, 'INFO', 
                    `~${Math.round(memoryEstimate / 1024)}KB estimated`);
                
            } catch (error) {
                this.logResult('performance', `${id} - Performance Test`, 'FAIL', error.message);
            }
        });
    }

    // Helper method to log results
    logResult(category, test, status, details) {
        const result = {
            test,
            status,
            details,
            timestamp: new Date().toISOString()
        };
        
        this.testResults[category].push(result);
        
        const icon = status === 'PASS' ? '✅' : status === 'FAIL' ? '❌' : status === 'WARN' ? '⚠️' : 'ℹ️';
        console.log(`${icon} ${test}: ${details}`);
    }

    // Generate comprehensive report
    generateReport() {
        console.log('\n📋 DATATABLE TEST REPORT');
        console.log('==========================================');
        
        let totalTests = 0;
        let passedTests = 0;
        let failedTests = 0;
        let warnings = 0;
        
        Object.keys(this.testResults).forEach(category => {
            const results = this.testResults[category];
            
            if (results.length > 0) {
                console.log(`\n📊 ${category.toUpperCase()} TESTS:`);
                
                results.forEach(result => {
                    totalTests++;
                    if (result.status === 'PASS') passedTests++;
                    else if (result.status === 'FAIL') failedTests++;
                    else if (result.status === 'WARN') warnings++;
                    
                    const icon = result.status === 'PASS' ? '✅' : 
                               result.status === 'FAIL' ? '❌' : 
                               result.status === 'WARN' ? '⚠️' : 'ℹ️';
                    console.log(`   ${icon} ${result.test}: ${result.details}`);
                });
            }
        });
        
        console.log('\n📈 SUMMARY:');
        console.log(`   Total Tests: ${totalTests}`);
        console.log(`   Passed: ${passedTests}`);
        console.log(`   Failed: ${failedTests}`);
        console.log(`   Warnings: ${warnings}`);
        console.log(`   Success Rate: ${totalTests > 0 ? ((passedTests / totalTests) * 100).toFixed(1) : 0}%`);
        
        return {
            summary: { totalTests, passedTests, failedTests, warnings },
            results: this.testResults,
            tables: this.tables
        };
    }

    // Run all DataTable tests
    async runAllTests() {
        console.log('🚀 Starting DataTable Test Suite...');
        console.log('==========================================');
        
        // Discover tables first
        this.discoverDataTables();
        
        if (this.tables.length === 0) {
            console.log('❌ No DataTables found on this page');
            return;
        }
        
        // Run all tests
        this.testInitialization();
        this.testSorting();
        this.testSearch();
        this.testPagination();
        this.testResponsiveBehavior();
        this.testExportFunctionality();
        this.testPerformance();
        
        // AJAX tests (with delay)
        setTimeout(() => {
            this.testAjaxFunctionality();
            
            // Generate report after all tests
            setTimeout(() => {
                this.generateReport();
            }, 2000);
        }, 1000);
    }
}

// Initialize DataTable tester
const dataTableTester = new DataTableTester();

// Make available globally
window.dataTableTester = dataTableTester;

// Auto-run tests
console.log('DataTable Tester loaded. Running tests...');
dataTableTester.runAllTests();

// Additional helper functions
window.testSpecificDataTable = function(tableId) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Table not found:', tableId);
        return;
    }
    
    if (!$.fn.DataTable.isDataTable(table)) {
        console.error('Table is not a DataTable:', tableId);
        return;
    }
    
    const dt = $(table).DataTable();
    console.log('🔍 Testing specific DataTable:', tableId);
    console.log('   API:', dt);
    console.log('   Settings:', dt.settings()[0]);
    console.log('   Info:', dt.page.info());
};

window.benchmarkDataTable = function(tableId, iterations = 10) {
    const table = document.getElementById(tableId);
    if (!table || !$.fn.DataTable.isDataTable(table)) {
        console.error('DataTable not found:', tableId);
        return;
    }
    
    const dt = $(table).DataTable();
    const times = [];
    
    console.log(`🏃 Benchmarking DataTable ${tableId} with ${iterations} iterations...`);
    
    for (let i = 0; i < iterations; i++) {
        const start = performance.now();
        dt.draw();
        const end = performance.now();
        times.push(end - start);
    }
    
    const avgTime = times.reduce((a, b) => a + b) / times.length;
    const minTime = Math.min(...times);
    const maxTime = Math.max(...times);
    
    console.log('📊 Benchmark Results:');
    console.log(`   Average: ${avgTime.toFixed(2)}ms`);
    console.log(`   Min: ${minTime.toFixed(2)}ms`);
    console.log(`   Max: ${maxTime.toFixed(2)}ms`);
    
    return { avgTime, minTime, maxTime, times };
};

console.log('🔧 Additional DataTable testing functions available:');
console.log('- testSpecificDataTable(tableId) - Test specific table');
console.log('- benchmarkDataTable(tableId, iterations) - Performance benchmark');
console.log('- dataTableTester.runAllTests() - Run all tests again');