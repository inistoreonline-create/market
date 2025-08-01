
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = trim($_POST['message'] ?? '');
$receiver_id = $_POST['receiver_id'] ?? '';

if (empty($message) || empty($receiver_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
$stmt = $db->prepare($query);

if ($stmt->execute([$_SESSION['user_id'], $receiver_id, $message])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
?>
