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
        .catch(error => console.error('Error updating cart count:', error));
}

// เพิ่มสินค้าลงตะกร้า
function addToCart(productId, event) {
    // ป้องกัน event bubbling
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (!productId || productId === 0) {
        showNotification('กรุณาเลือกสินค้า', 'warning');
        return;
    }
    
    // หาปุ่มที่ถูกคลิก
    const btn = event ? event.currentTarget : null;
    const originalText = btn ? btn.innerHTML : '';
    
    // แสดง loading บนปุ่ม
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังเพิ่ม...';
    }
    
    // ส่ง request
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=1'
    })
    .then(response => {
        // ตรวจสอบว่า response เป็น JSON หรือไม่
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON');
        }
        return response.json();
    })
    .then(data => {
        // คืนค่าปุ่มกลับเป็นปกติ
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        
        if (data.success) {
            // อัปเดตจำนวนสินค้าในตะกร้า
            updateCartCount();
            
            // แสดง notification สำเร็จ
            showNotification(data.message || 'เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
            
            // สั่นการ์ดสินค้า (ถ้ามี)
            const card = btn ? btn.closest('.card') : null;
            if (card) {
                card.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    card.style.animation = '';
                }, 500);
            }
        } else {
            // แสดง notification ข้อผิดพลาด
            showNotification(data.message || 'เกิดข้อผิดพลาด', 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        
        // คืนค่าปุ่มกลับเป็นปกติ
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        
        // แสดง notification ข้อผิดพลาด
        showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    });
}

// แสดง notification
function showNotification(message, type = 'success') {
    // สร้าง container ถ้ายังไม่มี
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(container);
    }
    
    // สร้าง notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.setAttribute('role', 'alert');
    notification.style.cssText = `
        min-width: 300px;
        margin-bottom: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease;
    `;
    
    // เลือก icon ตาม type
    let icon = 'fa-check-circle';
    if (type === 'danger') icon = 'fa-exclamation-circle';
    if (type === 'warning') icon = 'fa-exclamation-triangle';
    if (type === 'info') icon = 'fa-info-circle';
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${icon} me-2"></i>
            <div>${message}</div>
            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // ลบ notification อัตโนมัติหลัง 3 วินาที
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, 3000);
}

// เพิ่ม CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
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
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .spinner-border {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 0.2em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }
    
    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);



// เริ่มต้นเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // เพิ่ม active class ให้กับลิงก์ปัจจุบัน
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.php')) {
            link.classList.add('active');
        }
    });


});