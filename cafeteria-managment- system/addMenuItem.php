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
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    $conn->close();
    exit;
}

$category = trim($data['category'] ?? '');
$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$price = $data['price'] ?? null;
$imagePath = trim($data['image_path'] ?? '');
$active = isset($data['active']) ? (int)$data['active'] : 1;

if ($category === '' || $name === '' || $price === null) {
    echo json_encode(["success" => false, "message" => "Missing required fields (category, name, price)"]);
    $conn->close();
    exit;
}

if (!is_numeric($price)) {
    echo json_encode(["success" => false, "message" => "Price must be numeric"]);
    $conn->close();
    exit;
}

$price = (float)$price;

$stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_path, active) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("sssdsi", $category, $name, $description, $price, $imagePath, $active);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "id" => $stmt->insert_id]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

