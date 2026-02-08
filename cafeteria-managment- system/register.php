<?php
// Completely suppress all errors and warnings
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Set JSON header first
header('Content-Type: application/json');

// Start output buffering to catch any unwanted output
ob_start();

$host = "localhost";
$db = "cafeteria";
$user = "root";
$pass = "";

// Connect to database
$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Clear any output buffer
ob_end_clean();

// Get JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    $conn->close();
    exit;
}

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (!$username || !$password) {
    echo json_encode(["success" => false, "message" => "Fill all fields"]);
    $conn->close();
    exit;
}

// Check if username exists
$stmt = @$conn->prepare("SELECT id FROM users WHERE username=?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error. Please run create_db.php first"]);
    $conn->close();
    exit;
}

$stmt->bind_param("s", $username);
@$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already taken"]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = @$conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error. Please run create_db.php first"]);
    $conn->close();
    exit;
}

$stmt->bind_param("ss", $username, $hashedPassword);

if (@$stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Registration successful!"]);
} else {
    echo json_encode(["success" => false, "message" => "Registration failed"]);
}

$stmt->close();
$conn->close();
?>
