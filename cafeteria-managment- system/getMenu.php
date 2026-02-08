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

$onlyActive = isset($_GET['active']) ? (int)$_GET['active'] : 1;

if ($onlyActive === 1) {
    $stmt = $conn->prepare("SELECT id, category, name, description, price, image_path FROM menu_items WHERE active = 1 ORDER BY category, id");
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, category, name, description, price, image_path, active FROM menu_items ORDER BY category, id");
}

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode(["success" => true, "items" => $items]);
$conn->close();
?>

