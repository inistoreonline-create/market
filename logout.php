
<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update user offline status
    $query = "UPDATE users SET is_online = FALSE, last_seen = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
}

session_destroy();
header("Location: index.php");
exit();
?>
