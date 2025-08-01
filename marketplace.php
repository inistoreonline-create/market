
<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT p.*, u.username, u.is_online FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.status = 'active'";
$params = [];

if ($search) {
    $query .= " AND (p.title ILIKE ? OR p.description ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND p.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$cat_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2><i class="fas fa-store"></i> Marketplace</h2>
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search items..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category == $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if (empty($products)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No items found</h5>
                    <p>Try adjusting your search criteria</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="uploads/<?= $product['image'] ?? 'placeholder.jpg' ?>" class="card-img-top" alt="<?= htmlspecialchars($product['title']) ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['title']) ?></h5>
                    <p class="card-text"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                    <p class="card-text"><strong>Rp <?= number_format($product['price'], 0) ?></strong></p>
                    <p class="card-text">
                        <small class="text-muted">
                            by <?= htmlspecialchars($product['username']) ?>
                            <i class="fas fa-circle <?= $product['is_online'] ? 'online-indicator' : 'offline-indicator' ?>" style="font-size: 0.7em;"></i>
                        </small>
                    </p>
                    <?php if ($product['category']): ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($product['category']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['seller_id']): ?>
                    <a href="chat.php?seller=<?= $product['seller_id'] ?>" class="btn btn-outline-primary">Message Seller</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
