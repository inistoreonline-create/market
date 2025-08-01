
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get list of users who have conversations with current user
$conversations_query = "
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as user_id,
        u.username,
        u.is_online,
        u.last_seen,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END 
         AND receiver_id = ? AND is_read = FALSE) as unread_count,
        (SELECT message FROM messages m2 
         WHERE (m2.sender_id = ? AND m2.receiver_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
         OR (m2.receiver_id = ? AND m2.sender_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
         ORDER BY m2.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages m2 
         WHERE (m2.sender_id = ? AND m2.receiver_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
         OR (m2.receiver_id = ? AND m2.sender_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
         ORDER BY m2.created_at DESC LIMIT 1) as last_message_time
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
";

$stmt = $db->prepare($conversations_query);
$stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_seller = $_GET['current_seller'] ?? null;

if (empty($conversations)): ?>
<div class="p-3 text-center text-muted">
    <i class="fas fa-inbox fa-2x mb-2"></i>
    <p>No conversations yet</p>
    <small>Go to marketplace and message a seller</small>
</div>
<?php else: ?>
    <?php foreach ($conversations as $conversation): ?>
    <a href="chat.php?seller=<?= $conversation['user_id'] ?>" 
       class="d-block text-decoration-none border-bottom p-3 <?= $current_seller == $conversation['user_id'] ? 'bg-primary bg-opacity-10' : '' ?>" 
       style="color: inherit;">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="mb-0"><?= htmlspecialchars($conversation['username']) ?></h6>
                    <?php if ($conversation['unread_count'] > 0): ?>
                    <span class="badge bg-primary rounded-pill"><?= $conversation['unread_count'] ?></span>
                    <?php endif; ?>
                </div>
                <p class="mb-1 text-muted small"><?= htmlspecialchars(substr($conversation['last_message'] ?? '', 0, 50)) ?><?= strlen($conversation['last_message'] ?? '') > 50 ? '...' : '' ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-circle <?= $conversation['is_online'] ? 'text-success' : 'text-secondary' ?>"></i>
                        <?= $conversation['is_online'] ? 'Online' : 'Offline' ?>
                    </small>
                    <small class="text-muted">
                        <?= $conversation['last_message_time'] ? date('M j, H:i', strtotime($conversation['last_message_time'])) : '' ?>
                    </small>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
<?php endif; ?>
