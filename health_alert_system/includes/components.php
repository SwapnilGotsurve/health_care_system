<?php
/**
 * Reusable UI Components Library
 * 
 * This file contains reusable PHP functions for generating consistent
 * UI components across the Health Alert System. All components use
 * Tailwind CSS classes for styling and follow the system's design patterns.
 * 
 * Component Categories:
 * - Status badges and indicators
 * - Health status evaluation
 * - Data display cards
 * - Form components
 * - Navigation elements
 * - Alert and notification components
 * 
 * @author Health Alert System Team
 * @version 1.0
 */

/**
 * Render Status Badge Component
 * 
 * Creates a styled badge element for displaying status information
 * with consistent colors and typography across the application.
 * 
 * @param string $status The status text to display
 * @param string $type Badge color scheme: success, warning, danger, info, primary, gray
 * @param bool $pulse Whether to add subtle pulse animation for active states
 * @return string HTML markup for the status badge
 * 
 * @example
 * echo render_status_badge('Active', 'success', true);
 * echo render_status_badge('Pending', 'warning');
 */
function render_status_badge($status, $type = 'info', $pulse = false) {
    // Base classes for all badges - consistent sizing and typography
    $base_classes = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";
    
    // Optional pulse animation for dynamic states
    $pulse_class = $pulse ? " animate-pulse-slow" : "";
    
    // Color schemes mapped to Tailwind CSS utility classes
    $type_classes = [
        'success' => 'bg-success-100 text-success-800',    // Green for positive states
        'warning' => 'bg-yellow-100 text-yellow-800',      // Yellow for caution states
        'danger' => 'bg-danger-100 text-danger-800',       // Red for critical states
        'info' => 'bg-blue-100 text-blue-800',             // Blue for informational
        'primary' => 'bg-primary-100 text-primary-800',    // Brand color for primary actions
        'gray' => 'bg-gray-100 text-gray-800'              // Gray for neutral states
    ];
    
    // Combine classes with fallback to info type
    $classes = $base_classes . ' ' . ($type_classes[$type] ?? $type_classes['info']) . $pulse_class;
    
    // Return sanitized HTML with XSS protection
    return "<span class=\"{$classes}\">" . htmlspecialchars($status) . "</span>";
}

/**
 * Render Health Status Badge Component
 * 
 * Evaluates patient health data and returns an appropriate status badge
 * based on medical thresholds for blood pressure, sugar levels, and heart rate.
 * 
 * Health Status Criteria:
 * - Critical: Systolic ≥180, Diastolic ≥120, Sugar ≥300, HR ≥180 or ≤50
 * - Warning: Systolic 140-179, Diastolic 90-119, Sugar 180-299, HR 100-179 or 51-59
 * - Normal: All values within healthy ranges
 * 
 * @param array $health_data Associative array with keys: systolic_bp, diastolic_bp, sugar_level, heart_rate
 * @return string HTML markup for the health status badge
 * 
 * @example
 * $data = ['systolic_bp' => 120, 'diastolic_bp' => 80, 'sugar_level' => 95, 'heart_rate' => 72];
 * echo render_health_status_badge($data);
 */
function render_health_status_badge($health_data) {
    // Convert values to appropriate numeric types for comparison
    $systolic = (int)$health_data['systolic_bp'];
    $diastolic = (int)$health_data['diastolic_bp'];
    $sugar = (float)$health_data['sugar_level'];
    $heart_rate = (int)$health_data['heart_rate'];
    
    // Initialize status flags
    $is_critical = false;
    $is_warning = false;
    
    /**
     * Critical Health Conditions (Immediate Medical Attention)
     * 
     * Based on standard medical guidelines:
     * - Hypertensive Crisis: Systolic ≥180 or Diastolic ≥120
     * - Severe Hyperglycemia: Blood sugar ≥300 mg/dL
     * - Dangerous Heart Rate: ≥180 bpm (tachycardia) or ≤50 bpm (bradycardia)
     */
    if ($systolic >= 180 || $diastolic >= 120 || $sugar >= 300 || $heart_rate >= 180 || $heart_rate <= 50) {
        $is_critical = true;
    }
    // Warning conditions
    elseif ($systolic >= 140 || $diastolic >= 90 || $sugar >= 200 || $sugar <= 80 || $heart_rate >= 150 || $heart_rate <= 60) {
        $is_warning = true;
    }
    
    if ($is_critical) {
        return render_status_badge('Critical', 'danger', true);
    } elseif ($is_warning) {
        return render_status_badge('Warning', 'warning', true);
    } else {
        return render_status_badge('Normal', 'success');
    }
}

/**
 * Render a data card with consistent styling
 * @param string $title - Card title
 * @param string $value - Main value to display
 * @param string $subtitle - Optional subtitle
 * @param string $icon_path - SVG path for icon
 * @param string $color - Color theme: primary, success, warning, danger
 * @param bool $animate - Whether to add hover animations
 * @return string HTML for the data card
 */
function render_data_card($title, $value, $subtitle = '', $icon_path = '', $color = 'primary', $animate = true) {
    $animate_class = $animate ? ' hover-lift card-hover animate-scale-in' : '';
    
    $color_classes = [
        'primary' => 'border-primary-200 bg-primary-50',
        'success' => 'border-success-200 bg-success-50',
        'warning' => 'border-yellow-200 bg-yellow-50',
        'danger' => 'border-danger-200 bg-danger-50'
    ];
    
    $icon_colors = [
        'primary' => 'text-primary-600',
        'success' => 'text-success-600',
        'warning' => 'text-yellow-600',
        'danger' => 'text-danger-600'
    ];
    
    $card_color = $color_classes[$color] ?? $color_classes['primary'];
    $icon_color = $icon_colors[$color] ?? $icon_colors['primary'];
    
    $icon_html = '';
    if ($icon_path) {
        $icon_html = "
            <div class=\"flex-shrink-0\">
                <div class=\"w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm\">
                    <svg class=\"w-5 h-5 {$icon_color}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"{$icon_path}\"></path>
                    </svg>
                </div>
            </div>
        ";
    }
    
    return "
        <div class=\"bg-white border {$card_color} rounded-lg p-6 shadow-sm{$animate_class}\">
            <div class=\"flex items-center\">
                {$icon_html}
                <div class=\"" . ($icon_path ? 'ml-4' : '') . "\">
                    <p class=\"text-sm font-medium text-gray-600\">{$title}</p>
                    <p class=\"text-2xl font-bold text-gray-900\">{$value}</p>
                    " . ($subtitle ? "<p class=\"text-sm text-gray-500\">{$subtitle}</p>" : '') . "
                </div>
            </div>
        </div>
    ";
}

/**
 * Render a form input with floating label and validation
 * @param string $name - Input name attribute
 * @param string $label - Label text
 * @param string $type - Input type (text, email, password, number, etc.)
 * @param string $value - Current value
 * @param bool $required - Whether field is required
 * @param string $error - Error message to display
 * @param array $attributes - Additional HTML attributes
 * @return string HTML for the form input
 */
function render_form_input($name, $label, $type = 'text', $value = '', $required = false, $error = '', $attributes = []) {
    $required_attr = $required ? 'required' : '';
    $error_class = $error ? ' error' : '';
    $success_class = (!$error && $value) ? ' success' : '';
    
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= " {$key}=\"" . htmlspecialchars($val) . "\"";
    }
    
    $error_html = '';
    if ($error) {
        $error_html = "<p class=\"mt-1 text-sm text-danger-600 animate-fade-in-up\">{$error}</p>";
    }
    
    return "
        <div class=\"form-group\">
            <input 
                type=\"{$type}\" 
                id=\"{$name}\" 
                name=\"{$name}\" 
                value=\"" . htmlspecialchars($value) . "\"
                class=\"form-input{$error_class}{$success_class}\" 
                placeholder=\" \"
                {$required_attr}
                {$attr_string}
            >
            <label for=\"{$name}\" class=\"form-label\">{$label}</label>
            {$error_html}
        </div>
    ";
}

/**
 * Render a form textarea with floating label
 * @param string $name - Textarea name attribute
 * @param string $label - Label text
 * @param string $value - Current value
 * @param bool $required - Whether field is required
 * @param string $error - Error message to display
 * @param int $rows - Number of rows
 * @return string HTML for the textarea
 */
function render_form_textarea($name, $label, $value = '', $required = false, $error = '', $rows = 4) {
    $required_attr = $required ? 'required' : '';
    $error_class = $error ? ' error' : '';
    $success_class = (!$error && $value) ? ' success' : '';
    
    $error_html = '';
    if ($error) {
        $error_html = "<p class=\"mt-1 text-sm text-danger-600 animate-fade-in-up\">{$error}</p>";
    }
    
    return "
        <div class=\"form-group\">
            <textarea 
                id=\"{$name}\" 
                name=\"{$name}\" 
                rows=\"{$rows}\"
                class=\"form-input{$error_class}{$success_class}\" 
                placeholder=\" \"
                {$required_attr}
            >" . htmlspecialchars($value) . "</textarea>
            <label for=\"{$name}\" class=\"form-label\">{$label}</label>
            {$error_html}
        </div>
    ";
}

/**
 * Render a form select dropdown with floating label
 * @param string $name - Select name attribute
 * @param string $label - Label text
 * @param array $options - Options array [value => text]
 * @param string $selected - Currently selected value
 * @param bool $required - Whether field is required
 * @param string $error - Error message to display
 * @return string HTML for the select
 */
function render_form_select($name, $label, $options, $selected = '', $required = false, $error = '') {
    $required_attr = $required ? 'required' : '';
    $error_class = $error ? ' error' : '';
    $success_class = (!$error && $selected) ? ' success' : '';
    
    $options_html = '<option value="">Choose...</option>';
    foreach ($options as $value => $text) {
        $selected_attr = ($value == $selected) ? 'selected' : '';
        $options_html .= "<option value=\"" . htmlspecialchars($value) . "\" {$selected_attr}>" . htmlspecialchars($text) . "</option>";
    }
    
    $error_html = '';
    if ($error) {
        $error_html = "<p class=\"mt-1 text-sm text-danger-600 animate-fade-in-up\">{$error}</p>";
    }
    
    return "
        <div class=\"form-group\">
            <select 
                id=\"{$name}\" 
                name=\"{$name}\" 
                class=\"form-input{$error_class}{$success_class}\" 
                {$required_attr}
            >
                {$options_html}
            </select>
            <label for=\"{$name}\" class=\"form-label\">{$label}</label>
            {$error_html}
        </div>
    ";
}

/**
 * Render a button with consistent styling and animations
 * @param string $text - Button text
 * @param string $type - Button type: submit, button, reset
 * @param string $style - Button style: primary, secondary, success, warning, danger
 * @param string $size - Button size: sm, md, lg
 * @param bool $loading - Whether to show loading state
 * @param array $attributes - Additional HTML attributes
 * @return string HTML for the button
 */
function render_button($text, $type = 'button', $style = 'primary', $size = 'md', $loading = false, $attributes = []) {
    $base_classes = "inline-flex items-center justify-center font-medium rounded-md transition-all duration-200 focus-ring btn-animate btn-ripple";
    
    $style_classes = [
        'primary' => 'bg-primary-600 hover:bg-primary-700 text-white shadow-sm hover:shadow-md',
        'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-900 shadow-sm hover:shadow-md',
        'success' => 'bg-success-600 hover:bg-success-700 text-white shadow-sm hover:shadow-md',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm hover:shadow-md',
        'danger' => 'bg-danger-600 hover:bg-danger-700 text-white shadow-sm hover:shadow-md',
        'outline' => 'border-2 border-primary-600 text-primary-600 hover:bg-primary-600 hover:text-white'
    ];
    
    $size_classes = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base'
    ];
    
    $classes = $base_classes . ' ' . ($style_classes[$style] ?? $style_classes['primary']) . ' ' . ($size_classes[$size] ?? $size_classes['md']);
    
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= " {$key}=\"" . htmlspecialchars($val) . "\"";
    }
    
    $loading_html = '';
    if ($loading) {
        $loading_html = '<span class="loading-spinner mr-2"></span>';
        $classes .= ' opacity-75 cursor-not-allowed';
        $attr_string .= ' disabled';
    }
    
    return "<button type=\"{$type}\" class=\"{$classes}\"{$attr_string}>{$loading_html}{$text}</button>";
}

/**
 * Render an alert/notification message
 * @param string $message - Alert message
 * @param string $type - Alert type: success, warning, danger, info
 * @param bool $dismissible - Whether alert can be dismissed
 * @return string HTML for the alert
 */
function render_alert($message, $type = 'info', $dismissible = true) {
    $type_classes = [
        'success' => 'bg-success-50 border-success-200 text-success-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'danger' => 'bg-danger-50 border-danger-200 text-danger-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];
    
    $icons = [
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        'danger' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    ];
    
    $classes = 'border rounded-md p-4 animate-fade-in-up ' . ($type_classes[$type] ?? $type_classes['info']);
    $icon_path = $icons[$type] ?? $icons['info'];
    
    $dismiss_button = '';
    if ($dismissible) {
        $dismiss_button = "
            <button type=\"button\" class=\"ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 hover:bg-gray-100 transition-colors\" onclick=\"this.parentElement.remove()\">
                <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path>
                </svg>
            </button>
        ";
    }
    
    return "
        <div class=\"{$classes}\">
            <div class=\"flex items-start\">
                <svg class=\"w-5 h-5 mr-3 mt-0.5 flex-shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"{$icon_path}\"></path>
                </svg>
                <div class=\"flex-1\">{$message}</div>
                {$dismiss_button}
            </div>
        </div>
    ";
}

/**
 * Render a data table with consistent styling
 * @param array $headers - Table headers
 * @param array $rows - Table rows (array of arrays)
 * @param bool $striped - Whether to use striped rows
 * @param bool $hover - Whether to add hover effects
 * @return string HTML for the table
 */
function render_data_table($headers, $rows, $striped = true, $hover = true) {
    $table_classes = 'min-w-full divide-y divide-gray-200';
    $row_classes = $hover ? 'hover:bg-gray-50 transition-colors' : '';
    
    $headers_html = '';
    foreach ($headers as $header) {
        $headers_html .= "<th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($header) . "</th>";
    }
    
    $rows_html = '';
    foreach ($rows as $index => $row) {
        $stripe_class = ($striped && $index % 2 === 0) ? 'bg-white' : 'bg-gray-50';
        $rows_html .= "<tr class=\"{$stripe_class} {$row_classes}\">";
        
        foreach ($row as $cell) {
            $rows_html .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . $cell . "</td>";
        }
        
        $rows_html .= "</tr>";
    }
    
    return "
        <div class=\"overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg animate-fade-in-up\">
            <table class=\"{$table_classes}\">
                <thead class=\"bg-gray-50\">
                    <tr>{$headers_html}</tr>
                </thead>
                <tbody class=\"bg-white divide-y divide-gray-200\">
                    {$rows_html}
                </tbody>
            </table>
        </div>
    ";
}

/**
 * Render pagination controls
 * @param int $current_page - Current page number
 * @param int $total_pages - Total number of pages
 * @param string $base_url - Base URL for pagination links
 * @return string HTML for pagination
 */
function render_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination_html = '<nav class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6 animate-fade-in-up">';
    
    // Previous button
    $prev_disabled = $current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50';
    $prev_url = $current_page > 1 ? $base_url . '?page=' . ($current_page - 1) : '#';
    
    $pagination_html .= "
        <div class=\"flex flex-1 justify-between sm:hidden\">
            <a href=\"{$prev_url}\" class=\"relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 {$prev_disabled}\">Previous</a>
    ";
    
    // Next button (mobile)
    $next_disabled = $current_page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50';
    $next_url = $current_page < $total_pages ? $base_url . '?page=' . ($current_page + 1) : '#';
    
    $pagination_html .= "
            <a href=\"{$next_url}\" class=\"relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 {$next_disabled}\">Next</a>
        </div>
    ";
    
    // Desktop pagination
    $pagination_html .= '<div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">';
    $pagination_html .= "<p class=\"text-sm text-gray-700\">Page <span class=\"font-medium\">{$current_page}</span> of <span class=\"font-medium\">{$total_pages}</span></p>";
    
    $pagination_html .= '<div class="isolate inline-flex -space-x-px rounded-md shadow-sm">';
    
    // Previous button (desktop)
    $pagination_html .= "
        <a href=\"{$prev_url}\" class=\"relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 {$prev_disabled}\">
            <svg class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\">
                <path fill-rule=\"evenodd\" d=\"M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z\" clip-rule=\"evenodd\" />
            </svg>
        </a>
    ";
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active_class = $i === $current_page ? 'bg-primary-600 text-white' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50';
        $page_url = $base_url . '?page=' . $i;
        
        $pagination_html .= "
            <a href=\"{$page_url}\" class=\"relative inline-flex items-center px-4 py-2 text-sm font-semibold {$active_class} focus:z-20 focus:outline-offset-0\">{$i}</a>
        ";
    }
    
    // Next button (desktop)
    $pagination_html .= "
        <a href=\"{$next_url}\" class=\"relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 {$next_disabled}\">
            <svg class=\"h-5 w-5\" viewBox=\"0 0 20 20\" fill=\"currentColor\">
                <path fill-rule=\"evenodd\" d=\"M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z\" clip-rule=\"evenodd\" />
            </svg>
        </a>
    ";
    
    $pagination_html .= '</div></div></nav>';
    
    return $pagination_html;
}

/**
 * Render a loading skeleton for content that's being loaded
 * @param int $lines - Number of skeleton lines
 * @param array $widths - Array of widths for each line (e.g., ['100%', '75%', '50%'])
 * @return string HTML for the skeleton
 */
function render_loading_skeleton($lines = 3, $widths = ['100%', '75%', '50%']) {
    $skeleton_html = '<div class="animate-pulse">';
    
    for ($i = 0; $i < $lines; $i++) {
        $width = $widths[$i % count($widths)];
        $skeleton_html .= "<div class=\"h-4 bg-gray-200 rounded mb-3 skeleton\" style=\"width: {$width}\"></div>";
    }
    
    $skeleton_html .= '</div>';
    
    return $skeleton_html;
}
?>