// Main JavaScript file for the e-commerce site

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile menu toggle (if you add mobile menu button)
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('show');
        });
    }
    
    // Quantity input validation
    const quantityInputs = document.querySelectorAll('input[type="number"][name^="quantity"]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseInt(this.min) || 1;
            const max = parseInt(this.max) || 999;
            let value = parseInt(this.value) || min;
            
            if (value < min) value = min;
            if (value > max) value = max;
            
            this.value = value;
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert, [class*="message"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Search form validation
    const searchForm = document.querySelector('form[action*="category.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (searchInput && searchInput.value.trim() === '') {
                e.preventDefault();
                // If search is empty, just go to category page without search param
                window.location.href = 'category.php';
            }
        });
    }
    
    // Add to cart animation
    const addToCartBtns = document.querySelectorAll('.btn-red:not(.no-animation)');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!this.classList.contains('no-animation')) {
                // Don't prevent default, just add animation class
                this.classList.add('clicked');
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            }
        });
    });
    
    // Lazy loading images
    const images = document.querySelectorAll('img[data-src]');
    if (images.length > 0 && 'IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    // Price format helper
    function formatPrice(price) {
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB',
            minimumFractionDigits: 2
        }).format(price);
    }
    
    // Update cart count in header (if using AJAX)
    function updateCartCount() {
        fetch('get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    if (data.count > 0) {
                        cartCount.textContent = data.count;
                        cartCount.style.display = 'inline';
                    } else {
                        cartCount.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
    }
    
    // Optional: Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+Shift+C to go to cart
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            window.location.href = 'cart.php';
        }
        // Ctrl+Shift+H to go to home
        if (e.ctrlKey && e.shiftKey && e.key === 'H') {
            e.preventDefault();
            window.location.href = 'index.php';
        }
    });
});

// Admin panel specific functions
function showConfirm(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const required = form.querySelectorAll('[required]');
    let isValid = true;
    
    required.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'red';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Preview image before upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Debounce function for search inputs
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

// Auto-save form data (for admin panel)
function autoSave(formId, saveCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, textarea, select');
    const saveData = {};
    
    inputs.forEach(input => {
        if (input.name) {
            input.addEventListener('change', debounce(() => {
                saveData[input.name] = input.value;
                if (typeof saveCallback === 'function') {
                    saveCallback(saveData);
                }
            }, 1000));
        }
    });
}

// Export functions for use in other scripts
window.ecommerce = {
    formatPrice,
    updateCartCount,
    showConfirm,
    validateForm,
    previewImage,
    debounce,
    autoSave
};