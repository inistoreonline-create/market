
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get users to chat with
$query = "SELECT DISTINCT u.id, u.username, u.is_online, u.last_seen,
          (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message
          FROM users u 
          WHERE u.id != ? 
          ORDER BY u.is_online DESC, u.last_seen DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_user = $_GET['user'] ?? null;
$messages = [];

if ($selected_user) {
    // Get messages with selected user
    $query = "SELECT m.*, u.username FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
              ORDER BY m.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $selected_user, $selected_user, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read
    $read_query = "UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ?";
    $read_stmt = $db->prepare($read_query);
    $read_stmt->execute([$selected_user, $_SESSION['user_id']]);
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Conversations</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($users as $user): ?>
                        <a href="chat.php?user=<?= $user['id'] ?>" class="list-group-item list-group-item-action <?= $selected_user == $user['id'] ? 'active' : '' ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <?= htmlspecialchars($user['username']) ?>
                                    <i class="fas fa-circle <?= $user['is_online'] ? 'online-indicator' : 'offline-indicator' ?>" style="font-size: 0.7em;"></i>
                                </h6>
                                <small><?= $user['is_online'] ? 'Online' : 'Offline' ?></small>
                            </div>
                            <?php if ($user['last_message']): ?>
                            <p class="mb-1 text-truncate"><?= htmlspecialchars($user['last_message']) ?></p>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($selected_user): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-comments"></i> Chat</h5>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;" id="chatMessages">
                    <?php foreach ($messages as $message): ?>
                    <div class="mb-3 <?= $message['sender_id'] == $_SESSION['user_id'] ? 'text-end' : '' ?>">
                        <div class="d-inline-block p-2 rounded <?= $message['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                            <div><?= htmlspecialchars($message['message']) ?></div>
                            <small class="text-muted d-block"><?= date('M j, H:i', strtotime($message['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <form id="messageForm">
                        <div class="input-group">
                            <input type="text" class="form-control" id="messageInput" placeholder="Type a message..." required>
                            <input type="hidden" id="receiverId" value="<?= $selected_user ?>">
                            <button class="btn btn-primary" type="submit">Send</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5>Select a conversation to start chatting</h5>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$('#messageForm').on('submit', function(e) {
    e.preventDefault();
    
    const message = $('#messageInput').val().trim();
    const receiverId = $('#receiverId').val();
    
    if (message && receiverId) {
        $.post('ajax/send_message.php', {
            message: message,
            receiver_id: receiverId
        }, function(response) {
            if (response.success) {
                $('#messageInput').val('');
                location.reload(); // Refresh to show new message
            }
        }, 'json');
    }
});

// Auto-scroll to bottom
$('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
</script>

<?php include 'includes/footer.php'; ?>
