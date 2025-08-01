
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$seller_id = $_GET['seller'] ?? null;

if (!$seller_id) {
    exit();
}

// Get messages between user and seller
$query = "SELECT m.*, u.username as sender_name FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
          ORDER BY m.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $seller_id, $seller_id, $_SESSION['user_id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($messages)): ?>
<div class="text-center text-muted">
    <i class="fas fa-comments fa-lg mb-2"></i>
    <p class="small">No messages yet. Start the conversation!</p>
</div>
<?php else: ?>
    <?php foreach ($messages as $message): ?>
    <div class="mb-2 <?= $message['sender_id'] == $_SESSION['user_id'] ? 'text-end' : '' ?>">
        <div class="d-inline-block p-2 rounded <?= $message['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 75%; font-size: 0.85rem;">
            <div class="mb-1">
                <small class="<?= $message['sender_id'] == $_SESSION['user_id'] ? 'text-light' : 'text-muted' ?>" style="font-size: 0.7rem;">
                    <?= htmlspecialchars($message['sender_name']) ?>
                </small>
            </div>
            <div><?= nl2br(htmlspecialchars($message['message'])) ?></div>
            <small class="<?= $message['sender_id'] == $_SESSION['user_id'] ? 'text-light' : 'text-muted' ?> d-block mt-1" style="font-size: 0.65rem;">
                <?= date('M j, H:i', strtotime($message['created_at'])) ?>
            </small>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
