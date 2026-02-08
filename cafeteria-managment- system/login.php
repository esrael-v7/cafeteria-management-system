<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$db = "cafeteria";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success"=>false,"message"=>"Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? null;
$password = $data['password'] ?? null;

if (!$username || !$password) {
    echo json_encode(["success"=>false,"message"=>"Fill all fields"]);
    exit;
}
// Admin check
if($username === "admin" && $password === "admin123"){
    $_SESSION['username'] = "admin";
    $_SESSION['role'] = "admin";
    echo json_encode(["success"=>true,"user"=>["role"=>"admin","username"=>"admin"]]);
    exit;
}

// Customer check
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if(!$user){
    echo json_encode(["success"=>false,"message"=>"User not found"]);
    exit;
}

// Use password_verify to check hashed password
if(!password_verify($password, $user['password'])){
    echo json_encode(["success"=>false,"message"=>"Wrong password"]);
    exit;
}

$_SESSION['username'] = $user['username'];
$_SESSION['role'] = "customer";

echo json_encode(["success"=>true,"user"=>["role"=>"customer","username"=>$user['username']]]);
$stmt->close();
$conn->close();
?>
