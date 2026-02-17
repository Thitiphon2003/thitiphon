<?php
require_once '../config/database.php';

class CouponSystem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function validateCoupon($code, $userId = null, $cartTotal = 0) {
        try {
            // ตรวจสอบคูปอง
            $sql = "SELECT * FROM coupons WHERE code = :code AND status = 'active' 
                    AND start_date <= NOW() AND end_date >= NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':code' => $code]);
            $coupon = $stmt->fetch();
            
            if(!$coupon) {
                return ['valid' => false, 'message' => 'โค้ดส่วนลดไม่ถูกต้อง'];
            }
            
            // ตรวจสอบจำนวนการใช้
            if($coupon['usage_limit'] > 0) {
                $sql = "SELECT COUNT(*) as used FROM coupon_usage WHERE coupon_id = :coupon_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':coupon_id' => $coupon['id']]);
                $used = $stmt->fetch()['used'];
                
                if($used >= $coupon['usage_limit']) {
                    return ['valid' => false, 'message' => 'โค้ดส่วนลดหมดอายุการใช้งาน'];
                }
            }
            
            // ตรวจสอบการใช้ต่อ user
            if($userId && $coupon['per_user_limit'] > 0) {
                $sql = "SELECT COUNT(*) as used FROM coupon_usage 
                        WHERE coupon_id = :coupon_id AND user_id = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':coupon_id' => $coupon['id'],
                    ':user_id' => $userId
                ]);
                $userUsed = $stmt->fetch()['used'];
                
                if($userUsed >= $coupon['per_user_limit']) {
                    return ['valid' => false, 'message' => 'คุณใช้โค้ดส่วนลดนี้ครบจำนวนแล้ว'];
                }
            }
            
            // ตรวจสอบยอดขั้นต่ำ
            if($coupon['min_order'] > 0 && $cartTotal < $coupon['min_order']) {
                return [
                    'valid' => false, 
                    'message' => 'ยอดสั่งซื้อขั้นต่ำ ' . number_format($coupon['min_order']) . ' บาท'
                ];
            }
            
            // คำนวณส่วนลด
            $discount = 0;
            if($coupon['discount_type'] == 'percentage') {
                $discount = min($cartTotal * $coupon['discount_value'] / 100, $coupon['max_discount']);
            } else {
                $discount = min($coupon['discount_value'], $cartTotal);
            }
            
            return [
                'valid' => true,
                'coupon' => $coupon,
                'discount' => $discount,
                'message' => 'ใช้โค้ดส่วนลดสำเร็จ'
            ];
            
        } catch(PDOException $e) {
            return ['valid' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    public function applyCoupon($code, $userId, $orderId = null) {
        try {
            $this->db->beginTransaction();
            
            // ดึงข้อมูลคูปอง
            $sql = "SELECT * FROM coupons WHERE code = :code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':code' => $code]);
            $coupon = $stmt->fetch();
            
            // บันทึกการใช้
            $sql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, used_at) 
                    VALUES (:coupon_id, :user_id, :order_id, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':coupon_id' => $coupon['id'],
                ':user_id' => $userId,
                ':order_id' => $orderId
            ]);
            
            $this->db->commit();
            return true;
            
        } catch(Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getAvailableCoupons($userId = null) {
        $sql = "SELECT * FROM coupons WHERE status = 'active' 
                AND start_date <= NOW() AND end_date >= NOW() 
                ORDER BY created_at DESC LIMIT 10";
        return fetchAll($sql);
    }
}
?>