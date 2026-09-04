<?php
session_start();
require_once '../../../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized. Please log in.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Access control: only reports owned by the logged-in user may be
// downloaded. Previously any report ID could be requested regardless
// of ownership (Broken Object Level Authorization).
$stmt = $conn->prepare("SELECT title, content FROM reports WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="report_' . $id . '.txt"');
    echo "=== " . $report['title'] . " ===\n\n";
    echo $report['content'];
} else {
    http_response_code(404);
    echo "Report not found.";
}
?>
