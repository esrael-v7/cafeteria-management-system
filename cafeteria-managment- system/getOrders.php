<?php
header('Content-Type: application/json');

$host = "localhost";
$db = "cafeteria";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Check if user parameter is set (for order history)
$user = $_GET['user'] ?? null;

if ($user) {
    // Get orders for specific user
    $stmt = $conn->prepare("SELECT * FROM orders WHERE username = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Get all orders
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
}

$data = [];
while ($row = $result->fetch_assoc()) {
    // Get order items for each order
    $orderId = $row['id'];
    $itemsResult = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId");
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $row['items'] = $items;
    $data[] = $row;
}

echo json_encode(["orders" => $data]);
$conn->close();
?>

