// Main JavaScript file for the e-commerce site

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== MOBILE MENU TOGGLE ==========
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    const menuOverlay = document.querySelector('.menu-overlay');
    const closeMenuBtn = document.querySelector('.close-menu');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            if (menuOverlay) menuOverlay.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
    }
    
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', function() {
            navMenu.classList.remove('active');
            if (menuOverlay) menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    if (menuOverlay) {
        menuOverlay.addEventListener('click', function() {
            navMenu.classList.remove('active');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close menu on window resize (if screen becomes larger)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && navMenu && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            if (menuOverlay) menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // ========== HAMBURGER MENU FOR DESKTOP ==========
    const desktopHamburger = document.getElementById('desktopHamburger');
    const desktopMenu = document.getElementById('desktopMenu');
    
    if (desktopHamburger && desktopMenu) {
        desktopHamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            desktopMenu.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!desktopHamburger.contains(e.target) && !desktopMenu.contains(e.target)) {
                desktopHamburger.classList.remove('active');
                desktopMenu.classList.remove('active');
            }
        });
        
        // Close menu when clicking on a link
        desktopMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                desktopHamburger.classList.remove('active');
                desktopMenu.classList.remove('active');
            });
        });
        
        // Close menu with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                desktopHamburger.classList.remove('active');
                desktopMenu.classList.remove('active');
            }
        });
    }
    
    // ========== MOBILE MENU FROM HAMBURGER ==========
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const closeMobileMenu = document.getElementById('closeMobileMenu');
    
    if (desktopHamburger && mobileMenu && mobileMenuOverlay) {
        desktopHamburger.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                mobileMenu.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                this.classList.remove('active');
                if (desktopMenu) desktopMenu.classList.remove('active');
            }
        });
    }
    
    if (closeMobileMenu && mobileMenu && mobileMenuOverlay) {
        closeMobileMenu.addEventListener('click', closeMobileMenuFunc);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenuFunc);
    }
    
    function closeMobileMenuFunc() {
        if (mobileMenu) mobileMenu.classList.remove('active');
        if (mobileMenuOverlay) mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMobileMenuFunc);
        });
    }
    
    // ========== QUANTITY INPUT VALIDATION ==========
    const quantityInputs = document.querySelectorAll('input[type="number"][name^="quantity"]');
    quantityInputs.forEach(input => {
        // Validate on input change
        input.addEventListener('change', function() {
            validateQuantity(this);
        });
        
        // Validate on keyup
        input.addEventListener('keyup', function() {
            validateQuantity(this);
        });
        
        // Add +/- buttons if needed
        const container = input.closest('.quantity-selector');
        if (container) {
            const minusBtn = container.querySelector('.quantity-minus');
            const plusBtn = container.querySelector('.quantity-plus');
            
            if (minusBtn) {
                minusBtn.addEventListener('click', function() {
                    let value = parseInt(input.value) || 1;
                    const min = parseInt(input.min) || 1;
                    if (value > min) {
                        input.value = value - 1;
                        triggerEvent(input, 'change');
                    }
                });
            }
            
            if (plusBtn) {
                plusBtn.addEventListener('click', function() {
                    let value = parseInt(input.value) || 1;
                    const max = parseInt(input.max) || 999;
                    if (value < max) {
                        input.value = value + 1;
                        triggerEvent(input, 'change');
                    }
                });
            }
        }
    });
    
    function validateQuantity(input) {
        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || 999;
        let value = parseInt(input.value) || min;
        
        if (value < min) value = min;
        if (value > max) value = max;
        
        input.value = value;
    }
    
    function triggerEvent(element, eventName) {
        const event = new Event(eventName, { bubbles: true });
        element.dispatchEvent(event);
    }
    
    // ========== AUTO-HIDE ALERTS ==========
    const alerts = document.querySelectorAll('.alert, [class*="message"]:not(.permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (alert.parentNode) alert.remove();
            }, 500);
        }, 5000);
    });
    
    // ========== SEARCH FORM VALIDATION ==========
    const searchForm = document.querySelector('form[action*="category.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (searchInput && searchInput.value.trim() === '') {
                e.preventDefault();
                window.location.href = 'category.php';
            }
        });
    }
    
    // ========== ADD TO CART ANIMATION ==========
    const addToCartBtns = document.querySelectorAll('.btn-red:not(.no-animation), .add-to-cart-btn');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!this.classList.contains('no-animation')) {
                this.classList.add('clicked');
                
                // Create flying animation
                const cartIcon = document.querySelector('.cart-icon i, .cart-icon');
                if (cartIcon) {
                    const btnRect = this.getBoundingClientRect();
                    const cartRect = cartIcon.getBoundingClientRect();
                    
                    const flyingItem = document.createElement('div');
                    flyingItem.className = 'flying-item';
                    flyingItem.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                    flyingItem.style.cssText = `
                        position: fixed;
                        left: ${btnRect.left}px;
                        top: ${btnRect.top}px;
                        width: 30px;
                        height: 30px;
                        background: var(--primary-red);
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 9999;
                        transition: all 1s ease;
                        pointer-events: none;
                    `;
                    
                    document.body.appendChild(flyingItem);
                    
                    setTimeout(() => {
                        flyingItem.style.left = cartRect.left + 'px';
                        flyingItem.style.top = cartRect.top + 'px';
                        flyingItem.style.opacity = '0';
                        flyingItem.style.transform = 'scale(0.5)';
                    }, 100);
                    
                    setTimeout(() => {
                        flyingItem.remove();
                    }, 1100);
                }
                
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            }
        });
    });
    
    // ========== LAZY LOADING IMAGES ==========
    const images = document.querySelectorAll('img[data-src]');
    if (images.length > 0 && 'IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                    
                    // Add fade-in effect
                    img.style.opacity = '0';
                    img.style.transition = 'opacity 0.5s';
                    setTimeout(() => img.style.opacity = '1', 50);
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }
    
    // ========== PRODUCT IMAGE FALLBACK ==========
    const productImages = document.querySelectorAll('.product-image, .product-thumbnail, [class*="product"] img');
    productImages.forEach(img => {
        img.addEventListener('error', function() {
            // Try to load from stores folder
            const currentSrc = this.src;
            const storesSrc = currentSrc.replace('/images/', '/images/stores/');
            
            if (!currentSrc.includes('stores')) {
                this.src = storesSrc;
            } else {
                // If still error, show placeholder
                this.onerror = null;
                this.src = 'https://via.placeholder.com/300x300?text=No+Image';
            }
        });
    });
    
    // ========== UPDATE CART COUNT ==========
    updateCartCount();
    
    // Update cart count every 30 seconds
    setInterval(updateCartCount, 30000);
    
    // ========== KEYBOARD SHORTCUTS ==========
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
        // Ctrl+Shift+A for admin (if logged in)
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            e.preventDefault();
            window.location.href = 'admin/';
        }
    });
    
    // ========== BACK TO TOP BUTTON ==========
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // ========== TOOLTIP INITIALIZATION ==========
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
    
    function showTooltip(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = e.target.dataset.tooltip;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--dark);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 10000;
            pointer-events: none;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        e.target._tooltip = tooltip;
    }
    
    function hideTooltip(e) {
        if (e.target._tooltip) {
            e.target._tooltip.remove();
            e.target._tooltip = null;
        }
    }
});

// ========== PRICE FORMAT HELPER ==========
function formatPrice(price) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 2
    }).format(price);
}

// ========== UPDATE CART COUNT FUNCTION ==========
function updateCartCount() {
    fetch('get-counts.php')
        .then(response => response.json())
        .then(data => {
            // Update cart count
            const cartCounts = document.querySelectorAll('.cart-count');
            cartCounts.forEach(el => {
                if (data.cart_count > 0) {
                    el.textContent = data.cart_count;
                    el.style.display = 'inline';
                } else {
                    el.style.display = 'none';
                }
            });
            
            // Update notification count
            const notifyBadges = document.querySelectorAll('.notification-badge');
            notifyBadges.forEach(el => {
                if (data.notify_count > 0) {
                    el.textContent = data.notify_count;
                    el.style.display = 'inline';
                } else {
                    el.style.display = 'none';
                }
            });
        })
        .catch(error => console.error('Error updating counts:', error));
}

// ========== ADMIN PANEL FUNCTIONS ==========

// Show confirm dialog
function showConfirm(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const required = form.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalid = null;
    
    required.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger)';
            field.style.borderWidth = '2px';
            isValid = false;
            
            if (!firstInvalid) firstInvalid = field;
            
            // Add shake animation
            field.classList.add('shake');
            setTimeout(() => field.classList.remove('shake'), 500);
        } else {
            field.style.borderColor = '';
        }
    });
    
    if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    return isValid;
}

// Preview image before upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        // Check file size (max 2MB)
        if (input.files[0].size > 2 * 1024 * 1024) {
            alert('ไฟล์รูปต้องมีขนาดไม่เกิน 2MB');
            input.value = '';
            return;
        }
        
        // Check file type
        const fileType = input.files[0].type;
        if (!fileType.match(/image\/(jpeg|jpg|png|gif|webp)/)) {
            alert('รองรับเฉพาะไฟล์ JPG, PNG, GIF, WEBP เท่านั้น');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Add fade-in animation
            preview.style.animation = 'fadeIn 0.5s';
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
            // Load saved data from localStorage
            const saved = localStorage.getItem(`${formId}_${input.name}`);
            if (saved) {
                input.value = saved;
            }
            
            // Save on change
            input.addEventListener('change', debounce(() => {
                saveData[input.name] = input.value;
                localStorage.setItem(`${formId}_${input.name}`, input.value);
                
                if (typeof saveCallback === 'function') {
                    saveCallback(saveData);
                }
                
                // Show saved indicator
                showSavedIndicator(input);
            }, 1000));
        }
    });
}

function showSavedIndicator(element) {
    const indicator = document.createElement('span');
    indicator.className = 'saved-indicator';
    indicator.innerHTML = '✓ บันทึกแล้ว';
    indicator.style.cssText = `
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--success);
        font-size: 12px;
        animation: fadeOut 2s forwards;
    `;
    
    const container = element.closest('.form-group');
    if (container) {
        container.style.position = 'relative';
        container.appendChild(indicator);
        
        setTimeout(() => indicator.remove(), 2000);
    }
}

// ========== EXPORT FUNCTIONS ==========
window.ecommerce = {
    formatPrice,
    updateCartCount,
    showConfirm,
    validateForm,
    previewImage,
    debounce,
    autoSave
};

// ========== ADD SHAKE ANIMATION ==========
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .shake {
        animation: shake 0.5s;
    }
    
    @keyframes fadeOut {
        0% { opacity: 1; }
        70% { opacity: 1; }
        100% { opacity: 0; }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: var(--primary-blue);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 99;
        box-shadow: var(--shadow-lg);
    }
    
    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }
    
    .back-to-top:hover {
        background: var(--primary-dark);
        transform: translateY(-5px);
    }
    
    .btn.clicked {
        transform: scale(0.95);
        opacity: 0.8;
    }
`;

document.head.appendChild(style);