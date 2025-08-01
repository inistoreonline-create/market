
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    
    // Validate input
    $errors = [];
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($price <= 0) $errors[] = "Price must be greater than 0";
    if (empty($category)) $errors[] = "Category is required";
    
    // Handle image upload
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $image_name = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid image format. Only JPG, PNG and GIF allowed";
        }
    }
    
    // Insert product
    if (empty($errors)) {
        $query = "INSERT INTO products (seller_id, title, description, price, category, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$_SESSION['user_id'], $title, $description, $price, $category, $image_name])) {
            $_SESSION['success'] = "Item listed successfully!";
            header("Location: marketplace.php");
            exit();
        } else {
            $errors[] = "Failed to list item. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus-circle"></i> Sell Your Gaming Item</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Item Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="e.g., Gaming Keyboard, PS5 Game, etc." 
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Gaming Consoles" <?= ($_POST['category'] ?? '') == 'Gaming Consoles' ? 'selected' : '' ?>>Gaming Consoles</option>
                                <option value="PC Gaming" <?= ($_POST['category'] ?? '') == 'PC Gaming' ? 'selected' : '' ?>>PC Gaming</option>
                                <option value="Games" <?= ($_POST['category'] ?? '') == 'Games' ? 'selected' : '' ?>>Games</option>
                                <option value="Accessories" <?= ($_POST['category'] ?? '') == 'Accessories' ? 'selected' : '' ?>>Accessories</option>
                                <option value="Controllers" <?= ($_POST['category'] ?? '') == 'Controllers' ? 'selected' : '' ?>>Controllers</option>
                                <option value="Headsets" <?= ($_POST['category'] ?? '') == 'Headsets' ? 'selected' : '' ?>>Headsets</option>
                                <option value="Keyboards & Mice" <?= ($_POST['category'] ?? '') == 'Keyboards & Mice' ? 'selected' : '' ?>>Keyboards & Mice</option>
                                <option value="Monitors" <?= ($_POST['category'] ?? '') == 'Monitors' ? 'selected' : '' ?>>Monitors</option>
                                <option value="Other" <?= ($_POST['category'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price (USD) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       step="0.01" min="0.01" placeholder="0.00" 
                                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" 
                                      placeholder="Describe your item's condition, features, and any other relevant details..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Item Image</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif">
                            <div class="form-text">Optional: Upload an image of your item (JPG, PNG, GIF - Max 5MB)</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> List Item for Sale
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="marketplace.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle"></i> Selling Tips</h6>
                    <ul class="mb-0">
                        <li>Use clear, descriptive titles</li>
                        <li>Include high-quality photos</li>
                        <li>Be honest about item condition</li>
                        <li>Set competitive prices</li>
                        <li>Respond quickly to buyer messages</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
