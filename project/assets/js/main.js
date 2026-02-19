// assets/js/main.js

// อัปเดตจำนวนสินค้าในตะกร้า
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.count || 0;
            });
        })
        .catch(error => console.error('Error:', error));
}

// เพิ่มสินค้าลงตะกร้า
function addToCart(productId, quantity = 1) {
    if (!productId || productId === 0) {
        showToast('กรุณาเลือกสินค้า', 'warning');
        return;
    }
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount();
            showToast('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
            
            // สั่นการ์ดสินค้า
            const btn = event.target;
            const card = btn.closest('.card');
            if (card) {
                card.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    card.style.animation = '';
                }, 500);
            }
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'danger');
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

// เพิ่ม CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .toast {
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// เริ่มต้นเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
    
    // เพิ่ม active class ให้กับลิงก์ปัจจุบัน
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.php')) {
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