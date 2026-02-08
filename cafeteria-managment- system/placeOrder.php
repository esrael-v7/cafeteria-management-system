<?php
header('Content-Type: application/json');

$host = "localhost";
$db = "cafeteria";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success"=>false, "message"=>"Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success"=>false, "message"=>"Invalid request data"]);
    exit;
}

$username = $data['username'] ?? null;
$items = $data['items'] ?? null;
$payment = $data['payment'] ?? 'unknown';

if (!$username || !$items) {
    echo json_encode(["success"=>false, "message"=>"Missing data"]);
    $conn->close();
    exit;
}

// Calculate total using DB prices (prevents client-side tampering)
$total = 0;
$normalizedItems = [];

$stmtLookup = $conn->prepare("SELECT id, name, price FROM menu_items WHERE id = ? AND active = 1");
if (!$stmtLookup) {
    echo json_encode(["success"=>false, "message"=>"Database error: " . $conn->error]);
    $conn->close();
    exit;
}

foreach ($items as $item) {
    $itemId = $item['id'] ?? null;
    $qty = $item['qty'] ?? null;

    if (!$itemId || !is_numeric($itemId) || !$qty || !is_numeric($qty)) {
        continue;
    }

    $itemId = (int)$itemId;
    $qty = (int)$qty;
    if ($qty <= 0) continue;

    $stmtLookup->bind_param("i", $itemId);
    $stmtLookup->execute();
    $res = $stmtLookup->get_result();
    $menuRow = $res ? $res->fetch_assoc() : null;
    if (!$menuRow) {
        continue;
    }

    $price = (float)$menuRow['price'];
    $name = $menuRow['name'];

    $total += $price * $qty;
    $normalizedItems[] = [
        "id" => $itemId,
        "name" => $name,
        "qty" => $qty,
        "price" => $price
    ];
}

$stmtLookup->close();

if (count($normalizedItems) === 0) {
    echo json_encode(["success"=>false, "message"=>"No valid items"]);
    $conn->close();
    exit;
}

// Add tax (15%)
$tax = $total * 0.15;
$finalTotal = $total + $tax;

// 1. Create order
$stmt = $conn->prepare("INSERT INTO orders (username, payment, status, total, created_at) VALUES (?, ?, 'Pending', ?, NOW())");
if (!$stmt) {
    echo json_encode(["success"=>false, "message"=>"Database error: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ssd", $username, $payment, $finalTotal);
if (!$stmt->execute()) {
    echo json_encode(["success"=>false, "message"=>"Failed to create order: " . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$orderId = $stmt->insert_id;
$stmt->close();

// 2. Add order items
foreach ($normalizedItems as $item) {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        continue; // Skip if error
    }
    $itemName = $item['name'] ?? 'Unknown';
    $quantity = $item['qty'] ?? 1;
    $itemPrice = $item['price'] ?? 0;
    $stmt->bind_param("isid", $orderId, $itemName, $quantity, $itemPrice);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(["success"=>true, "orderId"=>$orderId, "total"=>$finalTotal]);
$conn->close();
?>

