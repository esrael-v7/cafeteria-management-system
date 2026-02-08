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
$id = $data['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(["success" => false, "message" => "Menu item id required"]);
    $conn->close();
    exit;
}

$id = (int)$id;

$stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Delete failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

