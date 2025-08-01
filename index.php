
<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get featured products
$query = "SELECT p.*, u.username FROM products p JOIN users u ON p.seller_id = u.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h1 class="display-4"><i class="fas fa-gamepad text-warning"></i> GameMarket</h1>
                    <p class="lead">The ultimate marketplace for gamers - Buy, Sell, and Trade gaming items!</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-primary btn-lg me-2">Join Now</a>
                    <a href="marketplace.php" class="btn btn-outline-primary btn-lg">Browse Items</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center mb-3"><i class="fas fa-search"></i> Find Gaming Items</h4>
                    <form action="marketplace.php" method="GET">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control form-control-lg" name="search" 
                                       placeholder="Search for games, consoles, accessories..." 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="text-center mb-4">Browse by Category</h3>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="marketplace.php?category=Game Item" class="text-decoration-none">
                <div class="card h-100 text-center category-card">
                    <div class="card-body">
                        <i class="fas fa-gamepad fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Game Item</h5>
                        <p class="card-text">Video games, digital codes, and game content</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="marketplace.php?category=Game Booster" class="text-decoration-none">
                <div class="card h-100 text-center category-card">
                    <div class="card-body">
                        <i class="fas fa-rocket fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Game Booster</h5>
                        <p class="card-text">Performance enhancers, accounts, and boosting services</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="marketplace.php?category=Hardware Game" class="text-decoration-none">
                <div class="card h-100 text-center category-card">
                    <div class="card-body">
                        <i class="fas fa-microchip fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Hardware Game</h5>
                        <p class="card-text">Gaming PCs, consoles, graphics cards, and components</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="marketplace.php?category=Accessories" class="text-decoration-none">
                <div class="card h-100 text-center category-card">
                    <div class="card-body">
                        <i class="fas fa-headphones fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Accessories</h5>
                        <p class="card-text">Controllers, headsets, keyboards, mice, and more</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

    <div class="row">
        <div class="col-12">
           <h3 class="text-center mb-4">Featured Items</h3>
        </div>
    </div>
    
        <div class="row mx-auto" style="max-width: 90%;">

    <div class="row mx-2">
        <?php foreach ($featured_products as $product): ?>
        <div class="col-md-2 mb-2">
            <div class="card h-100">
                <a href="product.php?id=<?= $product['id'] ?>" class="btn">
                <img src="uploads/<?= $product['image'] ?? 'placeholder.jpg' ?>" class="card-img-top" alt="<?= htmlspecialchars($product['title']) ?>" style="height: 200px; object-fit: cover;">
                 <div class="card-body">
                    <p class="card-title mb-1" style="font-size: 0.75rem; font-weight: bold;"><?= htmlspecialchars($product['title']) ?></p>
                    <p class="card-text" style="font-size: 0.7rem;"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                    <p class="card-text" style="font-size: 0.7rem;"><strong>$<?= number_format($product['price'], 2) ?></strong></p>
                    <p class="card-text" style="font-size: 0.65rem;"><small class="text-muted">by <?= htmlspecialchars($product['username']) ?></small></p>
                </div>
                </a>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
