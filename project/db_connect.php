<?php
// db_connect.php
$host = 'localhost';
$dbname = 'shop_db';
$username = 'root';
$password = 'r660109';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // ฟังก์ชัน query
    function query($sql, $params = []) {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    function fetchAll($sql, $params = []) {
        return query($sql, $params)->fetchAll();
    }
    
    function fetchOne($sql, $params = []) {
        return query($sql, $params)->fetch();
    }
    
    function insert($table, $data) {
        global $pdo;
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        foreach($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $pdo->lastInsertId();
    }
    
    function update($table, $data, $where, $whereParams = []) {
        global $pdo;
        $set = [];
        foreach(array_keys($data) as $key) {
            $set[] = "$key = :$key";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        $stmt = $pdo->prepare($sql);
        
        foreach($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        foreach($whereParams as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    }
    
    function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return query($sql, $params)->rowCount();
    }
    
    // ========== ฟังก์ชันจัดการรูปภาพ (พื้นฐาน) ==========
    
    /**
     * แสดงรูปภาพ
     */
    function showImage($filename, $folder = 'products') {
        if (!empty($filename) && file_exists("uploads/$folder/" . $filename)) {
            return "uploads/$folder/" . $filename;
        }
        return "https://via.placeholder.com/300x300?text=No+Image";
    }
    
    /**
     * ลบรูปภาพ
     */
    function deleteImage($filename, $folder = 'products') {
        if (empty($filename)) return true;
        $file_path = "uploads/$folder/" . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true;
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>