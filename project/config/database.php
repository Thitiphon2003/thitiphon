<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'shop_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("SET NAMES utf8mb4");
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}

// ฟังก์ชัน Helper สำหรับ query
function query($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
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
    global $db;
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($sql);
    
    foreach($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    return $db->lastInsertId();
}

function update($table, $data, $where, $whereParams = []) {
    global $db;
    $set = [];
    foreach(array_keys($data) as $key) {
        $set[] = "$key = :$key";
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
    $stmt = $db->prepare($sql);
    
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

// เริ่มต้น connection
$database = new Database();
$db = $database->getConnection();
?>