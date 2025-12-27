/**
 * Health Alert System - Enhanced Micro-interactions
 * 
 * This file contains JavaScript for enhanced user interactions,
 * animations, and dynamic UI behaviors across the application.
 * 
 * Features:
 * - Scroll-triggered animations
 * - Form enhancements
 * - Interactive feedback
 * - Accessibility improvements
 * - Performance optimizations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all interactions
    initScrollAnimations();
    initFormEnhancements();
    initInteractiveElements();
    initAccessibilityFeatures();
    initPerformanceOptimizations();
});

/**
 * Scroll-triggered animations for elements coming into view
 */
function initScrollAnimations() {
    // Create intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                // Unobserve after animation to improve performance
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe all elements with animate-on-scroll class
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
    
    // Staggered animations for card grids
    const cardGrids = document.querySelectorAll('.card-grid');
    cardGrids.forEach(grid => {
        const cards = grid.querySelectorAll('.card-hover, .hover-lift');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-fade-in-up');
        });
    });
}

/**
 * Enhanced form interactions and validation
 */
function initFormEnhancements() {
    // Real-time validation feedback
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        // Add focus/blur animations
        input.addEventListener('focus', function() {
            this.closest('.form-group')?.classList.add('focused');
            addRippleEffect(this);
        });
        
        input.addEventListener('blur', function() {
            this.closest('.form-group')?.classList.remove('focused');
            validateField(this);
        });
        
        // Real-time validation for specific field types
        if (input.type === 'email') {
            input.addEventListener('input', debounce(() => validateEmail(input), 300));
        }
        
        if (input.type === 'password') {
            input.addEventListener('input', () => updatePasswordStrength(input));
        }
        
        if (input.type === 'number') {
            input.addEventListener('input', () => validateNumber(input));
        }
    });
    
    // Enhanced form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                addLoadingState(submitBtn);
                
                // Add form validation
                if (!validateForm(this)) {
                    e.preventDefault();
                    removeLoadingState(submitBtn);
                    showFormErrors(this);
                }
            }
        });
    });
}

/**
 * Interactive element enhancements
 */
function initInteractiveElements() {
    // Enhanced button interactions
    const buttons = document.querySelectorAll('button, .btn, .interactive-element');
    buttons.forEach(button => {
        // Add ripple effect on click
        button.addEventListener('click', function(e) {
            if (!this.classList.contains('btn-ripple')) return;
            
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple-effect');
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
        
        // Add hover sound effect (optional)
        button.addEventListener('mouseenter', function() {
            if (this.classList.contains('hover-sound')) {
                playHoverSound();
            }
        });
    });
    
    // Enhanced card interactions
    const cards = document.querySelectorAll('.card-hover, .hover-lift');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
        
        // Add click animation
        card.addEventListener('click', function() {
            this.classList.add('animate-pulse');
            setTimeout(() => this.classList.remove('animate-pulse'), 300);
        });
    });
    
    // Status badge animations
    const statusBadges = document.querySelectorAll('.status-badge, [class*="status-"]');
    statusBadges.forEach(badge => {
        // Add pulse animation for critical statuses
        if (badge.textContent.toLowerCase().includes('critical') || 
            badge.classList.contains('status-critical')) {
            badge.classList.add('animate-heartbeat');
        }
        
        // Add glow effect for warnings
        if (badge.textContent.toLowerCase().includes('warning') || 
            badge.classList.contains('status-warning')) {
            badge.classList.add('animate-glow');
        }
    });
    
    // Enhanced table interactions
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            // Remove active class from other rows
            tableRows.forEach(r => r.classList.remove('bg-primary-50'));
            // Add active class to clicked row
            this.classList.add('bg-primary-50');
        });
    });
}

/**
 * Accessibility enhancements
 */
function initAccessibilityFeatures() {
    // Keyboard navigation enhancements
    document.addEventListener('keydown', function(e) {
        // Escape key to close modals/dropdowns
        if (e.key === 'Escape') {
            closeAllModals();
            closeAllDropdowns();
        }
        
        // Tab navigation improvements
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    // Mouse usage detection
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
    
    // Focus management for dynamic content
    const dynamicContent = document.querySelectorAll('[data-dynamic]');
    dynamicContent.forEach(element => {
        const observer = new MutationObserver(() => {
            manageFocus(element);
        });
        observer.observe(element, { childList: true, subtree: true });
    });
    
    // Screen reader announcements
    createAriaLiveRegion();
}

/**
 * Performance optimizations
 */
function initPerformanceOptimizations() {
    // Lazy load images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // Debounce scroll events
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        if (scrollTimeout) clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(handleScroll, 16); // ~60fps
    });
    
    // Optimize animations for reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.style.setProperty('--animation-duration', '0.01ms');
        document.documentElement.style.setProperty('--transition-duration', '0.01ms');
    }
}

/**
 * Utility functions
 */

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function validateField(field) {
    const value = field.value.trim();
    const fieldType = field.type;
    const isRequired = field.hasAttribute('required');
    
    // Remove existing validation classes
    field.classList.remove('error', 'success');
    
    // Check if required field is empty
    if (isRequired && !value) {
        addFieldError(field, 'This field is required');
        return false;
    }
    
    // Type-specific validation
    if (value) {
        switch (fieldType) {
            case 'email':
                if (!isValidEmail(value)) {
                    addFieldError(field, 'Please enter a valid email address');
                    return false;
                }
                break;
            case 'number':
                if (isNaN(value) || value < 0) {
                    addFieldError(field, 'Please enter a valid number');
                    return false;
                }
                break;
        }
    }
    
    // Field is valid
    field.classList.add('success');
    removeFieldError(field);
    return true;
}

function validateEmail(input) {
    const email = input.value.trim();
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    
    if (email && !isValid) {
        addFieldError(input, 'Please enter a valid email address');
    } else if (email && isValid) {
        input.classList.add('success');
        removeFieldError(input);
    }
}

function validateNumber(input) {
    const value = input.value;
    const min = input.getAttribute('min');
    const max = input.getAttribute('max');
    
    if (value && isNaN(value)) {
        addFieldError(input, 'Please enter a valid number');
        return;
    }
    
    if (min && value < parseFloat(min)) {
        addFieldError(input, `Value must be at least ${min}`);
        return;
    }
    
    if (max && value > parseFloat(max)) {
        addFieldError(input, `Value must be no more than ${max}`);
        return;
    }
    
    if (value) {
        input.classList.add('success');
        removeFieldError(input);
    }
}

function updatePasswordStrength(input) {
    const password = input.value;
    const strength = calculatePasswordStrength(password);
    
    // Remove existing strength indicators
    const existingIndicator = input.parentElement.querySelector('.password-strength');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    if (password.length > 0) {
        const indicator = createPasswordStrengthIndicator(strength);
        input.parentElement.appendChild(indicator);
    }
}

function calculatePasswordStrength(password) {
    let strength = 0;
    const checks = [
        password.length >= 8,
        /[a-z]/.test(password),
        /[A-Z]/.test(password),
        /[0-9]/.test(password),
        /[^A-Za-z0-9]/.test(password)
    ];
    
    strength = checks.filter(Boolean).length;
    return Math.min(strength, 4);
}

function createPasswordStrengthIndicator(strength) {
    const indicator = document.createElement('div');
    indicator.className = 'password-strength mt-2';
    
    const colors = ['red', 'orange', 'yellow', 'lightgreen', 'green'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    
    indicator.innerHTML = `
        <div class="flex items-center space-x-2">
            <div class="flex space-x-1">
                ${Array.from({length: 4}, (_, i) => 
                    `<div class="w-6 h-2 rounded-full ${i < strength ? 'bg-' + colors[strength] + '-500' : 'bg-gray-200'}"></div>`
                ).join('')}
            </div>
            <span class="text-xs text-gray-600">${labels[strength] || 'Very Weak'}</span>
        </div>
    `;
    
    return indicator;
}

function addFieldError(field, message) {
    field.classList.add('error');
    field.classList.remove('success');
    
    // Remove existing error message
    removeFieldError(field);
    
    // Add new error message
    const errorElement = document.createElement('p');
    errorElement.className = 'field-error mt-1 text-sm text-red-600 animate-fade-in-up';
    errorElement.textContent = message;
    
    field.parentElement.appendChild(errorElement);
    
    // Announce to screen readers
    announceToScreenReader(`Error: ${message}`);
}

function removeFieldError(field) {
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function validateForm(form) {
    const fields = form.querySelectorAll('input, textarea, select');
    let isValid = true;
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function showFormErrors(form) {
    const firstError = form.querySelector('.error');
    if (firstError) {
        firstError.focus();
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function addLoadingState(button) {
    button.disabled = true;
    button.classList.add('loading');
    
    const originalText = button.textContent;
    button.dataset.originalText = originalText;
    
    const spinner = document.createElement('span');
    spinner.className = 'loading-spinner mr-2';
    
    button.innerHTML = '';
    button.appendChild(spinner);
    button.appendChild(document.createTextNode('Loading...'));
}

function removeLoadingState(button) {
    button.disabled = false;
    button.classList.remove('loading');
    
    const originalText = button.dataset.originalText || 'Submit';
    button.textContent = originalText;
}

function addRippleEffect(element) {
    if (!element.classList.contains('btn-ripple')) return;
    
    const ripple = document.createElement('span');
    ripple.className = 'ripple-animation';
    element.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
}

function handleScroll() {
    const scrolled = window.pageYOffset;
    const rate = scrolled * -0.5;
    
    // Parallax effect for hero sections
    const parallaxElements = document.querySelectorAll('.parallax');
    parallaxElements.forEach(element => {
        element.style.transform = `translateY(${rate}px)`;
    });
    
    // Show/hide scroll-to-top button
    const scrollTopBtn = document.getElementById('scroll-to-top');
    if (scrollTopBtn) {
        if (scrolled > 300) {
            scrollTopBtn.classList.add('visible');
        } else {
            scrollTopBtn.classList.remove('visible');
        }
    }
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal, [data-modal]');
    modals.forEach(modal => {
        modal.classList.add('hidden');
    });
}

function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-open, [data-dropdown-open]');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('dropdown-open');
        dropdown.removeAttribute('data-dropdown-open');
    });
}

function manageFocus(container) {
    const focusableElements = container.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length > 0) {
        focusableElements[0].focus();
    }
}

function createAriaLiveRegion() {
    if (document.getElementById('aria-live-region')) return;
    
    const liveRegion = document.createElement('div');
    liveRegion.id = 'aria-live-region';
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only';
    
    document.body.appendChild(liveRegion);
}

function announceToScreenReader(message) {
    const liveRegion = document.getElementById('aria-live-region');
    if (liveRegion) {
        liveRegion.textContent = message;
        
        // Clear after announcement
        setTimeout(() => {
            liveRegion.textContent = '';
        }, 1000);
    }
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function playHoverSound() {
    // Optional: Add subtle hover sound effect
    // This would require audio files and user permission
    if (window.AudioContext && window.hoverSoundEnabled) {
        // Implementation for hover sound
    }
}

// Export functions for use in other scripts
window.HealthAlertInteractions = {
    validateField,
    validateForm,
    addLoadingState,
    removeLoadingState,
    announceToScreenReader,
    debounce
};