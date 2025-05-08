<?php
// includes/navbar.php
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/inventory_system">Inventory System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/products/">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/categories/">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/orders/">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/customers/">Customers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/inventory/">Inventory</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/campaigns/">Campaigns</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/inventory_system/modules/analytics/">Analytics</a>
                </li>
            </ul>
            <form class="d-flex position-relative">
                <input class="form-control" type="search" id="globalSearch" 
                       placeholder="Search products, orders, customers..." 
                       aria-label="Search" 
                       style="min-width: 300px;">
                <div id="searchResults" class="position-absolute top-100 start-0 mt-1 w-100 d-none">
                    <!-- Search results will be populated here -->
                </div>
            </form>
        </div>
    </div>
</nav>

<!-- Search Styles -->
<style>
#searchResults {
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1050;
}

.search-item {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-item:hover {
    background-color: #f8f9fa;
}

.search-item:last-child {
    border-bottom: none;
}

.search-item-title {
    font-weight: bold;
    margin-bottom: 2px;
    color: #333;
}

.search-item-subtitle {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 2px;
}

.search-item-details {
    font-size: 0.8em;
    color: #888;
}

.search-category {
    padding: 5px 15px;
    background-color: #f8f9fa;
    font-weight: bold;
    color: #666;
    border-bottom: 1px solid #ddd;
    font-size: 0.9em;
    text-transform: uppercase;
}

.search-loading {
    text-align: center;
    padding: 20px;
    color: #666;
}

.search-message {
    padding: 15px;
    text-align: center;
    color: #666;
}

.search-error {
    padding: 15px;
    text-align: center;
    color: #dc3545;
}

@media (max-width: 768px) {
    #globalSearch {
        min-width: 200px;
    }
    
    #searchResults {
        width: 100vw;
        left: 0;
        margin-left: -15px;
    }
}
</style>

<!-- Search Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    let currentSearchXHR;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        if (currentSearchXHR) {
            currentSearchXHR.abort();
        }

        if (this.value.length < 2) {
            searchResults.classList.add('d-none');
            return;
        }

        searchResults.classList.remove('d-none');
        searchResults.innerHTML = '<div class="search-loading">Searching...</div>';

        searchTimeout = setTimeout(() => {
            currentSearchXHR = new XMLHttpRequest();
            currentSearchXHR.open('GET', `/inventory_system/api/v1/search.php?q=${encodeURIComponent(this.value)}`);
            
            currentSearchXHR.onload = function() {
                if (currentSearchXHR.status === 200) {
                    try {
                        const data = JSON.parse(currentSearchXHR.responseText);
                        displaySearchResults(data);
                    } catch (e) {
                        showSearchError('Error processing results');
                    }
                } else {
                    showSearchError('Search failed');
                }
            };

            currentSearchXHR.onerror = function() {
                showSearchError('Network error occurred');
            };

            currentSearchXHR.send();
        }, 300);
    });

    function displaySearchResults(data) {
        searchResults.innerHTML = '';
        
        const categories = {
            products: 'Products',
            customers: 'Customers',
            orders: 'Orders',
            campaigns: 'Campaigns'
        };

        let hasResults = false;

        for (const [category, items] of Object.entries(data)) {
            if (items && items.length > 0) {
                hasResults = true;
                
                const categoryHeader = document.createElement('div');
                categoryHeader.className = 'search-category';
                categoryHeader.textContent = categories[category];
                searchResults.appendChild(categoryHeader);

                items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `
                        <div class="search-item-title">${escapeHtml(item.title)}</div>
                        <div class="search-item-subtitle">${escapeHtml(item.subtitle)}</div>
                        <div class="search-item-details">${escapeHtml(item.details)}</div>
                    `;
                    div.addEventListener('click', () => {
                        window.location.href = item.link;
                    });
                    searchResults.appendChild(div);
                });
            }
        }

        if (!hasResults) {
            searchResults.innerHTML = '<div class="search-message">No results found</div>';
        }
    }

    function showSearchError(message) {
        searchResults.innerHTML = `<div class="search-error">${escapeHtml(message)}</div>`;
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('d-none');
        }
    });

    // Prevent search results from closing when clicking inside
    searchResults.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = searchResults.getElementsByClassName('search-item');
        let currentIndex = -1;

        // Find currently focused item
        for (let i = 0; i < items.length; i++) {
            if (items[i].classList.contains('active')) {
                currentIndex = i;
                break;
            }
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (currentIndex < items.length - 1) {
                    if (currentIndex >= 0) items[currentIndex].classList.remove('active');
                    items[currentIndex + 1].classList.add('active');
                    items[currentIndex + 1].scrollIntoView({ block: 'nearest' });
                }
                break;

            case 'ArrowUp':
                e.preventDefault();
                if (currentIndex > 0) {
                    items[currentIndex].classList.remove('active');
                    items[currentIndex - 1].classList.add('active');
                    items[currentIndex - 1].scrollIntoView({ block: 'nearest' });
                }
                break;

            case 'Enter':
                e.preventDefault();
                if (currentIndex >= 0) {
                    items[currentIndex].click();
                }
                break;

            case 'Escape':
                searchResults.classList.add('d-none');
                searchInput.blur();
                break;
        }
    });
});
</script>