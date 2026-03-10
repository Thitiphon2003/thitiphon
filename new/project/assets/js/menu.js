// Mobile & Desktop Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Desktop Menu
    const desktopHamburger = document.getElementById('desktopHamburger');
    const desktopMenu = document.getElementById('desktopMenu');
    
    // Mobile Menu
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeMobileMenu = document.getElementById('closeMobileMenu');
    
    // Desktop Menu Toggle
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
    }
    
    // Mobile Menu Toggle (เปิดจาก desktop hamburger บนมือถือ)
    if (desktopHamburger) {
        desktopHamburger.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                mobileMenu.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                desktopHamburger.classList.remove('active');
                desktopMenu.classList.remove('active');
            }
        });
    }
    
    // Close mobile menu
    function closeMobileMenuFunc() {
        mobileMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (closeMobileMenu) {
        closeMobileMenu.addEventListener('click', closeMobileMenuFunc);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenuFunc);
    }
    
    // Close mobile menu when clicking on a link
    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMobileMenuFunc);
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileMenuFunc();
        }
    });
    
    // Close menu with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (desktopMenu && desktopMenu.classList.contains('active')) {
                desktopHamburger.classList.remove('active');
                desktopMenu.classList.remove('active');
            }
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                closeMobileMenuFunc();
            }
        }
    });
    
    // Update cart count and notification count periodically (optional)
    function updateCounts() {
        fetch('get-counts.php')
            .then(response => response.json())
            .then(data => {
                // Update cart badges
                const cartBadges = document.querySelectorAll('.cart-badge');
                cartBadges.forEach(badge => {
                    if (data.cart_count > 0) {
                        badge.textContent = data.cart_count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                });
                
                // Update notification badges
                const notifyBadges = document.querySelectorAll('.notification-badge');
                notifyBadges.forEach(badge => {
                    if (data.notify_count > 0) {
                        badge.textContent = data.notify_count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            })
            .catch(error => console.error('Error updating counts:', error));
    }
    
    // Update every 30 seconds
    // setInterval(updateCounts, 30000);
});