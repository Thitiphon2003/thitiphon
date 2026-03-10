    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Shop<span>Hub</span></h3>
                <p>ค้นพบสินค้าที่คุณชื่นชอบชิ้นต่อไปได้ที่ ShopHub สินค้าคุณภาพเยี่ยม ผู้ขายที่น่าเชื่อถือ และบริการที่เป็นเลิศ รับประกันได้.</p>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <a href="#" style="color: var(--white); font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color: var(--white); font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: var(--white); font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: var(--white); font-size: 1.5rem;"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>ร้านค้า</h4>
                <ul>
                    <li><a href="category.php">สินค้าทั้งหมด</a></li>
                    <li><a href="category.php">มาใหม่</a></li>
                    <li><a href="category.php">ขายดี</a></li>
                    <li><a href="category.php">โปรโมชั่น</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>ฝ่ายบริการลูกค้า</h4>
                <ul>
                    <li><a href="contact.php">ติดต่อเรา</a></li>
                    <li><a href="shipping.php">การจัดส่ง</a></li>
                    <li><a href="returns.php">การคืนสินค้า</a></li>
                    <li><a href="faq.php">คำถามที่พบบ่อย</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>บริษัท</h4>
                <ul>
                    <li><a href="about.php">เกี่ยวกับเรา</a></li>
                    <li><a href="blog.php">บล็อก</a></li>
                    <li><a href="careers.php">ร่วมงานกับเรา</a></li>
                    <li><a href="press.php">ข่าวสาร</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div>© 2025 ShopHub. All rights reserved.</div>
            <div class="footer-bottom-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="cookies.php">Cookie Policy</a>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/menu.js"></script>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>