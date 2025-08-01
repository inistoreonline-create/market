<?php
// Added CSS styles for category cards
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameMarket - Gaming Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FFD700;
            background-image: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            min-height: 100vh;
        }
        .navbar {
            background-color: rgba(0,0,0,0.8) !important;
        }
        .card {
            background-color: rgba(255,255,255,0.95);
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #FF6B35;
            border-color: #FF6B35;
        }
        .btn-primary:hover {
            background-color: #E55A2B;
            border-color: #E55A2B;
        }
        .online-indicator {
            color: #28a745;
        }
        .offline-indicator {
            color: #6c757d;
        }
        .category-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #dee2e6;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .category-card .card-body {
            padding: 2rem 1rem;
        }
        .category-card h5 {
            color: #333;
        }
        .category-card p {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-gamepad"></i> GameMarket</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="marketplace.php">Marketplace</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="sell.php">Sell Item</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">
                            Messages
                            <?php 
                            // Get unread message count
                            if (isset($_SESSION['user_id'])) {
                                require_once 'config/database.php';
                                $database = new Database();
                                $db = $database->getConnection();
                                $unread_query = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = FALSE";
                                $unread_stmt = $db->prepare($unread_query);
                                $unread_stmt->execute([$_SESSION['user_id']]);
                                $unread_result = $unread_stmt->fetch(PDO::FETCH_ASSOC);
                                $unread_count = $unread_result['unread_count'];
                                if ($unread_count > 0): 
                            ?>
                            <span class="badge bg-danger"><?= $unread_count ?></span>
                            <?php 
                                endif;
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="badge bg-danger"><?= count($_SESSION['cart']) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?= $_SESSION['username'] ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>