<?php
require_once 'config.php';
requireAuth();
logAccess();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Dashboard</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <!-- Hamburger Menu (Mobile) -->
    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Categories</h2>
        </div>
        <nav class="sidebar-nav" id="categoryList">
            <a href="#" class="category-item active" data-category="">All Products</a>
            <!-- Categories loaded dynamically -->
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="content-header">
            <h1>Product Dashboard</h1>
            <div class="header-actions">
                <div class="search-box">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search products..."
                        autocomplete="off"
                    >
                    <div class="search-results" id="searchResults"></div>
                </div>
                <button class="btn btn-primary" id="addProductBtn">+ Add Product</button>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </header>

        <!-- Tab Navigation -->
        <nav class="tabs">
            <button class="tab active" data-tab="products">Products</button>
            <button class="tab" data-tab="analytics">Analytics</button>
        </nav>

        <!-- Products Tab -->
        <section class="tab-content active" id="productsTab">
            <div class="products-grid" id="productsGrid">
                <!-- Products loaded dynamically -->
            </div>
            <div class="loading" id="loading">Loading more products...</div>
        </section>

        <!-- Analytics Tab -->
        <section class="tab-content" id="analyticsTab">
            <div class="analytics-container">
                <h2>Daily Active Users</h2>
                <div class="chart-controls">
                    <input type="date" id="analyticsDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <canvas id="dauChart"></canvas>
            </div>
        </section>
    </main>

    <!-- Add Product Modal -->
    <div class="modal" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <button class="modal-close" id="modalClose">&times;</button>
            </div>
            <form id="productForm">
                <div class="form-group">
                    <label for="productTitle">Title *</label>
                    <input type="text" id="productTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="productCategory">Category</label>
                        <select id="productCategory" name="category">
                            <option value="">Select category</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="productPrice">Price</label>
                        <input type="number" id="productPrice" name="price" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="productStock">Stock</label>
                        <input type="number" id="productStock" name="stock" min="0">
                    </div>
                    <div class="form-group">
                        <label for="productBrand">Brand</label>
                        <input type="text" id="productBrand" name="brand">
                    </div>
                </div>
                <div class="form-group">
                    <label for="productImages">Images</label>
                    <div class="dropzone" id="dropzone">
                        <p>Drag & drop images here or click to upload</p>
                        <input type="file" id="fileInput" multiple accept="image/*" hidden>
                    </div>
                    <div class="image-preview" id="imagePreview"></div>
                    <div class="upload-progress" id="uploadProgress" style="display:none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <span class="progress-text" id="progressText">0%</span>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            </form>
        </div>
    </div>

    <script src="static/js/app.js"></script>
</body>
</html>