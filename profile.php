
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user information
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's products
$products_query = "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute([$_SESSION['user_id']]);
$user_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    // Delete the product
    $delete_query = "DELETE FROM products WHERE id = ? AND seller_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    
    if ($delete_stmt->execute([$product_id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Product deleted successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $error = "Failed to delete product.";
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- User Dashboard -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user-circle"></i> User Dashboard</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                        <h5 class="mt-2"><?= htmlspecialchars($user['username']) ?></h5>
                        <?php if (!empty($user['full_name'])): ?>
                        <p class="text-muted"><?= htmlspecialchars($user['full_name']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Email:</small>
                        <p class="mb-1"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Member Since:</small>
                        <p class="mb-1"><?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Total Items Listed:</small>
                        <p class="mb-1"><strong><?= count($user_products) ?></strong></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Status:</small>
                        <p class="mb-1">
                            <span class="badge <?= $user['is_online'] ? 'bg-success' : 'bg-secondary' ?>">
                                <i class="fas fa-circle"></i> <?= $user['is_online'] ? 'Online' : 'Offline' ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="d-grid">
                        <a href="sell.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> List New Item
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar"></i> Quick Stats</h6>
                </div>
                <div class="card-body">
                    <?php
                    $active_count = count(array_filter($user_products, function($p) { return $p['status'] == 'active'; }));
                    $sold_count = count(array_filter($user_products, function($p) { return $p['status'] == 'sold'; }));
                    $total_value = array_sum(array_column(array_filter($user_products, function($p) { return $p['status'] == 'active'; }), 'price'));
                    ?>
                    <div class="row text-center">
                        <div class="col-4">
                            <h6 class="text-success"><?= $active_count ?></h6>
                            <small class="text-muted">Active</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-primary"><?= $sold_count ?></h6>
                            <small class="text-muted">Sold</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-warning">$<?= number_format($total_value, 0) ?></h6>
                            <small class="text-muted">Value</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User's Products -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-box"></i> My Listed Items</h5>
                    <a href="sell.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add New Item
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($user_products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h6>No items listed yet</h6>
                        <p class="text-muted">Start selling by listing your first gaming item!</p>
                        <a href="sell.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> List Your First Item
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($user_products as $product): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <img src="uploads/<?= $product['image'] ?? 'placeholder.jpg' ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($product['title']) ?>"
                                     style="height: 150px; object-fit: cover;">
                                <div class="card-body">
                                    <h6 class="card-title" style="font-size: 0.9rem;"><?= htmlspecialchars($product['title']) ?></h6>
                                    <p class="card-text" style="font-size: 0.8rem;">
                                        <?= htmlspecialchars(substr($product['description'], 0, 60)) ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong class="text-primary">$<?= number_format($product['price'], 2) ?></strong>
                                        <span class="badge <?= $product['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($product['category']) ?>
                                    </small>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="product.php?id=<?= $product['id'] ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?= $product['id'] ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Listed: <?= date('M d, Y', strtotime($product['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?= $product['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            Are you sure you want to delete "<strong><?= htmlspecialchars($product['title']) ?></strong>"?
                                            <br><small class="text-muted">This action cannot be undone.</small>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
