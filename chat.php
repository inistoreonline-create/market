
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get seller ID from URL parameter
$seller_id = $_GET['seller'] ?? null;
$seller_info = null;

if ($seller_id) {
    // Get seller information
    $query = "SELECT id, username, is_online, last_seen FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seller_info) {
        $_SESSION['error'] = "User not found!";
        header("Location: chat.php");
        exit();
    }
    
    // Don't allow messaging yourself
    if ($seller_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot message yourself!";
        header("Location: chat.php");
        exit();
    }
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    $receiver_id = $_POST['receiver_id'];
    
    if (!empty($message) && !empty($receiver_id)) {
        $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$_SESSION['user_id'], $receiver_id, $message])) {
            $_SESSION['success'] = "Message sent successfully!";
            // Redirect to avoid form resubmission
            header("Location: chat.php?seller=" . $receiver_id);
            exit();
        } else {
            $_SESSION['error'] = "Failed to send message!";
        }
    } else {
        $_SESSION['error'] = "Please enter a message!";
    }
}

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

// Get messages between user and seller
$messages = [];
if ($seller_id) {
    $query = "SELECT m.*, u.username as sender_name FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
              ORDER BY m.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $seller_id, $seller_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read (messages sent TO current user FROM seller)
    $read_query = "UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE";
    $read_stmt = $db->prepare($read_query);
    $read_stmt->execute([$seller_id, $_SESSION['user_id']]);
}

include 'includes/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Sidebar with conversations -->
        <div class="col-md-3">
            <div class="card" style="height: 200px;">
                <div class="card-header">
                    
                </div>
                <div class="card-body p-0" style="overflow-y: auto;">
                    <?php if (empty($conversations)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-inbox fa-lg mb-2"></i>
                        <p class="small">No conversations yet</p>
                        <small>Go to marketplace and message a seller</small>
                    </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                        <a href="chat.php?seller=<?= $conversation['user_id'] ?>" 
                           class="d-block text-decoration-none border-bottom p-2 <?= $seller_id == $conversation['user_id'] ? 'bg-primary bg-opacity-10' : '' ?>" 
                           style="color: inherit;">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="mb-0 small fw-bold"><?= htmlspecialchars($conversation['username']) ?></span>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill" style="font-size: 0.7rem;"><?= $conversation['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                            <!--
                                <p class="mb-1 text-muted" style="font-size: 0.75rem;"> 
                               <?//= htmlspecialchars(substr($conversation['last_message'] ?? '', 0, 40)) ?>
                               <?//= strlen($conversation['last_message'] ?? '') > 40 ? '...' : '' ?>
                                </p>
                            -->       
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <i class="fas fa-circle <?= $conversation['is_online'] ? 'text-success' : 'text-secondary' ?>"></i>
                                            <?= $conversation['is_online'] ? 'Online' : 'Offline' ?>
                                        </small>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <?= $conversation['last_message_time'] ? date('M j, H:i', strtotime($conversation['last_message_time'])) : '' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat area -->
        <div class="col-md-8">
            <h6><i class="fas fa-comments"></i> Messages</h6>
            <?php if (!$seller_id): ?>
            <div class="card" style="height: 600px;">
                <div class="card-body d-flex align-items-center justify-content-center text-center">
                    <div>
                        <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                        <h6>Select a conversation</h6>
                        <p class="text-muted small">Choose a conversation from the sidebar to view messages</p>
                        <a href="marketplace.php" class="btn btn-primary btn-sm">Browse Marketplace</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card" style="height: 600px;">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($seller_info['username']) ?></h6>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="fas fa-circle <?= $seller_info['is_online'] ? 'text-success' : 'text-secondary' ?>"></i>
                                <?= $seller_info['is_online'] ? 'Online' : 'Last seen ' . date('M j, H:i', strtotime($seller_info['last_seen'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body d-flex flex-column p-2" style="height: calc(100% - 60px);">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-sm p-2">
                        <small><?= htmlspecialchars($_SESSION['success']) ?></small>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-sm p-2">
                        <small><?= htmlspecialchars($_SESSION['error']) ?></small>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Messages Display -->
                    <div class="flex-grow-1 border rounded p-2 mb-2" style="overflow-y: auto; max-height: calc(100% - 80px);" id="messagesContainer">
                        <?php if (empty($messages)): ?>
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
                    </div>
                    
                    <!-- Message Form -->
                    <form method="POST">
                        <input type="hidden" name="receiver_id" value="<?= $seller_id ?>">
                        <div class="input-group">
                            <textarea class="form-control form-control-sm" name="message" rows="2" placeholder="Type your message..." required style="font-size: 0.85rem;"></textarea>
                            <button class="btn btn-primary btn-sm" type="submit" name="send_message">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});

// Auto refresh messages every 5 seconds
setInterval(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('seller')) {
        const container = document.getElementById('messagesContainer');
        const scrollPos = container.scrollTop;
        
        fetch('ajax/get_messages.php?seller=' + urlParams.get('seller'))
            .then(response => response.text())
            .then(data => {
                container.innerHTML = data;
                container.scrollTop = scrollPos;
            })
            .catch(error => console.log('Error refreshing messages:', error));
    }
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>
