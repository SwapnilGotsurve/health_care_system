/**
 * User-Friendly JavaScript Enhancements
 * 
 * This file provides enhanced user experience features including:
 * - Loading states
 * - Error handling
 * - Form validation
 * - Confirmation dialogs
 * - Auto-refresh functionality
 * 
 * @author Health Alert System Team
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize user-friendly features
    initializeLoadingStates();
    initializeFormValidation();
    initializeConfirmationDialogs();
    initializeAutoRefresh();
    initializeErrorHandling();
    
});

/**
 * Add loading states to buttons and forms
 */
function initializeLoadingStates() {
    // Add loading state to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                // Disable button and show loading state
                submitButton.disabled = true;
                const originalText = submitButton.textContent || submitButton.value;
                
                if (submitButton.tagName === 'BUTTON') {
                    submitButton.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    `;
                } else {
                    submitButton.value = 'Processing...';
                }
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitButton.disabled = false;
                    if (submitButton.tagName === 'BUTTON') {
                        submitButton.textContent = originalText;
                    } else {
                        submitButton.value = originalText;
                    }
                }, 10000);
            }
        });
    });
    
    // Add loading state to navigation links
    const navLinks = document.querySelectorAll('a[href$=".php"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't add loading to external links or anchors
            if (link.hostname !== window.location.hostname || link.getAttribute('href').startsWith('#')) {
                return;
            }
            
            // Show loading indicator
            showPageLoading();
        });
    });
}

/**
 * Enhanced form validation with user-friendly messages
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Real-time validation feedback
            input.addEventListener('blur', function() {
                validateField(input);
            });
            
            input.addEventListener('input', function() {
                // Clear error state when user starts typing
                clearFieldError(input);
            });
        });
        
        // Enhanced form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showValidationSummary(form);
            }
        });
    });
}

/**
 * Validate individual form field
 */
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.getAttribute('name') || field.getAttribute('id') || 'Field';
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        errorMessage = `${fieldName.replace('_', ' ')} is required`;
        isValid = false;
    }
    
    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        errorMessage = 'Please enter a valid email address';
        isValid = false;
    }
    
    // Number validation
    if (field.type === 'number' && value) {
        const min = field.getAttribute('min');
        const max = field.getAttribute('max');
        const numValue = parseFloat(value);
        
        if (isNaN(numValue)) {
            errorMessage = 'Please enter a valid number';
            isValid = false;
        } else if (min && numValue < parseFloat(min)) {
            errorMessage = `Value must be at least ${min}`;
            isValid = false;
        } else if (max && numValue > parseFloat(max)) {
            errorMessage = `Value must be no more than ${max}`;
            isValid = false;
        }
    }
    
    // Password validation
    if (field.type === 'password' && value && value.length < 6) {
        errorMessage = 'Password must be at least 6 characters long';
        isValid = false;
    }
    
    // Show/hide error message
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

/**
 * Show field error message
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('border-red-500', 'bg-red-50');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-600 text-sm mt-1 field-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Clear field error message
 */
function clearFieldError(field) {
    field.classList.remove('border-red-500', 'bg-red-50');
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Show validation summary
 */
function showValidationSummary(form) {
    const errors = form.querySelectorAll('.field-error');
    if (errors.length > 0) {
        const firstError = errors[0];
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Show toast notification
        showToast('Please fix the highlighted errors', 'error');
    }
}

/**
 * Initialize confirmation dialogs for destructive actions
 */
function initializeConfirmationDialogs() {
    const destructiveActions = document.querySelectorAll('[data-confirm]');
    
    destructiveActions.forEach(element => {
        element.addEventListener('click', function(e) {
            const message = element.getAttribute('data-confirm') || 'Are you sure?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Initialize auto-refresh for dynamic content
 */
function initializeAutoRefresh() {
    // Auto-refresh dashboard stats every 5 minutes
    if (window.location.pathname.includes('dashboard.php')) {
        setInterval(() => {
            refreshDashboardStats();
        }, 300000); // 5 minutes
    }
}

/**
 * Refresh dashboard statistics
 */
function refreshDashboardStats() {
    const statsCards = document.querySelectorAll('[data-stat]');
    
    statsCards.forEach(card => {
        const statType = card.getAttribute('data-stat');
        // Add subtle loading indicator
        card.style.opacity = '0.7';
    });
    
    // In a real implementation, this would make an AJAX call
    // For now, we'll just restore opacity after a short delay
    setTimeout(() => {
        statsCards.forEach(card => {
            card.style.opacity = '1';
        });
    }, 1000);
}

/**
 * Initialize global error handling
 */
function initializeErrorHandling() {
    // Handle JavaScript errors gracefully
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.error);
        showToast('An unexpected error occurred. Please refresh the page.', 'error');
    });
    
    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled Promise Rejection:', e.reason);
        showToast('An unexpected error occurred. Please try again.', 'error');
    });
}

/**
 * Show page loading indicator
 */
function showPageLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'page-loading';
    loadingDiv.className = 'fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50';
    loadingDiv.innerHTML = `
        <div class="text-center">
            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600">Loading...</p>
        </div>
    `;
    
    document.body.appendChild(loadingDiv);
    
    // Remove loading indicator after 10 seconds as fallback
    setTimeout(() => {
        const loading = document.getElementById('page-loading');
        if (loading) {
            loading.remove();
        }
    }, 10000);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-300 translate-x-full`;
    
    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    
    toast.className += ` ${colors[type] || colors.info}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

/**
 * Utility function to validate email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Utility function to format numbers
 */
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

/**
 * Utility function to format dates
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Export functions for use in other scripts
window.HealthAlertSystem = {
    showToast,
    showPageLoading,
    validateField,
    formatNumber,
    formatDate
};