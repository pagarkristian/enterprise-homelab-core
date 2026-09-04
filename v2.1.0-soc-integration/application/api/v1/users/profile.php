<?php
session_start();
require_once '../../../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Access control: a user may only view their own profile. Prior to this
// check, any authenticated user could view any profile by changing the
// id parameter (Broken Object Level Authorization).
if ($id !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden: you may only view your own profile"]);
    exit();
}

$stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(["status" => "success", "data" => $user]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
}
?>
