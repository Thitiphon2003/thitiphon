// script.js
// ฟังก์ชันหลักเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้น Tooltips
    initializeTooltips();
    
    // เริ่มต้น Popovers
    initializePopovers();
    
    // เริ่มต้น DataTables ถ้ามี
    initializeDataTables();
    
    // เพิ่ม animation ให้กับ elements
    addFadeInAnimation();
    
    // เริ่มต้นการทำงานของ search
    initializeSearch();
});

// เริ่มต้น Bootstrap Tooltips
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// เริ่มต้น Bootstrap Popovers
function initializePopovers() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// เริ่มต้น DataTables
function initializeDataTables() {
    // ตรวจสอบว่ามี DataTables หรือไม่
    if (typeof $.fn !== 'undefined' && $.fn.DataTable) {
        // Products Table
        if ($('#productsTable').length) {
            $('#productsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[1, 'asc']]
            });
        }
        
        // Categories Table
        if ($('#categoriesTable').length) {
            $('#categoriesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
                },
                pageLength: 10,
                responsive: true
            });
        }
        
        // Customers Table
        if ($('#customersTable').length) {
            $('#customersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
                },
                pageLength: 10,
                responsive: true
            });
        }
        
        // Orders Table
        if ($('#ordersTable').length) {
            $('#ordersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[1, 'desc']]
            });
        }
    }
}

// เพิ่ม animation fade-in ให้กับ elements
function addFadeInAnimation() {
    const elements = document.querySelectorAll('.card, .product-card, .alert');
    elements.forEach((element, index) => {
        element.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s forwards`;
        element.style.opacity = '0';
    });
}

// เริ่มต้นการทำงานของ search
function initializeSearch() {
    const searchInput = document.getElementById('searchProduct');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('productsTable');
            if (table) {
                const rows = table.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    for (let j = 0; j < cells.length; j++) {
                        const cell = cells[j];
                        if (cell && cell.textContent.toLowerCase().indexOf(searchTerm) > -1) {
                            found = true;
                            break;
                        }
                    }
                    row.style.display = found ? '' : 'none';
                }
            }
        });
    }
}

// ฟังก์ชันสำหรับแก้ไขสินค้า (admin)
function editProduct(productId) {
    // ในระบบจริงควรโหลดข้อมูลสินค้ามาแสดงใน modal
    alert('กำลังแก้ไขสินค้า ID: ' + productId);
    // ตัวอย่าง: เปิด modal และโหลดข้อมูล
    $('#editProductModal').modal('show');
}

// ฟังก์ชันสำหรับลบสินค้า (admin)
function deleteProduct(productId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')) {
        // ส่งคำขอลบ
        fetch('admin/delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('ลบสินค้าเรียบร้อย');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการลบสินค้า');
        });
    }
}

// ฟังก์ชันสำหรับเพิ่มสินค้าลงตะกร้า
function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            product_id: productId,
            quantity: 1 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // อัปเดตจำนวนสินค้าในตะกร้า
            updateCartCount(data.cart_count);
            // แสดงข้อความ success
            showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
        } else {
            showNotification('เกิดข้อผิดพลาด: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการเพิ่มสินค้า', 'danger');
    });
}

// อัปเดตจำนวนสินค้าในตะกร้า
function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = count;
        if (count > 0) {
            cartBadge.style.display = 'inline';
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// แสดง notification
function showNotification(message, type = 'info') {
    // สร้าง notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // ลบ notification อัตโนมัติหลังจาก 3 วินาที
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// ฟังก์ชันสำหรับตรวจสอบความถูกต้องของฟอร์ม
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// ฟังก์ชันสำหรับยืนยันคำสั่งซื้อ
function confirmOrder() {
    if (!validateForm('checkoutForm')) {
        showNotification('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return false;
    }
    
    // แสดง loading
    showLoading();
    
    // ส่งคำสั่งซื้อ
    fetch('process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            payment_method: document.querySelector('input[name="payment"]:checked')?.value,
            shipping_address: document.querySelector('#shipping_address')?.value
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('คำสั่งซื้อสำเร็จ! ขอบคุณที่ใช้บริการ', 'success');
            setTimeout(() => {
                window.location.href = 'order_success.php?order_id=' + data.order_id;
            }, 2000);
        } else {
            showNotification('เกิดข้อผิดพลาด: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดำเนินการ', 'danger');
    });
}

// แสดง loading
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.id = 'loadingOverlay';
    loading.innerHTML = `
        <div class="spinner"></div>
        <p class="mt-2">กำลังดำเนินการ...</p>
    `;
    document.body.appendChild(loading);
}

// ซ่อน loading
function hideLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.remove();
    }
}

// เพิ่ม CSS สำหรับ loading
const style = document.createElement('style');
style.textContent = `
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 99999;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
    }
    
    .is-invalid:focus {
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
`;

document.head.appendChild(style);

// จัดการกับ responsive menu
function handleResponsiveMenu() {
    const width = window.innerWidth;
    const navbar = document.querySelector('.navbar-collapse');
    
    if (width < 768 && navbar && navbar.classList.contains('show')) {
        // ทำอะไรบางอย่างเมื่อเมนูเปิดบนมือถือ
    }
}

window.addEventListener('resize', handleResponsiveMenu);