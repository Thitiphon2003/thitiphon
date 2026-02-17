<?php
require_once '../config/database.php';

class ShippingCalculator {
    private $db;
    private $shippingRates = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadShippingRates();
    }
    
    private function loadShippingRates() {
        $sql = "SELECT * FROM shipping_rates WHERE status = 'active' ORDER BY weight_from";
        $this->shippingRates = fetchAll($sql);
    }
    
    public function calculateShipping($items, $province, $district = null) {
        $totalWeight = 0;
        $totalPrice = 0;
        $itemCount = 0;
        
        foreach($items as $item) {
            $totalWeight += ($item['weight'] ?? 0.5) * $item['quantity'];
            $totalPrice += $item['price'] * $item['quantity'];
            $itemCount += $item['quantity'];
        }
        
        $shippingOptions = [];
        
        foreach($this->shippingRates as $rate) {
            // ตรวจสอบน้ำหนัก
            if($totalWeight < $rate['weight_from'] || $totalWeight > $rate['weight_to']) {
                continue;
            }
            
            // ตรวจสอบราคา
            if($rate['min_price'] > 0 && $totalPrice < $rate['min_price']) {
                continue;
            }
            
            // คำนวณค่าจัดส่งตามจังหวัด
            $baseCost = $rate['base_cost'];
            
            // ส่วนลดค่าจัดส่งตามยอด
            $shippingDiscount = 0;
            if($rate['free_shipping_min'] > 0 && $totalPrice >= $rate['free_shipping_min']) {
                $shippingDiscount = $baseCost;
            }
            
            $shippingOptions[] = [
                'id' => $rate['id'],
                'name' => $rate['name'],
                'description' => $rate['description'],
                'cost' => $baseCost,
                'discount' => $shippingDiscount,
                'final_cost' => max(0, $baseCost - $shippingDiscount),
                'estimated_days' => $rate['estimated_days'],
                'provider' => $rate['provider'],
                'tracking_available' => $rate['tracking_available']
            ];
        }
        
        // เรียงตามราคา
        usort($shippingOptions, function($a, $b) {
            return $a['final_cost'] - $b['final_cost'];
        });
        
        return $shippingOptions;
    }
    
    public function getProvinceShipping($province) {
        $sql = "SELECT * FROM province_shipping WHERE province = :province";
        return fetchOne($sql, [':province' => $province]);
    }
    
    public function trackShipment($trackingNumber, $courier) {
        // เชื่อมต่อ API ของแต่ละขนส่ง
        switch($courier) {
            case 'kerry':
                return $this->trackKerry($trackingNumber);
            case 'flash':
                return $this->trackFlash($trackingNumber);
            case 'jnt':
                return $this->trackJNT($trackingNumber);
            case 'thailandpost':
                return $this->trackThailandPost($trackingNumber);
            default:
                return null;
        }
    }
    
    private function trackKerry($trackingNumber) {
        // เรียก API ของ Kerry
        return [
            'status' => 'in_transit',
            'location' => 'ศูนย์คัดแยกบางนา',
            'timestamp' => date('Y-m-d H:i:s'),
            'description' => 'กำลังดำเนินการจัดส่ง'
        ];
    }
    
    private function trackFlash($trackingNumber) {
        return [
            'status' => 'picked_up',
            'location' => 'สาขาบางนา',
            'timestamp' => date('Y-m-d H:i:s'),
            'description' => 'รับพัสดุเรียบร้อย'
        ];
    }
    
    private function trackJNT($trackingNumber) {
        return [
            'status' => 'sorting',
            'location' => 'ศูนย์คัดแยกลาดกระบัง',
            'timestamp' => date('Y-m-d H:i:s'),
            'description' => 'กำลังคัดแยกพัสดุ'
        ];
    }
    
    private function trackThailandPost($trackingNumber) {
        // เรียก API ไปรษณีย์ไทย
        return [
            'status' => 'delivered',
            'location' => 'ไปรษณีย์คลองเตย',
            'timestamp' => date('Y-m-d H:i:s'),
            'description' => 'นำจ่ายสำเร็จ'
        ];
    }
}
?>