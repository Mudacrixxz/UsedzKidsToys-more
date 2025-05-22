<?php
// Check if admin is logged in, redirect if not
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle product actions
$message = '';

// Delete product
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Product deleted successfully";
    } else {
        $message = "Error deleting product: " . $conn->error;
    }
}

// Add or update product
if (isset($_POST['save_product'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $condition = $_POST['condition'];
    $stock = (int)$_POST['stock'];
    
    // Handle image upload (simplified, no actual upload in this demo)
    $image = 'default.jpg';
    if (!empty($_POST['image'])) {
        $image = $_POST['image'];
    }
    
    if ($id > 0) {
        // Update existing product
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image = ?, `condition` = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("ssdissii", $name, $description, $price, $category_id, $image, $condition, $stock, $id);
        
        if ($stmt->execute()) {
            $message = "Product updated successfully";
        } else {
            $message = "Error updating product: " . $conn->error;
        }
    } else {
        // Add new product
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, image, `condition`, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdissi", $name, $description, $price, $category_id, $image, $condition, $stock);
        
        if ($stmt->execute()) {
            $message = "Product added successfully";
        } else {
            $message = "Error adding product: " . $conn->error;
        }
    }
}

// Get categories for form select
$categories = [];
$result = $conn->query("SELECT id, name FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Check if editing a product
$editProduct = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editProduct = $result->fetch_assoc();
    }
}

// Get all products
$products = [];
$result = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name");
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Close database connection
$conn->close();
?>

<h2>Products Management</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Add/Edit Product Form -->
<div class="product-form">
    <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
    
    <form method="post" action="index.php?view=products">
        <?php if ($editProduct): ?>
            <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="name">Product Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $editProduct ? $editProduct['name'] : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required><?php echo $editProduct ? $editProduct['description'] : ''; ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price (£):</label>
                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo $editProduct ? $editProduct['stock'] : ''; ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($editProduct && $editProduct['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="condition">Condition:</label>
                <select id="condition" name="condition" required>
                    <option value="">Select Condition</option>
                    <option value="Excellent" <?php echo ($editProduct && $editProduct['condition'] == 'Excellent') ? 'selected' : ''; ?>>Excellent</option>
                    <option value="Very Good" <?php echo ($editProduct && $editProduct['condition'] == 'Very Good') ? 'selected' : ''; ?>>Very Good</option>
                    <option value="Good" <?php echo ($editProduct && $editProduct['condition'] == 'Good') ? 'selected' : ''; ?>>Good</option>
                    <option value="Fair" <?php echo ($editProduct && $editProduct['condition'] == 'Fair') ? 'selected' : ''; ?>>Fair</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="image">Image Filename:</label>
            <input type="text" id="image" name="image" value="<?php echo $editProduct ? $editProduct['image'] : ''; ?>">
            <small>Enter the filename of the product image, e.g., "toy.jpg"</small>
        </div>
        
        <div class="form-group">
            <button type="submit" name="save_product" class="btn">Save Product</button>
            <?php if ($editProduct): ?>
                <a href="index.php?view=products" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Products List -->
<h3>All Products</h3>
<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Condition</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($products)): ?>
            <tr>
                <td colspan="8">No products found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td>
                        <?php if ($product['image']): ?>
                            <img src="../images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" width="50">
                        <?php else: ?>
                            <span>No image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo $product['category_name']; ?></td>
                    <td>£<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['condition']; ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td class="action-buttons">
                        <a href="index.php?view=products&edit=<?php echo $product['id']; ?>" class="btn btn-small">Edit</a>
                        <a href="index.php?view=products&delete=<?php echo $product['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table> 