
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    if ($delete_stmt->execute([$cart_id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Item removed from cart!";
    }
}

// Handle clear cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_cart'])) {
    $clear_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $db->prepare($clear_query);
    if ($clear_stmt->execute([$_SESSION['user_id']])) {
        $_SESSION['success'] = "Cart cleared!";
    }
}

// Handle note update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_note'])) {
    $cart_id = $_POST['cart_id'];
    $note = $_POST['note'];
    $update_query = "UPDATE cart SET note = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $db->prepare($update_query);
    if ($update_stmt->execute([$note, $cart_id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Note updated!";
    }
}

// Get cart items from database
$cart_query = "SELECT c.id as cart_id, c.note, p.*, u.username as seller_name 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               JOIN users u ON p.seller_id = u.id 
               WHERE c.user_id = ? 
               ORDER BY c.added_at DESC";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([$_SESSION['user_id']]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'];
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-shopping-cart"></i> Shopping Cart</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($cart_items)): ?>
                    <div class="text-center">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>Your cart is empty</h5>
                        <p class="text-muted">Start shopping to add items to your cart</p>
                        <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-8">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="card mb-3">
                                <div class="row g-0">
                                    <div class="col-md-3">
                                        <img src="uploads/<?= $item['image'] ?? 'placeholder.jpg' ?>" 
                                             class="img-fluid rounded-start" 
                                             alt="<?= htmlspecialchars($item['title']) ?>"
                                             style="object-fit: cover; width: 220px; height: 230px;">
                                    </div>
                                    <div class="col-md-9">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6 class="card-title" style="font-size: 0.8rem;"><?= htmlspecialchars($item['title']) ?></h6>
                                                    <p class="card-text" style="font-size: 0.8rem;">
                                                        <small class="text-muted">Sold by <?= htmlspecialchars($item['seller_name']) ?></small>
                                                    </p>
                                                    <p class="card-text" style="font-size: 0.8rem;">
                                                        <strong class="text-primary">Rp <?= number_format($item['price'], 0) ?></strong>
                                                    </p>
                                                    <form method="POST">
                                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="note" class="form-label" style="font-size: 0.8rem;">Note:</label>
                                                            <textarea class="form-control" id="note" name="note" rows="1" style="font-size: 0.8rem;"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
                                                        </div>
                                                        <button type="submit" name="update_note" class="btn btn-primary btn-sm" style="font-size: 0.8rem;">Update Note</button>
                                                    </form>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                        <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Order Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Items (<?= count($cart_items) ?>):</span>
                                        <span>Rp <?= number_format($total_price, 0) ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>Total:</strong>
                                        <strong class="text-primary">Rp <?= number_format($total_price, 0) ?></strong>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-lg" onclick="alert('Checkout functionality coming soon!')">
                                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                                        </button>
                                        <form method="POST" class="mt-2">
                                            <button type="submit" name="clear_cart" class="btn btn-outline-secondary w-100"
                                                    onclick="return confirm('Are you sure you want to clear your cart?')">
                                                <i class="fas fa-trash"></i> Clear Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-info-circle"></i> Cart Information</h6>
                                    <ul class="mb-0 small">
                                        <li>Items are reserved for 24 hours</li>
                                        <li>Contact sellers for payment details</li>
                                        <li>Meet safely in public places</li>
                                        <li>Inspect items before paying</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
