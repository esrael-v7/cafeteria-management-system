<?php
// Script to create database and table if they don't exist
header('Content-Type: text/html; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";

// Connect to MySQL (without selecting database)
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS cafeteria";
if ($conn->query($sql) === TRUE) {
    echo "Database 'cafeteria' checked/created successfully.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("cafeteria");

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' checked/created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create orders table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    payment VARCHAR(50) DEFAULT 'Cash',
    status VARCHAR(20) DEFAULT 'Pending',
    total DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'orders' checked/created successfully.<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'order_items' checked/created successfully.<br>";
} else {
    echo "Error creating order_items table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(30) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) DEFAULT '',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'menu_items' checked/created successfully.<br>";
} else {
    echo "Error creating menu_items table: " . $conn->error . "<br>";
}

$conn->close();
echo "<br><strong>Database setup complete!</strong><br>";
echo "<a href='index.html'>Go to application</a>";
?>

