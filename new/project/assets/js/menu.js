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
    
    // Mobile Menu Toggle (สามารถเปิดจากปุ่มอะไรก็ได้ที่ต้องการ)
    // ถ้าต้องการให้ desktop hamburger เปิด mobile menu บนมือถือ
    if (window.innerWidth <= 768) {
        if (desktopHamburger) {
            desktopHamburger.addEventListener('click', function(e) {
                e.preventDefault();
                mobileMenu.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }
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
            // ถ้าหน้าจอใหญ่กว่า 768px ให้ปิด mobile menu
            closeMobileMenuFunc();
        } else {
            // ถ้าหน้าจอเล็กกว่า 768px ให้ desktop hamburger เปิด mobile menu
            if (desktopHamburger) {
                desktopHamburger.removeEventListener('click', function(){});
                desktopHamburger.addEventListener('click', function(e) {
                    e.preventDefault();
                    mobileMenu.classList.add('active');
                    mobileMenuOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }
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
    
    // Prevent body scrolling when mobile menu is open
    if (mobileMenu) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (mobileMenu.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            });
        });
        
        observer.observe(mobileMenu, { attributes: true });
    }
});