<?php
class Category {
    private $conn;
    private $table_name = "Category";
    public function __construct($db) {
        $this->conn = $db;
    }
    public function getAllCategories() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>