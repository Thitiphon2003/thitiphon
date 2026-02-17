<?php
require_once '../config/database.php';

class AddressManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getUserAddresses($userId) {
        $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
        return fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function getAddress($addressId, $userId = null) {
        $sql = "SELECT * FROM user_addresses WHERE id = :id";
        if($userId) {
            $sql .= " AND user_id = :user_id";
        }
        
        $params = [':id' => $addressId];
        if($userId) {
            $params[':user_id'] = $userId;
        }
        
        return fetchOne($sql, $params);
    }
    
    public function addAddress($userId, $data) {
        try {
            $this->db->beginTransaction();
            
            // ถ้าตั้งเป็นค่าเริ่มต้น ให้ยกเลิกค่าเริ่มต้นอื่น
            if($data['is_default']) {
                $sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id";
                query($sql, [':user_id' => $userId]);
            }
            
            // เพิ่มที่อยู่ใหม่
            $sql = "INSERT INTO user_addresses 
                    (user_id, address_name, recipient, phone, address, district, city, province, postcode, is_default, created_at) 
                    VALUES 
                    (:user_id, :address_name, :recipient, :phone, :address, :district, :city, :province, :postcode, :is_default, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':address_name' => $data['address_name'],
                ':recipient' => $data['recipient'],
                ':phone' => $data['phone'],
                ':address' => $data['address'],
                ':district' => $data['district'],
                ':city' => $data['city'],
                ':province' => $data['province'],
                ':postcode' => $data['postcode'],
                ':is_default' => $data['is_default'] ? 1 : 0
            ]);
            
            $addressId = $this->db->lastInsertId();
            
            $this->db->commit();
            return ['success' => true, 'address_id' => $addressId];
            
        } catch(Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateAddress($addressId, $userId, $data) {
        try {
            $this->db->beginTransaction();
            
            // ถ้าตั้งเป็นค่าเริ่มต้น ให้ยกเลิกค่าเริ่มต้นอื่น
            if($data['is_default']) {
                $sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id";
                query($sql, [':user_id' => $userId]);
            }
            
            // อัปเดตที่อยู่
            $sql = "UPDATE user_addresses SET 
                    address_name = :address_name,
                    recipient = :recipient,
                    phone = :phone,
                    address = :address,
                    district = :district,
                    city = :city,
                    province = :province,
                    postcode = :postcode,
                    is_default = :is_default
                    WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':address_name' => $data['address_name'],
                ':recipient' => $data['recipient'],
                ':phone' => $data['phone'],
                ':address' => $data['address'],
                ':district' => $data['district'],
                ':city' => $data['city'],
                ':province' => $data['province'],
                ':postcode' => $data['postcode'],
                ':is_default' => $data['is_default'] ? 1 : 0,
                ':id' => $addressId,
                ':user_id' => $userId
            ]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch(Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteAddress($addressId, $userId) {
        // ตรวจสอบว่าไม่ใช่ที่อยู่ค่าเริ่มต้น
        $address = $this->getAddress($addressId, $userId);
        if($address && $address['is_default']) {
            return ['success' => false, 'message' => 'ไม่สามารถลบที่อยู่ค่าเริ่มต้นได้'];
        }
        
        $sql = "DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id";
        $result = query($sql, [':id' => $addressId, ':user_id' => $userId]);
        
        return ['success' => $result > 0];
    }
    
    public function setDefaultAddress($addressId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // ยกเลิกค่าเริ่มต้นทั้งหมด
            $sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id";
            query($sql, [':user_id' => $userId]);
            
            // ตั้งค่าเริ่มต้นใหม่
            $sql = "UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id";
            query($sql, [':id' => $addressId, ':user_id' => $userId]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch(Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getDefaultAddress($userId) {
        $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id AND is_default = 1";
        $address = fetchOne($sql, [':user_id' => $userId]);
        
        if(!$address) {
            // ถ้าไม่มีค่าเริ่มต้น ให้เลือกอันล่าสุด
            $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
            $address = fetchOne($sql, [':user_id' => $userId]);
        }
        
        return $address;
    }
    
    public function getProvinces() {
        $sql = "SELECT * FROM thai_provinces ORDER BY name_th";
        return fetchAll($sql);
    }
    
    public function getDistricts($provinceId) {
        $sql = "SELECT * FROM thai_districts WHERE province_id = :province_id ORDER BY name_th";
        return fetchAll($sql, [':province_id' => $provinceId]);
    }
    
    public function getSubdistricts($districtId) {
        $sql = "SELECT * FROM thai_subdistricts WHERE district_id = :district_id ORDER BY name_th";
        return fetchAll($sql, [':district_id' => $districtId]);
    }
}
?>