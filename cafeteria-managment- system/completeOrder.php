<?php
header('Content-Type: application/json');

$host = "localhost";
$db = "cafeteria";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data['orderId'] ?? null;
$status = $data['status'] ?? 'Ready';

if (!$orderId) {
    echo json_encode(["success" => false, "message" => "Order ID required"]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("si", $status, $orderId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Order status updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update order: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

