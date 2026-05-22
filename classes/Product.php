<?php
class Product {
    private $conn;
    private $table_name = "Product";
    public function __construct($db) {
        $this->conn = $db;
    }
    public function getAllProducts() {
        $query = "SELECT * FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    public function getProductsByIDs($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT * FROM " . $this->table_name . " WHERE ProductID IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        return $stmt;
    }
    public function getProductsByCategory($categoryId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE CategoryID = :cat_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cat_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    public function getProductsByCategoryWithFilter($categoryId, $sort = '', $start = 0, $limit = 8) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE CategoryID = :cat_id";

        if ($sort == 'price_asc') {
            $query .= " ORDER BY Price ASC";
        }
        $query .= " LIMIT :start, :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cat_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
    public function countProductsByCategory($categoryId) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE CategoryID = :cat_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cat_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    public function searchProducts($keyword, $sort = '', $start = 0, $limit = 8) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ProductName LIKE :keyword";
        if ($sort == 'price_asc') {
            $query .= " ORDER BY Price ASC";
        }
        $query .= " LIMIT :start, :limit";
        $stmt = $this->conn->prepare($query);
        $keyword_param = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword_param, PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
    public function countSearchProducts($keyword) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE ProductName LIKE :keyword";
        $stmt = $this->conn->prepare($query);
        $keyword_param = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword_param, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    public function getProductById($id) {
        $query = "SELECT p.*, c.CategoryName 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN Category c ON p.CategoryID = c.CategoryID 
                  WHERE p.ProductID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>