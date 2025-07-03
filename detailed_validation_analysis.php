<?php

/**
 * DETAILED VALIDATION ANALYSIS SCRIPT
 * Analyzes current validation implementation and identifies specific gaps
 */

class DetailedValidationAnalyzer
{
    private $validationRules = [];
    private $validationGaps = [];
    private $securityIssues = [];

    public function analyzeValidation()
    {
        echo "🔍 DETAILED VALIDATION ANALYSIS\n";
        echo "==============================\n\n";

        $this->analyzeClienteValidation();
        $this->analyzeVehiculoValidation();
        $this->analyzeServicioValidation();
        $this->analyzeEmpleadoValidation();
        $this->analyzeOrdenTrabajoValidation();
        $this->analyzeProfileValidation();
        
        $this->generateValidationReport();
        
        return true;
    }

    private function analyzeClienteValidation()
    {
        echo "📋 ANALYZING CLIENTES VALIDATION\n";
        echo "==============================\n";

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clientes,email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'document_number' => 'required|string|unique:clientes,document_number',
            'status' => 'boolean'
        ];

        $this->validationRules['CLIENTES'] = $rules;

        // Identify validation gaps
        $gaps = [];
        
        // Phone validation gaps
        if (!strpos($rules['phone'], 'min:') && !strpos($rules['phone'], 'max:')) {
            $gaps[] = "Phone field has no length restrictions - vulnerable to buffer overflow";
        }
        
        if (!strpos($rules['phone'], 'regex:')) {
            $gaps[] = "Phone field has no format validation - accepts any string";
        }

        // Address validation gaps
        if (!strpos($rules['address'], 'max:')) {
            $gaps[] = "Address field has no maximum length - vulnerable to buffer overflow";
        }

        // Document number validation gaps
        if (!strpos($rules['document_number'], 'max:')) {
            $gaps[] = "Document number has no maximum length restriction";
        }

        if (!strpos($rules['document_number'], 'regex:') && !strpos($rules['document_number'], 'alpha_num')) {
            $gaps[] = "Document number allows special characters - potential injection vector";
        }

        // General gaps
        $gaps[] = "No input sanitization rules to prevent XSS";
        $gaps[] = "No SQL injection protection beyond Laravel's basic escaping";
        $gaps[] = "No rate limiting validation";

        $this->validationGaps['CLIENTES'] = $gaps;

        foreach ($gaps as $gap) {
            echo "  ⚠️  $gap\n";
        }
        echo "\n";
    }

    private function analyzeVehiculoValidation()
    {
        echo "🚗 ANALYZING VEHICULOS VALIDATION\n";
        echo "===============================\n";

        $rules = [
            'cliente_id' => 'required|exists:clientes,id',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'license_plate' => 'required|string|unique:vehiculos,license_plate',
            'vin' => 'required|string|unique:vehiculos,vin',
            'color' => 'required|string',
            'status' => 'boolean'
        ];

        $this->validationRules['VEHICULOS'] = $rules;

        $gaps = [];

        // License plate validation gaps
        if (!strpos($rules['license_plate'], 'regex:')) {
            $gaps[] = "License plate has no format validation - should follow country-specific patterns";
        }

        if (!strpos($rules['license_plate'], 'max:')) {
            $gaps[] = "License plate has no maximum length restriction";
        }

        // VIN validation gaps
        if (!strpos($rules['vin'], 'size:17') && !strpos($rules['vin'], 'min:17|max:17')) {
            $gaps[] = "VIN should be exactly 17 characters - current validation allows any length";
        }

        if (!strpos($rules['vin'], 'regex:')) {
            $gaps[] = "VIN has no format validation - should be alphanumeric with specific pattern";
        }

        // Color validation gaps
        if (!strpos($rules['color'], 'max:')) {
            $gaps[] = "Color field has no maximum length restriction";
        }

        if (!strpos($rules['color'], 'alpha')) {
            $gaps[] = "Color field allows numbers and special characters";
        }

        // Brand/Model validation gaps
        if (!strpos($rules['brand'], 'alpha_dash') && !strpos($rules['brand'], 'regex:')) {
            $gaps[] = "Brand field allows special characters that could be injection vectors";
        }

        if (!strpos($rules['model'], 'alpha_dash') && !strpos($rules['model'], 'regex:')) {
            $gaps[] = "Model field allows special characters that could be injection vectors";
        }

        $this->validationGaps['VEHICULOS'] = $gaps;

        foreach ($gaps as $gap) {
            echo "  ⚠️  $gap\n";
        }
        echo "\n";
    }

    private function analyzeServicioValidation()
    {
        echo "🔧 ANALYZING SERVICIOS VALIDATION\n";
        echo "===============================\n";

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_hours' => 'required|numeric|min:0',
            'status' => 'boolean'
        ];

        $this->validationRules['SERVICIOS'] = $rules;

        $gaps = [];

        // Price validation gaps
        if (!strpos($rules['price'], 'max:')) {
            $gaps[] = "Price has no maximum limit - vulnerable to extremely high values";
        }

        if (!strpos($rules['price'], 'decimal:')) {
            $gaps[] = "Price precision not controlled - could cause calculation errors";
        }

        // Duration validation gaps
        if (!strpos($rules['duration_hours'], 'max:')) {
            $gaps[] = "Duration has no maximum limit - could accept unrealistic values";
        }

        if (strpos($rules['price'], 'min:0') !== false) {
            $gaps[] = "Price allows zero - business logic issue";
        }

        // Description validation gaps
        if (!strpos($rules['description'], 'max:')) {
            $gaps[] = "Description has no maximum length - vulnerable to buffer overflow";
        }

        // Name validation gaps
        if (!strpos($rules['name'], 'unique:')) {
            $gaps[] = "Service name not unique - could cause business logic issues";
        }

        $this->validationGaps['SERVICIOS'] = $gaps;

        foreach ($gaps as $gap) {
            echo "  ⚠️  $gap\n";
        }
        echo "\n";
    }

    private function analyzeEmpleadoValidation()
    {
        echo "👥 ANALYZING EMPLEADOS VALIDATION\n";
        echo "===============================\n";

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:empleados,email',
            'phone' => 'required|string',
            'position' => 'required|string',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'boolean'
        ];

        $this->validationRules['EMPLEADOS'] = $rules;

        $gaps = [];

        // Salary validation gaps
        if (!strpos($rules['salary'], 'max:')) {
            $gaps[] = "Salary has no maximum limit - could accept unrealistic values";
        }

        if (strpos($rules['salary'], 'min:0') !== false) {
            $gaps[] = "Salary allows zero - business logic issue";
        }

        // Hire date validation gaps
        if (!strpos($rules['hire_date'], 'before:') && !strpos($rules['hire_date'], 'before_or_equal:today')) {
            $gaps[] = "Hire date allows future dates - business logic issue";
        }

        // Phone validation gaps (same as clientes)
        if (!strpos($rules['phone'], 'regex:')) {
            $gaps[] = "Phone field has no format validation";
        }

        // Position validation gaps
        if (!strpos($rules['position'], 'max:')) {
            $gaps[] = "Position field has no maximum length restriction";
        }

        if (!strpos($rules['position'], 'in:') && !strpos($rules['position'], 'regex:')) {
            $gaps[] = "Position field not restricted to valid job titles";
        }

        $this->validationGaps['EMPLEADOS'] = $gaps;

        foreach ($gaps as $gap) {
            echo "  ⚠️  $gap\n";
        }
        echo "\n";
    }

    private function analyzeOrdenTrabajoValidation()
    {
        echo "📋 ANALYZING ORDENES VALIDATION\n";
        echo "=============================\n";

        $rules = [
            'cliente_id' => 'required|exists:clientes,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'empleado_id' => 'required|exists:empleados,id',
            'servicio_id' => 'required|exists:servicios,id',
            'description' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'total_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date'
        ];

        $this->validationRules['ORDENES'] = $rules;

        $gaps = [];

        // Business logic validation gaps
        if (!strpos($rules['vehiculo_id'], 'vehiculo_belongs_to_cliente')) {
            $gaps[] = "No validation that selected vehicle belongs to selected client";
        }

        // Total amount validation gaps
        if (!strpos($rules['total_amount'], 'max:')) {
            $gaps[] = "Total amount has no maximum limit";
        }

        // Date validation gaps
        if (!strpos($rules['start_date'], 'after_or_equal:today')) {
            $gaps[] = "Start date allows past dates - business logic issue";
        }

        // Description validation gaps
        if (!strpos($rules['description'], 'max:')) {
            $gaps[] = "Description has no maximum length restriction";
        }

        // Foreign key validation gaps
        $gaps[] = "No validation for employee availability on selected dates";
        $gaps[] = "No validation for service duration vs date range";

        $this->validationGaps['ORDENES'] = $gaps;

        foreach ($gaps as $gap) {
            echo "  ⚠️  $gap\n";
        }
        echo "\n";
    }

    private function analyzeProfileValidation()
    {
        echo "👤 ANALYZING PROFILE VALIDATION\n";
        echo "=============================\n";

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users'
        ];

        $this->validationRules['PROFILE'] = $rules;

        $gaps = [];

        // Password validation gaps
        $gaps[] = "No password complexity requirements defined";
        $gaps[] = "No password history validation";
        $gaps[] = "No password age restrictions";

        // Email validation gaps
        if (!strpos($rules['email'], 'confirmed')) {
            $gaps[] = "Email changes don't require confirmation";
        }

        // General security gaps
        $gaps[] = "No rate limiting for profile updates";
        $gaps[] = "No audit logging for profile changes";

        $this->validationGaps['PROFILE'] = $gaps;

        foreach ($gaps as $gap) {
            echo "  ⚠️  $gap\n";
        }
        echo "\n";
    }

    private function generateValidationReport()
    {
        echo "📊 GENERATING VALIDATION ANALYSIS REPORT\n";
        echo "======================================\n\n";

        $totalGaps = 0;
        $criticalGaps = 0;
        $securityGaps = 0;

        foreach ($this->validationGaps as $module => $gaps) {
            $totalGaps += count($gaps);
            foreach ($gaps as $gap) {
                if (strpos($gap, 'injection') !== false || strpos($gap, 'XSS') !== false) {
                    $criticalGaps++;
                } elseif (strpos($gap, 'buffer overflow') !== false || strpos($gap, 'vulnerable') !== false) {
                    $securityGaps++;
                }
            }
        }

        $report = [
            'analysis_summary' => [
                'modules_analyzed' => count($this->validationRules),
                'total_validation_gaps' => $totalGaps,
                'critical_security_gaps' => $criticalGaps,
                'security_related_gaps' => $securityGaps,
                'analysis_date' => date('Y-m-d H:i:s')
            ],
            'current_validation_rules' => $this->validationRules,
            'validation_gaps_by_module' => $this->validationGaps,
            'security_recommendations' => $this->generateSecurityRecommendations(),
            'implementation_priorities' => $this->generateImplementationPriorities()
        ];

        // Save detailed report
        file_put_contents('/mnt/c/Users/lukka/taller-sistema/DETAILED_VALIDATION_ANALYSIS_REPORT.json', json_encode($report, JSON_PRETTY_PRINT));
        
        // Generate markdown report
        $this->generateValidationMarkdownReport($report);

        // Display summary
        echo "📈 VALIDATION ANALYSIS SUMMARY:\n";
        echo "==============================\n";
        echo "• Modules Analyzed: " . count($this->validationRules) . "\n";
        echo "• Total Validation Gaps: $totalGaps\n";
        echo "• Critical Security Gaps: $criticalGaps 🔴\n";
        echo "• Security-Related Gaps: $securityGaps 🟠\n\n";

        echo "🎯 TOP PRIORITY FIXES:\n";
        echo "=====================\n";
        echo "1. Implement input sanitization to prevent XSS attacks\n";
        echo "2. Add proper length restrictions to prevent buffer overflow\n";
        echo "3. Implement format validation for structured data (phone, VIN, etc.)\n";
        echo "4. Add business logic validation for dates and amounts\n";
        echo "5. Implement rate limiting for all form submissions\n\n";

        echo "💾 Reports Generated:\n";
        echo "• JSON: DETAILED_VALIDATION_ANALYSIS_REPORT.json\n";
        echo "• Markdown: DETAILED_VALIDATION_ANALYSIS_REPORT.md\n\n";
        echo "✅ DETAILED VALIDATION ANALYSIS COMPLETED\n";
    }

    private function generateSecurityRecommendations()
    {
        return [
            'immediate_fixes' => [
                'Add input sanitization rules: sanitize_html, strip_tags',
                'Implement length restrictions on all text fields',
                'Add regex validation for structured data (phone, VIN, license plates)',
                'Implement SQL injection protection beyond basic escaping',
                'Add XSS protection headers and content security policy'
            ],
            'validation_improvements' => [
                'Add unique constraints where business logic requires',
                'Implement business rule validation (future dates, realistic values)',
                'Add foreign key relationship validation',
                'Implement decimal precision control for monetary values',
                'Add enum validation for status fields'
            ],
            'security_enhancements' => [
                'Implement rate limiting per IP and per user',
                'Add CAPTCHA for sensitive operations',
                'Implement audit logging for all CRUD operations',
                'Add session security (CSRF, secure cookies)',
                'Implement password complexity requirements'
            ]
        ];
    }

    private function generateImplementationPriorities()
    {
        return [
            'critical' => [
                'CLIENTES: Add input sanitization and length restrictions',
                'VEHICULOS: Implement VIN format validation (17 characters, alphanumeric)',
                'SERVICIOS: Add maximum price and duration limits',
                'EMPLEADOS: Implement hire date validation (no future dates)',
                'ORDENES: Add business logic validation for vehicle-client relationship'
            ],
            'high' => [
                'Implement phone number format validation across all modules',
                'Add proper error handling to prevent information disclosure',
                'Implement rate limiting for all form submissions',
                'Add CSRF protection verification',
                'Implement proper session management'
            ],
            'medium' => [
                'Add unique validation where business logic requires',
                'Implement audit logging for security events',
                'Add password complexity requirements',
                'Implement content security policy',
                'Add backup validation for critical operations'
            ]
        ];
    }

    private function generateValidationMarkdownReport($data)
    {
        $md = "# DETAILED VALIDATION ANALYSIS REPORT\n\n";
        $md .= "**Analysis Date:** {$data['analysis_summary']['analysis_date']}  \n";
        $md .= "**Application:** Laravel Taller Sistema  \n\n";

        $md .= "## 📊 Analysis Summary\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Modules Analyzed | {$data['analysis_summary']['modules_analyzed']} |\n";
        $md .= "| Total Validation Gaps | {$data['analysis_summary']['total_validation_gaps']} |\n";
        $md .= "| Critical Security Gaps | {$data['analysis_summary']['critical_security_gaps']} |\n";
        $md .= "| Security-Related Gaps | {$data['analysis_summary']['security_related_gaps']} |\n\n";

        $md .= "## 🔍 Current Validation Rules\n\n";
        foreach ($data['current_validation_rules'] as $module => $rules) {
            $md .= "### $module Module\n";
            foreach ($rules as $field => $rule) {
                $md .= "- **$field:** `$rule`\n";
            }
            $md .= "\n";
        }

        $md .= "## ⚠️ Validation Gaps by Module\n\n";
        foreach ($data['validation_gaps_by_module'] as $module => $gaps) {
            $md .= "### $module Module Gaps\n";
            foreach ($gaps as $gap) {
                $md .= "- $gap\n";
            }
            $md .= "\n";
        }

        $md .= "## 🛠️ Security Recommendations\n\n";
        $md .= "### 🔴 Immediate Fixes Required\n";
        foreach ($data['security_recommendations']['immediate_fixes'] as $fix) {
            $md .= "- $fix\n";
        }
        $md .= "\n";

        $md .= "### 🟡 Validation Improvements\n";
        foreach ($data['security_recommendations']['validation_improvements'] as $improvement) {
            $md .= "- $improvement\n";
        }
        $md .= "\n";

        $md .= "### 🟢 Security Enhancements\n";
        foreach ($data['security_recommendations']['security_enhancements'] as $enhancement) {
            $md .= "- $enhancement\n";
        }
        $md .= "\n";

        $md .= "## 🎯 Implementation Priorities\n\n";
        $md .= "### 🔴 Critical Priority\n";
        foreach ($data['implementation_priorities']['critical'] as $item) {
            $md .= "- $item\n";
        }
        $md .= "\n";

        $md .= "### 🟠 High Priority\n";
        foreach ($data['implementation_priorities']['high'] as $item) {
            $md .= "- $item\n";
        }
        $md .= "\n";

        $md .= "### 🟡 Medium Priority\n";
        foreach ($data['implementation_priorities']['medium'] as $item) {
            $md .= "- $item\n";
        }

        file_put_contents('/mnt/c/Users/lukka/taller-sistema/DETAILED_VALIDATION_ANALYSIS_REPORT.md', $md);
    }
}

// Execute the detailed validation analysis
$analyzer = new DetailedValidationAnalyzer();
$analyzer->analyzeValidation();

?>