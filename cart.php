<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $remove_id = $_POST['item_id'];
    if (isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($remove_id) {
            return $item['id'] != $remove_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
        $_SESSION['success'] = "Item removed from cart!";
    }
}

// Handle clear cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['success'] = "Cart cleared!";
}

// Handle note update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_note'])) {
    $item_id = $_POST['item_id'];
    $note = $_POST['note'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            $_SESSION['cart'][$key]['note'] = $note;
            $_SESSION['success'] = "Note updated!";
            break;
        }
    }
}


$cart_items = $_SESSION['cart'] ?? [];
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
                                                        <small class="text-muted">Sold by <?= htmlspecialchars($item['seller']) ?></small>
                                                    </p>
                                                    <p class="card-text" style="font-size: 0.8rem;">
                                                        <strong class="text-primary">Rp <?= number_format($item['price'], 0) ?></strong>
                                                    </p>
                                                    <form method="POST">
                                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="note" class="form-label" style="font-size: 0.8rem;">Note:</label>
                                                            <textarea class="form-control" id="note" name="note" rows="1" style="font-size: 0.8rem;"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
                                                        </div>
                                                        <button type="submit" name="update_note" class="btn btn-primary btn-sm" style="font-size: 0.8rem;">Update Note</button>
                                                    </form>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
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