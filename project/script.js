// Global variables
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let currentUser = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    checkUserSession();
    initializeEventListeners();
});

// Cart Functions
function addToCart(productName, price) {
    const existingItem = cart.find(item => item.name === productName);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            name: productName,
            price: price,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
}

function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

// Search Functions
function toggleSearch() {
    const searchBar = document.getElementById('searchBar');
    searchBar.classList.toggle('active');
    if (searchBar.classList.contains('active')) {
        document.getElementById('searchInput').focus();
    }
}

function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value;
    if (searchTerm.trim()) {
        window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
    }
}

// Filter Functions
function filterByCategory(category) {
    window.location.href = `category.php?name=${encodeURIComponent(category)}`;
}

function sortProducts(value) {
    const productGrid = document.getElementById('productGrid');
    const products = Array.from(productGrid.children);
    
    products.sort((a, b) => {
        const priceA = parseFloat(a.dataset.price);
        const priceB = parseFloat(b.dataset.price);
        
        switch(value) {
            case 'price-low':
                return priceA - priceB;
            case 'price-high':
                return priceB - priceA;
            default:
                return 0;
        }
    });
    
    productGrid.innerHTML = '';
    products.forEach(product => productGrid.appendChild(product));
}

function showPerPage(value) {
    // Implementation for showing items per page
    console.log('Show per page:', value);
}

// Modal Functions
function quickView(productName) {
    const modal = document.getElementById('quickViewModal');
    const modalBody = modal.querySelector('.modal-body');
    
    // Fetch product details (demo data)
    modalBody.innerHTML = `
        <div class="quick-view-content">
            <div class="quick-view-image">
                <img src="https://via.placeholder.com/400x400" alt="${productName}">
            </div>
            <div class="quick-view-info">
                <h2>${productName}</h2>
                <div class="quick-view-price">฿299</div>
                <div class="quick-view-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span>(128 รีวิว)</span>
                </div>
                <p class="quick-view-description">
                    สินค้าคุณภาพดี วัสดุพรีเมี่ยม ทนทาน ใช้งานได้หลากหลาย เหมาะสำหรับทุกเพศทุกวัย
                </p>
                <div class="quick-view-actions">
                    <button class="btn-primary" onclick="addToCart('${productName}', 299)">เพิ่มลงตะกร้า</button>
                    <button class="btn-secondary">ซื้อทันที</button>
                </div>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('quickViewModal').style.display = 'none';
}

// User Session
function checkUserSession() {
    // Check if user is logged in (demo)
    const loggedIn = localStorage.getItem('userLoggedIn');
    if (loggedIn) {
        currentUser = JSON.parse(localStorage.getItem('currentUser'));
    }
}

// Notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Event Listeners
function initializeEventListeners() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let modal of modals) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    };
    
    // Search on enter key
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
    
    // Initialize product filters
    initializeFilters();
}

// Admin Panel Functions
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Update active nav item
    document.querySelectorAll('.admin-nav li').forEach(item => {
        item.classList.remove('active');
    });
    
    event.target.closest('li').classList.add('active');
    
    // Update page title
    const titles = {
        'dashboard': 'แดชบอร์ด',
        'products': 'จัดการสินค้า',
        'categories': 'จัดการประเภทสินค้า',
        'customers': 'จัดการลูกค้า',
        'orders': 'จัดการออเดอร์',
        'reports': 'รายงาน',
        'settings': 'ตั้งค่า'
    };
    document.getElementById('pageTitle').textContent = titles[sectionId];
}

function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('expanded');
}

// Product Management
function showAddProductModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มสินค้าใหม่';
    document.getElementById('productForm').reset();
    document.getElementById('productModal').style.display = 'block';
}

function editProduct(productId) {
    document.getElementById('modalTitle').textContent = 'แก้ไขสินค้า';
    // Fetch product data and populate form
    document.getElementById('productCode').value = productId;
    document.getElementById('productName').value = 'ตัวอย่างสินค้า';
    document.getElementById('productPrice').value = '299';
    document.getElementById('productStock').value = '50';
    document.getElementById('productModal').style.display = 'block';
}

function deleteProduct(productId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')) {
        // Delete product logic
        showNotification('ลบสินค้าเรียบร้อย', 'success');
    }
}

function viewProduct(productId) {
    // View product details
    quickView(productId);
}

function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

// Category Management
function showAddCategoryModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(categoryId) {
    // Fetch category data
    document.getElementById('categoryName').value = 'ประเภทสินค้า';
    document.getElementById('categoryIcon').value = 'fas fa-laptop';
    document.getElementById('categoryModal').style.display = 'block';
}

function deleteCategory(categoryId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบประเภทสินค้านี้?')) {
        showNotification('ลบประเภทสินค้าเรียบร้อย', 'success');
    }
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

// Customer Management
function editCustomer(customerId) {
    // Edit customer logic
    showNotification('แก้ไขข้อมูลลูกค้า', 'info');
}

function deleteCustomer(customerId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูลลูกค้านี้?')) {
        showNotification('ลบข้อมูลลูกค้าเรียบร้อย', 'success');
    }
}

function viewCustomer(customerId) {
    // View customer details
    alert('ดูรายละเอียดลูกค้า: ' + customerId);
}

// Order Management
function viewOrder(orderId) {
    document.getElementById('orderDetailModal').style.display = 'block';
}

function viewOrderDetails(orderId) {
    document.getElementById('orderDetailModal').style.display = 'block';
}

function updateOrderStatus(orderId) {
    // Update order status
    showNotification('อัปเดตสถานะออเดอร์เรียบร้อย', 'success');
}

function closeOrderModal() {
    document.getElementById('orderDetailModal').style.display = 'none';
}

function printOrder() {
    window.print();
}

// Image Preview
document.getElementById('productImages')?.addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    for (let file of e.target.files) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        }
        reader.readAsDataURL(file);
    }
});

// Form Submission
document.getElementById('productForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    showNotification('บันทึกข้อมูลเรียบร้อย', 'success');
    closeProductModal();
});

document.getElementById('categoryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    showNotification('บันทึกข้อมูลเรียบร้อย', 'success');
    closeCategoryModal();
});

// Initialize Filters
function initializeFilters() {
    const filterSelects = document.querySelectorAll('.filter-group select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Apply filters
            console.log('Filter changed:', this.value);
        });
    });
}

// Scroll to top button
window.onscroll = function() {
    const scrollBtn = document.getElementById('scrollTop');
    if (document.body.scrollTop > 500 || document.documentElement.scrollTop > 500) {
        scrollBtn?.classList.add('show');
    } else {
        scrollBtn?.classList.remove('show');
    }
};

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        color: #333;
        padding: 1rem 1.5rem;
        border-radius: 5px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 9999;
        transform: translateX(400px);
        transition: transform 0.3s;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.success {
        border-left: 4px solid #28a745;
    }
    
    .notification.success i {
        color: #28a745;
    }
    
    .notification.error {
        border-left: 4px solid #dc3545;
    }
    
    .notification.error i {
        color: #dc3545;
    }
    
    .notification.info {
        border-left: 4px solid #17a2b8;
    }
    
    .notification.info i {
        color: #17a2b8;
    }
    
    #scrollTop {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        transition: all 0.3s;
        z-index: 999;
    }
    
    #scrollTop.show {
        display: flex;
    }
    
    #scrollTop:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .quick-view-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    .quick-view-image img {
        width: 100%;
        border-radius: 10px;
    }
    
    .quick-view-info h2 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .quick-view-price {
        font-size: 1.8rem;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 1rem;
    }
    
    .quick-view-rating {
        margin-bottom: 1rem;
    }
    
    .quick-view-rating i {
        color: #ffd700;
    }
    
    .quick-view-description {
        color: #666;
        line-height: 1.8;
        margin-bottom: 2rem;
    }
    
    .quick-view-actions {
        display: flex;
        gap: 1rem;
    }
    
    @media (max-width: 768px) {
        .quick-view-content {
            grid-template-columns: 1fr;
        }
    }
`;

document.head.appendChild(style);

// Add scroll to top button
const scrollBtn = document.createElement('button');
scrollBtn.id = 'scrollTop';
scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
scrollBtn.onclick = scrollToTop;
document.body.appendChild(scrollBtn);