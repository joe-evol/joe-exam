// app.js - Basic functionality

let currentPage = 1;
let currentCategory = '';
let isLoading = false;
var selectedFiles = [];
var isSearching = false;
let hasMorePages = true;


// Load categories on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadProducts();
    setupEventListeners();
});

// Fetch and display categories
function loadCategories() {
    fetch('/api/api.php?path=categories', { credentials: 'include'})
        .then(res => res.json())
        .then(categories => {
            const categoryList = document.getElementById('categoryList');
            categoryList.innerHTML = '<a href="#" class="category-item active" data-category="">All Products</a>';
            
            categories.forEach(cat => {
                const link = document.createElement('a');
                link.href = '#';
                link.className = 'category-item';
                link.textContent = cat.name;
                link.dataset.category = cat.slug;
                categoryList.appendChild(link);
            });
            
            // Add category click handlers
            document.querySelectorAll('.category-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    currentCategory = this.dataset.category;
                    currentPage = 1;
                    hasMorePages = true; 
                    isSearching = false; 
                    document.getElementById('productsGrid').innerHTML = '';
                    loadProducts();
                });
            });

            const categorySelect = document.getElementById('productCategory');
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.slug;
                option.textContent = cat.name;
                categorySelect.appendChild(option);
            });
        });
}

// Fetch and display products
function loadProducts() {
    // if (isLoading) return;
    if (isLoading || !hasMorePages) return; // Check hasMorePages

    isLoading = true;
    document.getElementById('loading').classList.add('show');
    
    let url = `/api/api.php?path=products&page=${currentPage}&limit=40`;
    if (currentCategory) url += `&category=${currentCategory}`;
    
    fetch(url, { credentials: 'include'})
        .then(res => res.json())
        .then(data => {
            const grid = document.getElementById('productsGrid');
            
            data.products.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <img src="${product.thumbnail || 'https://via.placeholder.com/300'}" 
                        loading="lazy"
                         alt="${product.title}" class="product-image">
                    <div class="product-info">
                        <h3 class="product-title">${product.title}</h3>
                        <p class="product-description">${product.description || ''}</p>
                        <div class="product-price">$${product.price}</div>
                    </div>
                `;
                grid.appendChild(card);
                
            });
            
            isLoading = false;
            document.getElementById('loading').classList.remove('show');
            // If there are more pages, increment for next load
            if (currentPage < data.pages) {
                currentPage++;
            } else {
                hasMorePages = false; // No more pages to load
            }
        });
}

// Infinite scroll
window.addEventListener('scroll', function() {
    if (isSearching) return; 
    if (isLoading) return; // Add this check
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        loadProducts();
    }
});

// Search functionality
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        document.getElementById('searchResults').classList.remove('show');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`/api/api.php?path=products&search=${encodeURIComponent(query)}&limit=10`, { credentials: 'include'})
            .then(res => res.json())
            .then(data => {
                const results = document.getElementById('searchResults');
                results.innerHTML = '';
                
                if (data.products.length === 0) {
                    results.innerHTML = '<div class="search-result-item">No results found</div>';
                } else {
                    data.products.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'search-result-item';
                        item.textContent = `${product.title} - $${product.price}`;
                        item.addEventListener('click', () => {
                            document.getElementById('searchInput').value = product.title;
                            results.classList.remove('show');
                            currentPage = 1;
                            currentCategory = product.category;
                            document.getElementById('productsGrid').innerHTML = '';
                            loadProducts();
                        });
                        results.appendChild(item);
                    });
                }
                
                results.classList.add('show');
            });
    }, 300);
});
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = e.target.value.trim();
        if (query.length > 0) {
            currentPage = 1;
            currentCategory = '';
            document.getElementById('productsGrid').innerHTML = '';
            document.getElementById('searchResults').classList.remove('show');
            loadProductsWithSearch(query);
        }
    }
});

function loadProductsWithSearch(query) {
    if (isLoading) return;
    isLoading = true;
    isSearching = true;
    document.getElementById('loading').classList.add('show');
    
    let url = `/api/api.php?path=products&page=${currentPage}&limit=20&search=${encodeURIComponent(query)}`;
    
    fetch(url, { credentials: 'include'})
        .then(res => res.json())
        .then(data => {
            const grid = document.getElementById('productsGrid');
            
            data.products.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <img src="${product.thumbnail || 'https://via.placeholder.com/300'}" 
                        loading="lazy"
                         alt="${product.title}" class="product-image">
                    <div class="product-info">
                        <h3 class="product-title">${product.title}</h3>
                        <p class="product-description">${product.description || ''}</p>
                        <div class="product-price">$${product.price}</div>
                    </div>
                `;
                grid.appendChild(card);
            });
            
            isLoading = false;
            document.getElementById('loading').classList.remove('show');
            
            if (currentPage < data.pages) {
                currentPage++;
            }
        });
}


// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-box')) {
        document.getElementById('searchResults').classList.remove('show');
    }
});

// Setup event listeners
function setupEventListeners() {
    // Hamburger menu
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    if (hamburger) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
    
    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            const targetTab = this.dataset.tab;
            document.getElementById(targetTab + 'Tab').classList.add('active');
            
            if (targetTab === 'analytics') {
                loadAnalytics();
            }
        });
    });
    
    // Add product button
    document.getElementById('addProductBtn').addEventListener('click', () => {
        document.getElementById('addProductModal').classList.add('show');
    });
    
    // Close modal
    document.getElementById('modalClose').addEventListener('click', () => {
        document.getElementById('addProductModal').classList.remove('show');
    });
    
    document.getElementById('cancelBtn').addEventListener('click', () => {
        document.getElementById('addProductModal').classList.remove('show');
    });
    
    // Form submit
    document.getElementById('productForm').addEventListener('submit', handleFormSubmit);
    
    // Dropzone
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    
    dropzone.addEventListener('click', () => fileInput.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

// Handle file preview
function handleFiles(files) {
    selectedFiles = Array.from(files); // Store the files
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    selectedFiles.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="preview-remove">&times;</button>
                `;
                div.querySelector('.preview-remove').addEventListener('click', () => {
                    div.remove();
                    // Remove from selectedFiles array
                    selectedFiles = selectedFiles.filter(f => f !== file);
                });
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
}


// Upload files to S3 and return URLs
async function uploadFilesToS3(files) {
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    uploadProgress.style.display = 'block';
    
    const urls = [];
    let uploaded = 0;
    
    for (const file of files) {
        const formData = new FormData();
        formData.append('file', file);
        
        const response = await fetch('/upload.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            urls.push(result.url);
        }
        
        uploaded++;
        const progress = (uploaded / files.length) * 100;
        progressFill.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
    }
    
    uploadProgress.style.display = 'none';
    return urls;
}

// Handle form submit
async function handleFormSubmit(e) {
    e.preventDefault();

    const fileInput = document.getElementById('fileInput');
    const files = fileInput.files;
    
    let thumbnail = 'https://via.placeholder.com/300';

    if (selectedFiles.length > 0) {
        const urls = await uploadFilesToS3(selectedFiles);
        if (urls.length > 0) {
            thumbnail = urls[0];
        }
    }

    if (files.length > 0) {
        const urls = await uploadFilesToS3(Array.from(files));
        if (urls.length > 0) {
            thumbnail = urls[0];
        }
    }
    
    const formData = {
        csrf_token: document.querySelector('[name="csrf_token"]').value,
        title: document.getElementById('productTitle').value,
        description: document.getElementById('productDescription').value,
        category: document.getElementById('productCategory').value,
        price: document.getElementById('productPrice').value,
        // thumbnail: 'https://via.placeholder.com/300'
        thumbnail: thumbnail
    };
    
    fetch('/api/api.php?path=products', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('addProductModal').classList.remove('show');
            document.getElementById('productForm').reset();
            document.getElementById('imagePreview').innerHTML = '';
            currentPage = 1;
            document.getElementById('productsGrid').innerHTML = '';
            loadProducts();
        }
    });
}

// analytics
function loadAnalytics() {
    const date = document.getElementById('analyticsDate').value;
    
    fetch(`/api/api.php?path=analytics&date=${date}`, { credentials: 'include'})
        .then(res => res.json())
        .then(data => {
            drawChart(data.data);
        });
}

// bar chart
function drawChart(data) {
    // debugger
    const canvas = document.getElementById('dauChart');
    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth * 0.8;
    canvas.height = 400;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (data.length === 0) {
        ctx.fillText('No data for this date', canvas.width / 2, canvas.height / 2);
        return;
    }

    // const sampledData = data.filter((_, i) => i % 5 === 0);
    const sampledData = data.filter((_, i) => i % 60 === 0);


    const maxDau = Math.max(...sampledData.map(d => d.dau));
    const barWidth = canvas.width / sampledData.length;
    const chartHeight = canvas.height - 60;

    sampledData.forEach((item, index) => {
        const barHeight = (item.dau / maxDau) * chartHeight;
        const x = index * barWidth;
        const y = canvas.height - barHeight - 40;
        
        ctx.fillStyle = '#3498db';
        ctx.fillRect(x + 2, y, barWidth - 4, barHeight);
        if (index % 5 === 0) {
            ctx.fillStyle = '#333';
            ctx.font = '10px sans-serif';
            const time = item.minute.substring(11, 16); // Extract HH:MM
            ctx.fillText(time, x, canvas.height - 10);
        }

    });

    
    // const maxDau = Math.max(...data.map(d => d.dau));
    // const barWidth = canvas.width / data.length;
    
    // data.forEach((item, index) => {
    //     const barHeight = (item.dau / maxDau) * (canvas.height - 40);
    //     const x = index * barWidth;
    //     const y = canvas.height - barHeight - 20;
        
    //     ctx.fillStyle = '#3498db';
    //     ctx.fillRect(x + 2, y, barWidth - 4, barHeight);
        
    //     ctx.fillStyle = '#333';
    //     ctx.font = '10px sans-serif';
    //     ctx.fillText(item.dau, x + barWidth / 2 - 5, y - 5);
    // });
}

// Analytics date change
document.getElementById('analyticsDate').addEventListener('change', loadAnalytics);