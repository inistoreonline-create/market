
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

if ($action == 'update_online') {
    $query = "UPDATE users SET is_online = TRUE, last_seen = CURRENT_TIMESTAMP WHERE id = ?";
} else if ($action == 'update_offline') {
    $query = "UPDATE users SET is_online = FALSE, last_seen = CURRENT_TIMESTAMP WHERE id = ?";
} else {
    exit();
}

$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
?>
