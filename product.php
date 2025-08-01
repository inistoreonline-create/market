
<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    header("Location: marketplace.php");
    exit();
}

// Get product details
$query = "SELECT p.*, u.username, u.is_online, u.last_seen FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.id = ? AND p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: marketplace.php");
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    if ($_SESSION['user_id'] == $product['seller_id']) {
        $_SESSION['error'] = "You cannot buy your own item!";
    } else {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if item already in cart
        $item_exists = false;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['id'] == $product_id) {
                $item_exists = true;
                break;
            }
        }
        
        if ($item_exists) {
            $_SESSION['error'] = "Item is already in your cart!";
        } else {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => $product['image'],
                'seller' => $product['username']
            ];
            $_SESSION['success'] = "Item added to cart successfully!";
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="row g-0">
                    <div class="col-md-6">
                        <img src="uploads/<?= $product['image'] ?? 'placeholder.jpg' ?>" 
                             class="img-fluid rounded-start h-100" 
                             alt="<?= htmlspecialchars($product['title']) ?>"
                             style="object-fit: cover; min-height: 300px;">
                    </div>
                    <div class="col-md-6">
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($product['title']) ?></h3>
                            <h4 class="text-primary mb-3">Rp <?= number_format($product['price'], 0) ?></h4>
                            
                            <?php if ($product['category']): ?>
                            <p><strong>Category:</strong> 
                                <span class="badge bg-secondary"><?= htmlspecialchars($product['category']) ?></span>
                            </p>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p class="mt-2"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            </div>
                            
                            <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($_SESSION['success']) ?>
                                <?php unset($_SESSION['success']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($_SESSION['error']) ?>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <?php if ($_SESSION['user_id'] != $product['seller_id']): ?>
                                    <form method="POST" class="mb-2">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                    <a href="chat.php?user=<?= $product['seller_id'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-comment"></i> Message Seller
                                    </a>
                                    <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> This is your own listing
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Login to Buy
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user"></i> Seller Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?= htmlspecialchars($product['username']) ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-circle <?= $product['is_online'] ? 'online-indicator' : 'offline-indicator' ?>" style="font-size: 0.7em;"></i>
                                <?= $product['is_online'] ? 'Online' : 'Last seen ' . date('M j, Y', strtotime($product['last_seen'])) ?>
                            </small>
                        </div>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['seller_id']): ?>
                    <a href="chat.php?user=<?= $product['seller_id'] ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-comment"></i> Contact Seller
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-shield-alt"></i> Safety Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 small">
                        <li>Meet in public places</li>
                        <li>Inspect items before buying</li>
                        <li>Use secure payment methods</li>
                        <li>Trust your instincts</li>
                        <li>Report suspicious activity</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <a href="marketplace.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Marketplace
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
