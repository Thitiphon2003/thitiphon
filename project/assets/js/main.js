// ===== assets/js/main.js =====
// JavaScript หลักที่ใช้ร่วมกันทุกหน้า

// อัปเดตจำนวนสินค้าในตะกร้า
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartCounts = document.querySelectorAll('.cart-count');
            cartCounts.forEach(el => {
                el.textContent = data.count || 0;
            });
        })
        .catch(error => console.error('Error:', error));
}

// เพิ่มสินค้าลงตะกร้า
function addToCart(productId, quantity = 1) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            updateCartCount();
            showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
        } else {
            showNotification(data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'error');
    });
}

// แสดงการแจ้งเตือน
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        max-width: 350px;
    `;
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-circle' : 'info-circle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// แสดง/ซ่อนรหัสผ่าน
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if(input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ตรวจสอบความแข็งแรงของรหัสผ่าน
function checkPasswordStrength(password) {
    let strength = 0;
    const patterns = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^a-zA-Z0-9]/.test(password)
    };
    
    Object.values(patterns).forEach(value => {
        if(value) strength += 20;
    });
    
    return {
        score: strength,
        patterns: patterns
    };
}

// แสดงตัวอย่างรูปภาพก่อนอัปโหลด
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '<i class="fas fa-image"></i>';
    }
}

// เลือกสินค้าทั้งหมดในตะกร้า
function toggleSelectAll(checkbox) {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    itemCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateCartSummary();
}

// อัปเดตสรุปตะกร้าสินค้า
function updateCartSummary() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    let total = 0;
    let count = 0;
    
    selectedItems.forEach(cb => {
        const row = cb.closest('.cart-item');
        const price = parseFloat(row.dataset.price);
        const quantity = parseInt(row.querySelector('.quantity-input').value);
        total += price * quantity;
        count++;
    });
    
    const totalElement = document.getElementById('cartTotal');
    if(totalElement) {
        totalElement.textContent = total.toLocaleString();
    }
    
    const countElement = document.getElementById('selectedCount');
    if(countElement) {
        countElement.textContent = count;
    }
}

// เปลี่ยนจำนวนสินค้าในตะกร้า
function updateQuantity(input, delta) {
    const newValue = parseInt(input.value) + delta;
    if(newValue >= 1) {
        input.value = newValue;
        updateCartSummary();
    }
}

// ค้นหาสินค้า
function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    if(searchInput && searchInput.value.trim()) {
        window.location.href = `category.php?search=${encodeURIComponent(searchInput.value.trim())}`;
    }
}

// เรียงลำดับสินค้า
function sortProducts(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    window.location.href = url.toString();
}

// แสดงจำนวนสินค้าต่อหน้า
function showPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}

// เลื่อนหน้าไปด้านบน
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// แสดง/ซ่อนปุ่มเลื่อนขึ้น
window.addEventListener('scroll', function() {
    const scrollBtn = document.getElementById('scrollTop');
    if(scrollBtn) {
        if(window.scrollY > 500) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    }
});

// เริ่มต้นเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    // อัปเดตจำนวนสินค้าในตะกร้า
    updateCartCount();
    
    // เพิ่ม Event Listeners สำหรับปุ่มต่างๆ
    const searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                searchProducts();
            }
        });
    }
    
    // เพิ่ม animation ให้กับ dropdown
    const dropdowns = document.querySelectorAll('.user-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('mouseenter', function() {
            const content = this.querySelector('.dropdown-content');
            if(content) {
                content.style.opacity = '1';
                content.style.visibility = 'visible';
                content.style.transform = 'translateY(0)';
            }
        });
        
        dropdown.addEventListener('mouseleave', function() {
            const content = this.querySelector('.dropdown-content');
            if(content) {
                content.style.opacity = '0';
                content.style.visibility = 'hidden';
                content.style.transform = 'translateY(10px)';
            }
        });
    });
    
    // เพิ่ม active class ให้กับลิงก์ปัจจุบัน
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-links a');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if(href === currentPage) {
            link.classList.add('active');
        }
    });
});

// เพิ่ม CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #scrollTop {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 45px;
        height: 45px;
        background: #0f172a;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        z-index: 99;
        transition: all 0.3s;
    }
    
    #scrollTop:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    
    #scrollTop.show {
        display: flex;
    }
`;

document.head.appendChild(style);

// เพิ่มปุ่มเลื่อนขึ้น
const scrollBtn = document.createElement('button');
scrollBtn.id = 'scrollTop';
scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
scrollBtn.onclick = scrollToTop;
document.body.appendChild(scrollBtn);