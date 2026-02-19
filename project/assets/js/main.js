// assets/js/main.js

// อัปเดตจำนวนสินค้าในตะกร้า
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.count || 0;
            });
        });
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
            showToast('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
        }
    });
}

// แสดง Toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    `;
    document.body.appendChild(container);
    return container;
}

// แสดงตัวอย่างรูปภาพ
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" class="img-fluid">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ตรวจสอบความแข็งแรงของรหัสผ่าน
function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const messages = ['อ่อนมาก', 'อ่อน', 'ปานกลาง', 'ดี', 'ดีมาก'];
    const colors = ['#ef4444', '#f59e0b', '#f59e0b', '#10b981', '#10b981'];
    
    return {
        score: strength,
        text: messages[strength] || 'อ่อนมาก',
        color: colors[strength] || '#ef4444'
    };
}

// ค้นหาสินค้า
function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value.trim()) {
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

// ยืนยันการลบ
function confirmDelete(message = 'คุณแน่ใจหรือไม่?') {
    return confirm(message);
}

// เริ่มต้นเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
    
    // เพิ่ม active class ให้กับลิงก์ปัจจุบัน
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});