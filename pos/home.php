<!-- Low Stock Modal -->
<div class="modal fade" id="lowStockModal" tabindex="-1" aria-labelledby="lowStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="lowStockModalLabel">Low Stock Alert</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="lowStockModalBody">
                <!-- Low stock items will be listed here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="continueLowStock" style="display:none;">Continue Anyway</button>
            </div>
        </div>
    </div>
</div>

<!-- No Products Selected Modal -->
<div class="modal fade" id="noProductsModal" tabindex="-1" aria-labelledby="noProductsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #c67c4e; color: white; border-bottom: none;">
                <h5 class="modal-title" id="noProductsModalLabel">
                    <i class="fa fa-shopping-cart me-2"></i>No Products Selected
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="margin-bottom: 20px;">
                    <i class="fa fa-inbox" style="font-size: 4rem; color: #c67c4e; opacity: 0.8;"></i>
                </div>
                <h6 class="text-dark mb-3" style="font-weight: 600;">Please choose a product first</h6>
                <p class="text-muted mb-0">
                    <small>Select products from the menu to proceed with payment. You must have at least one item in your order.</small>
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                <button type="button" class="btn" style="color: #ffffff; background-color: #c67c4e; border: none; padding: 8px 20px; border-radius: 6px; font-weight: 600;" data-bs-dismiss="modal">
                    Got it!
                </button>
            </div>
        </div>
    </div>
</div>
<?php 
    include('../includes/config.php');
    //include('generate_receipt.php');
?>
    
<style>
    html, body, main {
        height: 100%;
    }

    main .card {
        height: calc(100%);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    main .card-body {
        height: auto;
        min-height: 500px;
        overflow: visible;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    #o-list {
        flex: 1;
        overflow: auto;
        margin-bottom: 10px;
    }

    #calc {
        flex-shrink: 0;
        width: 100%;
    }

    .prod-details {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-align: center;  /* Optional, centers the content inside */
    align-items: center; /* Centers the content horizontally */
  }
    .prod-item {
        min-height: 12vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        height: auto;
        cursor: pointer;
    }

    .prod-item .prod-image {
        margin-bottom: 5px;
    }

    .prod-image img {
    max-width: 100%;
    height: auto;
    object-fit: cover;
    margin-bottom: 10px;
}

    .prod-item .prod-name span {
        display: block;
        text-align: center;
        font-size: 14px;
    }

    .prod-item .prod-name .product-category-badge {
        display: block;
        font-size: 11px;
        font-weight: 700;
        background: linear-gradient(90deg, #3f2305 0%, #a95e2d 100%);
        color: #ffe6c7;
        padding: 5px 10px;
        border-radius: 8px;
        margin-top: 8px;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        box-shadow: 0 2px 8px rgba(63, 35, 5, 0.18);
        border: 1.5px solid #a95e2d;
        transition: all 0.2s ease;
    }

    .prod-item:hover .product-category-badge {
        background: linear-gradient(90deg, #a95e2d 0%, #3f2305 100%);
        box-shadow: 0 4px 12px rgba(63, 35, 5, 0.28);
        border-color: #c67c4e;
        color: #fff8ef;
        transform: scale(1.07);
    }

    .prod-item:hover {
        opacity: .8;
    }

    .prod-item .card-body {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #cat-list {
        width: 100%;
    }

    #cat-list.sidebar-mode {
        flex-direction: column;
        min-height: auto;
        justify-content: flex-start;
        align-items: stretch;
        padding: 0;
        background: transparent;
        border-right: none;
    }

    .cat-item {
        cursor: pointer;
        margin-bottom: 0;
    }

    #cat-list.sidebar-mode .cat-item button {
        background: linear-gradient(135deg, #c67c4e 0%, #a95e2d 100%) !important;
        color: #fff !important;
        border: none !important;
        width: 100% !important;
        height: 48px !important;
        min-height: 48px !important;
        max-height: 48px !important;
        padding: 0 14px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        margin-bottom: 2px !important;
        margin-right: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        transition: all 0.2s ease !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        text-align: center !important;
        line-height: 1 !important;
    }

    #cat-list.sidebar-mode .cat-item button i {
        display: inline-block;
        margin-right: 6px;
        font-size: 0.85rem;
    }

    #cat-list.sidebar-mode .cat-item button:hover {
        background: linear-gradient(135deg, #a95e2d 0%, #8b4513 100%) !important;
        transform: none !important;
        box-shadow: none !important;
    }

    #cat-list.sidebar-mode .cat-item button.active,
    #cat-list.sidebar-mode .cat-item button:focus {
        background: linear-gradient(135deg, #8b4513 0%, #6d3410 100%) !important;
        box-shadow: none !important;
    }

    #prod-list-container {
        display: none;
    }

    #prod-list-container.show {
        display: block;
    }

    .category-sidebar {
        width: 200px;
        float: left;
    }

    .products-main {
        margin-left: 220px;
    }
    
    .pagination .page-link {
        background-color: #3f2305;
        color: #fff;
        border: 1px solid #3f2305;
    }

    .pagination .page-link:hover,
    .pagination .page-item.active .page-link {
        background-color: #c67c4e;
        color: #fff;
        border-color: #c67c4e;
    }

    .pagination .page-item.disabled .page-link {
        background-color: #e0e0e0;
        color: #aaa;
        border-color: #e0e0e0;
    }

    /* Low stock visual treatment */
    .prod-item.low-stock-border {
        position: relative;
        border: 2px solid #d97706 !important; /* warm amber */
        border-radius: 14px !important;
        background: linear-gradient(135deg, #fff8ef 0%, #ffe0c2 55%, #ffd3a3 100%) !important;
        box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25), 0 2px 4px rgba(0,0,0,0.08) !important;
        overflow: hidden;
        animation: lowStockPulse 3s ease-in-out infinite;
    }

    /* Stripes overlay for subtle texture */
    .prod-item.low-stock-border::before {
        content: "";
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(45deg, rgba(217,119,6,0.12) 0 6px, rgba(255,255,255,0.15) 6px 12px);
        pointer-events: none;
        mix-blend-mode: multiply;
    }

    /* Badge */
    .prod-item.low-stock-border::after {
        content: "LOW STOCK";
        position: absolute;
        top: 8px;
        left: 8px;
        background: linear-gradient(90deg,#dc2626,#f97316);
        color: #fff;
        padding: 4px 8px;
        font-size: 0.60rem;
        font-weight: 700;
        letter-spacing: 0.6px;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        text-transform: uppercase;
        pointer-events: none;
    }

    @keyframes lowStockPulse {
        0% { box-shadow: 0 0 0 0 rgba(217,119,6,0.45); }
        60% { box-shadow: 0 0 0 10px rgba(217,119,6,0); }
        100% { box-shadow: 0 0 0 0 rgba(217,119,6,0); }
    }

    @media (max-width: 767px) {
    .prod-item {
        margin-bottom: 10px;
    }
  }

  .search-bar-container {
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #c67c4e;
    margin-bottom: 15px;
    border-radius: 8px;
  }

  .search-bar-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #c67c4e;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
  }

  .search-bar-input:focus {
    outline: none;
    border-color: #a95e2d;
    box-shadow: 0 0 0 3px rgba(198, 124, 78, 0.1);
  }

  .search-bar-input::placeholder {
    color: #adb5bd;
  }

  .search-results-count {
    margin-top: 8px;
    font-size: 0.9rem;
    color: #6c757d;
    text-align: center;
  }

  .no-search-results {
    padding: 30px;
    text-align: center;
    color: #6c757d;
  }

  .no-search-results i {
    font-size: 2rem;
    margin-bottom: 15px;
    opacity: 0.5;
  }

  /* Hierarchical Category Styles */
    #category-container {
        background: transparent;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
        border: none;
        width: 100%;
        flex: 0 0 auto;
        min-width: 0;
    }

    #category-container.sidebar-active {
        width: 200px;
        flex: 0 0 200px;
        min-width: 200px;
    }

    .pos-product-area {
        display: flex;
        gap: 16px;
        width: 100%;
        height: auto;
        overflow: visible;
        align-items: flex-start;
        justify-content: center;
    }

    #prod-list-container {
        flex: 1 1 auto;
        min-width: 0;
        display: none;
        flex-direction: column;
        overflow: hidden;
    }

    #prod-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        margin: 0;
        align-items: start;
    }
    
    #prod-list .prod-item {
        align-self: start;
    }

  #cat-list {
    max-width: 100%;
  }

  /* Initial card view layout */
    #cat-list.initial-view {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: auto auto;
        gap: 22px;
        padding: 30px 40px;
        align-items: stretch;
        max-width: 1000px;
        margin: 0 auto;
        width: 100%;
        height: auto;
    }

    @media (max-width: 992px) {
        #cat-list.initial-view {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        #cat-list.initial-view {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }

  #cat-list.initial-view .main-category-all {
    grid-column: 1 / -1;
  }

  #cat-list.initial-view .main-category-all,
  #cat-list.initial-view .category-group {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }

    #cat-list.initial-view .category-group-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f4f0 100%);
    border-radius: 18px;
    padding: 60px 35px;
    text-align: center;
    cursor: pointer;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 2px solid #e8ddd1;
    min-height: 220px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

    #cat-list.initial-view .main-category-all .category-group-card {
        min-height: 130px;
        height: 100%;
        padding: 35px 40px;
        background: linear-gradient(135deg, #c67c4e 0%, #a95e2d 100%);
        border: 2px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 6px 20px rgba(198, 124, 78, 0.3), 0 2px 6px rgba(0, 0, 0, 0.1);
    }

  #cat-list.initial-view .category-group-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(198, 124, 78, 0.05) 0%, rgba(169, 94, 45, 0.08) 100%);
    opacity: 0;
    transition: opacity 0.35s ease;
    pointer-events: none;
    border-radius: 16px;
  }

  #cat-list.initial-view .category-group-card:hover::before {
    opacity: 1;
  }

  #cat-list.initial-view .category-group-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15), 0 4px 8px rgba(0, 0, 0, 0.08);
    border-color: #c67c4e;
  }

  #cat-list.initial-view .main-category-all .category-group-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 32px rgba(198, 124, 78, 0.4), 0 4px 10px rgba(0, 0, 0, 0.15);
    background: linear-gradient(135deg, #d4895a 0%, #b86935 100%);
    border-color: rgba(255, 255, 255, 0.35);
  }

  #cat-list.initial-view .category-group-card:active {
    transform: translateY(-2px);
    transition: all 0.1s ease;
  }

  #cat-list.initial-view .category-group-card i.fa-coffee,
  #cat-list.initial-view .category-group-card i.fa-utensils,
  #cat-list.initial-view .category-group-card i.fa-plus-circle {
    font-size: 5rem;
    color: #c67c4e;
    margin-bottom: 22px;
    display: block;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
  }

  #cat-list.initial-view .main-category-all .category-group-card i.fa-bars {
    font-size: 2.8rem;
    color: white;
    margin-bottom: 0;
    display: inline-block;
    margin-right: 12px;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  }

  #cat-list.initial-view .category-group-card:hover i {
    transform: scale(1.1) translateY(-4px);
    color: #a95e2d;
  }

  #cat-list.initial-view .main-category-all .category-group-card:hover i {
    color: white;
    transform: scale(1.05);
  }

  #cat-list.initial-view .category-group-card .card-title {
    color: #3f2305;
    font-size: 1.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.8px;
    margin: 0;
    position: relative;
    z-index: 1;
    line-height: 1.2;
    transition: all 0.35s ease;
  }

  #cat-list.initial-view .main-category-all .category-group-card .card-title {
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    font-size: 1.6rem;
  }

  #cat-list.initial-view .category-group-card:hover .card-title {
    color: #c67c4e;
    letter-spacing: 2px;
  }

  #cat-list.initial-view .main-category-all .category-group-card:hover .card-title {
    color: white;
    letter-spacing: 2px;
  }

    /* In initial view, ALL is shown as a card (not as a button) */
    #cat-list.initial-view .main-category-all .cat-item {
        display: none;
    }

  #cat-list.initial-view .category-subcategories {
    display: none !important;
  }

  .category-group {
    margin-bottom: 0;
  }

  /* Expandable dropdown category header - visually distinct */
  .category-group-header {
    background: linear-gradient(135deg, #8b5a3c 0%, #6d4423 100%);
    color: white;
    padding: 0 14px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    margin-bottom: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    user-select: none;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    height: 48px;
    min-height: 48px;
    max-height: 48px;
    position: relative;
  }

  /* Subtle gradient overlay for depth */
  .category-group-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,0.08) 0%, transparent 100%);
    border-radius: 8px 8px 0 0;
    pointer-events: none;
  }

  .category-group-header span {
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 1;
  }

  .category-group-header span i {
    display: inline-block;
    font-size: 1rem;
    opacity: 0.9;
  }

  /* Chevron icon - clear indicator of expandability */
  .category-group-header > i {
    display: inline-block;
    font-size: 0.75rem;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0.8;
    position: relative;
    z-index: 1;
  }

  .category-group-header:hover {
    background: linear-gradient(135deg, #9d6847 0%, #7d5129 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.15);
  }

  .category-group-header:active {
    transform: translateY(0);
  }

  /* Active/opened dropdown state - bright and expanded */
  .category-group-header.active {
    background: linear-gradient(135deg, #c67c4e 0%, #a95e2d 100%);
    box-shadow: 0 3px 10px rgba(198, 124, 78, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.25);
    margin-bottom: 6px;
  }

  /* Rotate chevron when expanded */
  .category-group-header.active > i {
    transform: rotate(180deg);
    opacity: 1;
  }

  /* Pulse animation for active state */
  .category-group-header.active::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -4px;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #c67c4e, transparent);
    border-radius: 2px;
  }

  .category-subcategories {
    display: none;
    padding: 0;
    flex-direction: column;
    gap: 0;
    margin-bottom: 8px;
  }

  .category-subcategories.show {
    display: flex;
  }

  .subcategory-item {
    flex: 0 0 100%;
  }

  /* Normal category buttons - lighter, indented appearance */
  .subcategory-item button {
    width: 100% !important;
    height: 40px !important;
    min-height: 40px !important;
    max-height: 40px !important;
    min-width: auto !important;
    max-width: 100% !important;
    padding: 0 16px 0 28px !important;
    font-size: 0.78rem !important;
    margin: 0 0 4px 0 !important;
    border-radius: 7px !important;
    transition: all 0.2s ease !important;
    box-shadow: none !important;
    border: 1px solid rgba(198, 124, 78, 0.2) !important;
    line-height: 1 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    text-align: left !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    font-weight: 600 !important;
    background: linear-gradient(135deg, #f0dac7 0%, #e5cfbb 100%) !important;
    color: #6d4423 !important;
    position: relative !important;
  }

  /* Subtle left border indicator */
  .subcategory-item button::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 4px;
    background: #c67c4e;
    border-radius: 50%;
  }

  .subcategory-item button:hover {
    transform: translateX(3px) !important;
    background: linear-gradient(135deg, #ead5c1 0%, #ddc4ad 100%) !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    border-color: rgba(198, 124, 78, 0.3) !important;
  }

  /* Active subcategory button - highlighted */
  .subcategory-item button:active,
  .subcategory-item button.active {
    background: linear-gradient(135deg, #c67c4e 0%, #a95e2d 100%) !important;
    color: white !important;
    transform: translateX(3px) !important;
    box-shadow: 0 2px 6px rgba(198, 124, 78, 0.3) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    font-weight: 600 !important;
  }

  .subcategory-item button.active::before {
    background: white;
  }

  #cat-list.sidebar-mode {
    max-width: 100%;
    margin: 0;
    padding: 10px;
    height: fit-content;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
  }

  #category-container.sidebar-active {
    padding: 10px;
    min-height: auto;
    max-height: fit-content;
    display: block;
    align-items: flex-start;
  }

  #cat-list.sidebar-mode .category-group {
    margin-bottom: 6px;
  }

  #cat-list.sidebar-mode .subcategory-item {
    flex: 0 0 100%;
  }

  #cat-list.sidebar-mode .category-group-header {
    font-size: 0.8rem;
    padding: 0 12px;
    height: 46px;
    min-height: 46px;
    max-height: 46px;
  }

  #cat-list.sidebar-mode .subcategory-item button {
    font-size: 0.76rem !important;
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    padding: 0 12px 0 24px !important;
  }

  #cat-list.sidebar-mode ~ #prod-list-container {
    margin-left: 20px;
  }

  /* Smooth transitions for mode switching */
  #category-container,
  #cat-list,
  .category-group-card,
  .category-group-header {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Active state feedback */
  .cat-item button.active {
    background: linear-gradient(135deg, #8b4513 0%, #6d3410 100%) !important;
    box-shadow: none !important;
  }

  /* Card header for categories section */
  .categories-card-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #3f2305;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 3px solid #c67c4e;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .main-category-all {
    margin-bottom: 8px;
    padding-bottom: 0;
    border-bottom: none;
  }

  #cat-list.sidebar-mode .main-category-all .cat-item button {
    width: 100%;
    max-width: 100%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border-radius: 8px;
    height: 48px !important;
    min-height: 48px !important;
    padding: 12px 14px !important;
    font-size: 0.85rem !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    text-align: center !important;
    line-height: 1.3 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border: none !important;
  }

  #cat-list.sidebar-mode .main-category-all .cat-item button i {
    display: none;
  }

  #cat-list.sidebar-mode .main-category-all .cat-item button:hover {
    transform: none;
    box-shadow: none;
  }


</style>

<div class="container-fluid o-field">   
    <div class="row mt-3 ml-3 mr-3">
       <div class="col-lg-8 order-lg-1 p-field">
            <div class="card">
                <div class="card-header text-dark">
                    <b>Products</b>
                </div>
                <div class="card-body" id="product-list">
                    <div class="pos-product-area">
                        <div id="category-container">
                            <!-- Category -->
                            <div class="row justify-content-start align-items-center initial-view" id="cat-list">
                                <!-- ALL Card -->
                                <div class="col-12 main-category-all">
                                    <div class="category-group-card" data-group="all">
                                        <i class="fas fa-bars"></i>
                                        <div class="card-title">ALL</div>
                                    </div>
                                    <div class="cat-item" data-id="all" style="display: none;">
                                        <button class="btn btn-primary" style="width: 100% !important; max-width: 100% !important;">
                                            <i class="fas fa-bars me-2"></i><b class="text-white">ALL</b>
                                        </button>
                                    </div>
                                </div>

                                <?php
                                try {
                                    include'../includes/config.php';
                                    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    $query = "SELECT id, name, type FROM categories ORDER BY type ASC, name ASC";
                                    $stmt = $pdo->query($query);
                                    
                                    // Organize categories into groups based on type from database
                                    $drinks = [];
                                    $meals = [];
                                    $extras = [];
                                    
                                    if ($stmt->rowCount() > 0) {
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $type = strtolower(trim($row['type'] ?? ''));
                                            
                                            // Group by type field from database
                                            if ($type === 'drinks' || $type === 'drink') {
                                                $drinks[] = $row;
                                            }
                                            elseif ($type === 'food' || $type === 'meal' || $type === 'meals') {
                                                $meals[] = $row;
                                            }
                                            elseif ($type === 'extras' || $type === 'add-ons' || $type === 'addons') {
                                                $extras[] = $row;
                                            }
                                        }
                                ?>
                                
                                <!-- DRINKS Category Group -->
                                <div class="col-12 category-group" data-group-type="drinks">
                                    <div class="category-group-card" data-group="drinks">
                                        <i class="fas fa-coffee"></i>
                                        <div class="card-title">DRINKS</div>
                                    </div>
                                    <div class="category-group-header" data-group="drinks" style="display: none;">
                                        <span><i class="fas fa-coffee me-2"></i>DRINKS</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="category-subcategories" id="drinks-subcategories">
                                        <?php
                                        foreach ($drinks as $cat) {
                                            echo '<div class="subcategory-item cat-item" data-id="' . htmlspecialchars($cat['id']) . '">';
                                            echo '<button class="btn btn-primary">' . htmlspecialchars(ucwords($cat['name'])) . '</button>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- MEALS Category Group -->
                                <div class="col-12 category-group" data-group-type="meals">
                                    <div class="category-group-card" data-group="meals">
                                        <i class="fas fa-utensils"></i>
                                        <div class="card-title">MEALS</div>
                                    </div>
                                    <div class="category-group-header" data-group="meals" style="display: none;">
                                        <span><i class="fas fa-utensils me-2"></i>MEALS</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="category-subcategories" id="meals-subcategories">
                                        <?php
                                        foreach ($meals as $cat) {
                                            echo '<div class="subcategory-item cat-item" data-id="' . htmlspecialchars($cat['id']) . '">';
                                            echo '<button class="btn btn-primary">' . htmlspecialchars(ucwords($cat['name'])) . '</button>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- EXTRAS Category Group -->
                                <?php if (count($extras) > 0): ?>
                                <div class="col-12 category-group" data-group-type="extras">
                                    <div class="category-group-card" data-group="extras">
                                        <i class="fas fa-plus-circle"></i>
                                        <div class="card-title">EXTRAS</div>
                                    </div>
                                    <div class="category-group-header" data-group="extras" style="display: none;">
                                        <span><i class="fas fa-plus-circle me-2"></i>EXTRAS</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="category-subcategories" id="extras-subcategories">
                                        <?php
                                        foreach ($extras as $cat) {
                                            echo '<div class="subcategory-item cat-item" data-id="' . htmlspecialchars($cat['id']) . '">';
                                            echo '<button class="btn btn-primary">' . htmlspecialchars(ucwords($cat['name'])) . '</button>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php
                                    } else {
                                        echo '<div class="alert alert-warning">No categories found.</div>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger">Error fetching categories: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        <div id="prod-list-container" style="flex: 1;">
                            <div class="search-bar-container">
                                <input type="text" id="productSearchInput" class="search-bar-input" placeholder="Search products by name...">
                                <div class="search-results-count" id="searchResultsCount"></div>
                            </div>
                            <div class="row" id="prod-list">
                                    <!-- product cards will be rendered here -->
                                </div>
                                <div class="d-flex justify-content-center mt-2">
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="product-pagination"></ul>
                                    </nav>
                                </div>

                        </div>
                    </div>
                </div>
                </div>
                <!-- Footer with Payment Options -->
                <!-- <div class="card-footer">
                    <div class="row justify-content-center">
                        <div class="btn btn-sm col-sm-3 btn-primary mx-4" style="color: #ffffff; background-color:#3f2305; border:none;" type="button" id="other_payment">Other Payment</div>
                        <div class="btn btn-sm col-sm-3 btn-primary" style="color: #ffffff; background-color:#3f2305; border:none;" type="button" id="pay">Cash</div>
                        <div class="btn btn-sm col-sm-3 btn-primary mx-4" style="color: #ffffff; background-color:#3f2305; border:none;" type="button" id="save_order">Pay later</div>
                       
                    </div>
                </div> -->
            </div>              
        <!-- </div> -->


         <div class="col-lg-4 order-lg-2">
           <div class="card">
           <div class="card-header text-dark d-flex justify-content-between align-items-center">
            <b>Order List</b>          
                <div>                   
                <a class="btn btn-primary btn-sm" style="color: #ffffff; background-color:#3f2305; border:none;" 
                        href="<?php 
                                if ($_SESSION['role'] === 'admin') {
                                    echo '../index.php'; // Admin dashboard
                                } elseif ($_SESSION['role'] === 'cashier') {
                                    echo '../pos/index.php'; // Cashier POS page
                                } else {
                                    echo '../login.php'; // Default to login page if role is undefined
                                }
                            ?>">
                            <i class="fa fa-home"></i> Home
                        </a>
                    <a class="btn btn-primary btn-sm" style="color: #ffffff; background-color:#c67c4e; border:none;" href="../orders.php">
                        <i class="fa fa-list"></i> Orders
                    </a>    

                </div>                  
                 
                              
            </div>

           <div class="card-body">
            <form action="" id="manage-order">
            <input type="hidden" name="id" id="order_id" value="<?php echo isset($_GET['id']) ? $_GET['id'] : '' ?>">
            <input type="hidden" name="order_type" id="order_type" value="">
                <div class="bg-white" id='o-list'>
                   <table class="table bg-light mb-5" style="table-layout: fixed; width: 100%;"   >
                   <colgroup>
                      <col width="20%">
                      <col width="30%">
                      <col width="15%">
                      <col width="20%">
                      <col width="10%">
                  </colgroup>
                       <thead>
                           <tr>
                            <th>QTY</th>
                            <th>Order</th>
                            <th>Price</th>
                            <th>Amount</th>
                            <th></th>
                           </tr>
                       </thead>
                       <tbody>
                       <?php 
                                if(isset($items) && is_object($items)):
                                    while($row=$items->fetch_assoc()):
                            ?>
                            <tr>
                              <td class="qty" style="text-align: left;"><?php echo htmlspecialchars($row['qty']); ?></td>
                              <td><?php echo htmlspecialchars($row['order']); ?></td>
                              <td class="price" style="text-align: right;"><?php echo htmlspecialchars($row['price']); ?></td>
                              <td class="amount" style="text-align: right;"><?php echo htmlspecialchars($row['amount']); ?></td>
                              <td><button class="btn btn-danger btn-sm remove-item">Remove</button></td>
                          </tr>
                            <?php 
                                    endwhile;
                                endif;
                            ?>
                        </tbody>
                   </table>
                </div>
                   <div class="d-block bg-white mb-2" style="flex-shrink: 0;">
                       <label for="order_notes" class="text-dark"><b>Order Notes:</b></label>
                       <textarea id="order_notes" name="order_notes" class="form-control" rows="2" placeholder="Add special instructions or notes..."></textarea>
                   </div>
                   <div class="d-block bg-white mb-2" id="calc" style="flex-shrink: 0;">
                       <table class="" width="100%" style="table-layout: fixed;">
                           <tbody>
                                <tr>
                                <td style="text-align: left;"><b><h6>Total</h6></b></td>
                                <td style="text-align: right;">
                                    <input type="hidden" name="total_amount" value="0">
                                    <input type="hidden" name="total_tendered" value="0">
                                    <span><h6><b id="total_amount">0.00</b></h6></span>
                                </td>
                               </tr>
                           </tbody>
                       </table>
                   </div>
                   <div class="d-block bg-white" style="flex-shrink: 0;">
                       <div class="d-flex justify-content-center gap-2 flex-wrap">
                           <button class="btn btn-sm flex-fill" 
                               style="color: #ffffff; background-color:#3f2305; border:none; max-width: 120px;"
                               type="button" id="other_payment">
                               <i class="fas fa-credit-card me-1"></i>Other Payment
                           </button>
                           <button class="btn btn-sm flex-fill" 
                               style="color: #ffffff; background-color:#c67c4e; border:none; max-width: 120px;"
                               type="button" id="pay">
                               <i class="fas fa-money-bill-wave me-1"></i>Cash
                           </button>
                           <button class="btn btn-sm flex-fill" 
                               style="color: #ffffff; background-color:#3f2305; border:none; max-width: 120px;"
                               type="button" id="save_order">
                               <i class="far fa-clock me-1"></i>Pay Later
                           </button>
                       </div>
                   </div>
            </form>
        </div>
        
            <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
            </div>
        </div>
    </div>                           
       <!-- Order Type Selection Modal -->
        <div class="modal fade" id="orderTypeModal" tabindex="-1" role="dialog" aria-labelledby="orderTypeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #c67c4e; color: white;">
                        <h5 class="modal-title" id="orderTypeModalLabel"><i class="fa fa-clipboard-list me-2"></i>Select Order Type</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-5">
                        <p class="mb-4" style="font-size: 1.1rem; color: #3f2305;"><strong>Please choose how the customer will receive their order:</strong></p>
                        <div class="row justify-content-center gap-4">
                            <div class="col-5">
                                <button type="button" class="btn btn-lg w-100 py-4" style="background: linear-gradient(135deg, #c67c4e 0%, #a95e2d 100%); color: white; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;" id="selectDineIn" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">
                                    <i class="fa fa-utensils mb-3 d-block" style="font-size: 3rem;"></i>
                                    <div style="font-size: 1.3rem; font-weight: bold;">Dine-in</div>
                                    <small class="d-block mt-2" style="opacity: 0.9;">Customer will eat here</small>
                                </button>
                            </div>
                            <div class="col-5">
                                <button type="button" class="btn btn-lg w-100 py-4" style="background: linear-gradient(135deg, #3f2305 0%, #2a1803 100%); color: white; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;" id="selectTakeOut" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">
                                    <i class="fa fa-shopping-bag mb-3 d-block" style="font-size: 3rem;"></i>
                                    <div style="font-size: 1.3rem; font-weight: bold;">Take-out</div>
                                    <small class="d-block mt-2" style="opacity: 0.9;">Customer will take to go</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       <!-- Cash Payment Modal -->
        <div class="modal fade" id="cashPaymentModal" tabindex="-1" role="dialog" aria-labelledby="cashPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashPaymentModalLabel">Cash Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>                       
                    </div>
                    <div class="modal-body">
                        <form id="cashPaymentForm">
                            <div class="form-group mb-3">
                                <label for="totalAmount">Total Amount</label>
                                <input type="text" class="form-control" id="totalAmount" readonly>
                            </div>
                            <div class="form-group mb-3">
                                <label for="cashReceived">Cash Received</label>
                                <input type="number" class="form-control" id="cashReceived" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="changeDue">Change Due</label>
                                <input type="text" class="form-control" id="changeDue" readonly>
                            </div>
                            <div class="form-group ">
                                <div class="d-flex flex-wrap justify-content-between">
                                <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="100">P100</button>
                                <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="150">P150</button>
                                <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="200">P200</button>
                                <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="250">P250</button>
                                <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="500">P500</button>
                                <button type="button" class="btn btn-outline-primary quick-cash m-1" data-amount="1000">P1000</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" style="color: #ffffff; background-color:#3f2305; border:none;" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" style="color: #ffffff; background-color:#c67c4e; border:none;" id="processPayment">Process Payment</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PayMongo Payment Modal -->
        <div class="modal fade" id="paymongoPaymentModal" tabindex="-1" role="dialog" aria-labelledby="paymongoPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; border: none; padding: 25px 30px;">
                        <div>
                            <h5 class="modal-title mb-1" id="paymongoPaymentModalLabel" style="font-weight: 600; font-size: 1.4rem;">
                                <i class="fas fa-shield-alt me-2"></i>Secure Payment
                            </h5>
                            <p class="mb-0" style="font-size: 0.85rem; opacity: 0.9;">Powered by PayMongo</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="padding: 30px;">
                        <!-- Loading State -->
                        <div id="paymongo-loading" style="display: none; text-align: center; padding: 40px 20px;">
                            <div class="spinner-border" style="width: 3rem; height: 3rem; color: #667eea;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3" style="color: #6c757d; font-size: 1.1rem;">Processing your payment...</p>
                            <p class="text-muted small">Please wait, do not close this window</p>
                        </div>
                        
                        <!-- Payment Content -->
                        <div id="paymongo-content">
                            <!-- Amount Display -->
                            <div class="mb-4 p-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 15px; text-align: center;">
                                <p class="mb-2 text-muted" style="font-size: 0.9rem; font-weight: 500;">AMOUNT TO PAY</p>
                                <h2 class="mb-0" style="font-weight: 700; color: #2d3748;">₱<span id="paymongoTotalAmount">0.00</span></h2>
                            </div>
                            
                            <!-- Payment Method Selection -->
                            <div class="mb-4">
                                <label class="form-label" style="font-weight: 600; color: #2d3748; margin-bottom: 15px;">
                                    <i class="fas fa-credit-card me-2"></i>Choose Payment Method
                                </label>
                                <div class="row g-3">
                                    <!-- Card Option -->
                                    <div class="col-12">
                                        <input type="radio" class="btn-check" name="paymentMethod" id="paymentCard" value="card" autocomplete="off" checked>
                                        <label class="btn w-100 text-start" for="paymentCard" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; transition: all 0.3s; background: white;">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-credit-card" style="color: white; font-size: 1.3rem;"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div style="font-weight: 600; color: #2d3748; font-size: 1.05rem;">Credit / Debit Card</div>
                                                    <div class="text-muted" style="font-size: 0.85rem;">Visa, Mastercard, JCB</div>
                                                </div>
                                                <i class="fas fa-chevron-right text-muted"></i>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- GCash Option -->
                                    <div class="col-12">
                                        <input type="radio" class="btn-check" name="paymentMethod" id="paymentGcash" value="gcash" autocomplete="off">
                                        <label class="btn w-100 text-start" for="paymentGcash" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; transition: all 0.3s; background: white;">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 50px; height: 50px; background: #007DFF; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-mobile-alt" style="color: white; font-size: 1.3rem;"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div style="font-weight: 600; color: #2d3748; font-size: 1.05rem;">GCash</div>
                                                    <div class="text-muted" style="font-size: 0.85rem;">Pay via GCash wallet</div>
                                                </div>
                                                <i class="fas fa-chevron-right text-muted"></i>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- GrabPay Option -->
                                    <div class="col-12">
                                        <input type="radio" class="btn-check" name="paymentMethod" id="paymentGrabpay" value="grab_pay" autocomplete="off">
                                        <label class="btn w-100 text-start" for="paymentGrabpay" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; transition: all 0.3s; background: white;">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 50px; height: 50px; background: #00B14F; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-wallet" style="color: white; font-size: 1.3rem;"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div style="font-weight: 600; color: #2d3748; font-size: 1.05rem;">GrabPay</div>
                                                    <div class="text-muted" style="font-size: 0.85rem;">Pay via GrabPay wallet</div>
                                                </div>
                                                <i class="fas fa-chevron-right text-muted"></i>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Payment Form -->
                            <div id="cardPaymentForm" class="payment-method-form">
                                <div class="card" style="border: 2px solid #e2e8f0; border-radius: 15px; padding: 25px; background: #f8fafc;">
                                    <div class="mb-3">
                                        <label for="cardNumber" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">
                                            <i class="fas fa-credit-card me-2"></i>CARD NUMBER
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem; letter-spacing: 1px;">
                                        </div>
                                        <small class="text-muted mt-1 d-block">
                                            <i class="fas fa-info-circle me-1"></i>Test: 4123450131001381
                                        </small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="cardExpMonth" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">MONTH</label>
                                            <input type="text" class="form-control" id="cardExpMonth" placeholder="MM" maxlength="2" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem;">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cardExpYear" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">YEAR</label>
                                            <input type="text" class="form-control" id="cardExpYear" placeholder="YY" maxlength="2" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem;">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cardCvc" class="form-label" style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">
                                                CVC
                                                <i class="fas fa-question-circle ms-1" data-bs-toggle="tooltip" title="3 or 4 digits on the back of your card"></i>
                                            </label>
                                            <input type="text" class="form-control" id="cardCvc" placeholder="123" maxlength="4" style="border: 1px solid #cbd5e0; border-radius: 8px; padding: 14px; font-size: 1.05rem;">
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3" style="background: #edf2f7; border-radius: 8px; border-left: 4px solid #667eea;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-lock me-2" style="color: #667eea;"></i>
                                            <small style="color: #4a5568;">Your payment information is encrypted and secure</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- E-Wallet Payment Info -->
                            <div id="ewalletPaymentForm" class="payment-method-form" style="display: none;">
                                <div class="card" style="border: 2px solid #e2e8f0; border-radius: 15px; padding: 25px; background: #f8fafc;">
                                    <div class="text-center mb-3">
                                        <div class="mb-3">
                                            <i class="fas fa-mobile-screen-button" style="font-size: 3rem; color: #667eea;"></i>
                                        </div>
                                        <h5 style="color: #2d3748; font-weight: 600;">E-Wallet Payment</h5>
                                        <p class="text-muted mb-0">You'll be redirected to complete your payment</p>
                                    </div>
                                    <div class="alert" style="background: #ebf8ff; border: 1px solid #bee3f8; border-radius: 10px; color: #2c5282;">
                                        <div class="d-flex">
                                            <i class="fas fa-info-circle me-3 mt-1" style="font-size: 1.2rem;"></i>
                                            <div>
                                                <strong>What happens next:</strong>
                                                <ol class="mb-0 mt-2 ps-3" style="font-size: 0.9rem;">
                                                    <li>A secure payment window will open</li>
                                                    <li>Log in to your GCash or GrabPay account</li>
                                                    <li>Confirm the payment amount</li>
                                                    <li>You'll be redirected back automatically</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3" style="background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-shield-check me-2" style="color: #10b981; font-size: 1.2rem;"></i>
                                            <small style="color: #065f46;">Secure transaction powered by PayMongo</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Error Alert -->
                        <div id="paymongo-error" class="alert alert-danger mt-3" style="display: none; border-radius: 12px; border: none; background: #fee; color: #c53030;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="paymongo-error-message"></span>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 20px 30px; background: #fafafa;">
                        <button type="button" class="btn" style="color: #4a5568; background-color: #e2e8f0; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600;" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn" style="color: #ffffff; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 35px; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);" id="processPaymongoPayment">
                            <i class="fas fa-lock me-2"></i>Pay Securely
                        </button>
                    </div>
                </div>
            </div>
        </div>

       <style>
        /* Toast notification styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        }

        .toast-notification {
            background: white;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast-notification.success {
            border-left: 4px solid #10b981;
            background: #f0fdf4;
        }

        .toast-notification.success .toast-icon {
            color: #10b981;
        }

        .toast-notification.error {
            border-left: 4px solid #ef4444;
            background: #fef2f2;
        }

        .toast-notification.error .toast-icon {
            color: #ef4444;
        }

        .toast-notification.warning {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
        }

        .toast-notification.warning .toast-icon {
            color: #f59e0b;
        }

        .toast-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .toast-content {
            flex-grow: 1;
        }

        .toast-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }

        .toast-message {
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .toast-notification.hide {
            animation: slideOutRight 0.3s ease-in forwards;
        }

        /* Payment method button hover effects */
        input[name="paymentMethod"]:checked + label {
            border-color: #667eea !important;
            background: linear-gradient(135deg, #f0f4ff 0%, #e9efff 100%) !important;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2) !important;
        }
        
        input[name="paymentMethod"] + label:hover {
            border-color: #667eea !important;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15) !important;
            transform: translateY(-2px);
        }
        
        input[name="paymentMethod"] + label {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        /* Card input focus effects */
        #cardPaymentForm input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }
        
        /* Animation for error */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s;
        }
       </style>

       <script>
// Helper function to get local datetime string in ISO format
function getLocalDateTimeString() {
    const now = new Date();
    // Get local timezone offset in minutes and convert to milliseconds
    const tzOffset = now.getTimezoneOffset() * 60000;
    // Adjust the date to local time
    const localDateTime = new Date(now.getTime() - tzOffset);
    // Return ISO string format but in local time
    return localDateTime.toISOString().slice(0, 19).replace('T', ' ');
}

// Toast notification function for real-time feedback
function showToast(type = 'success', title = '', message = '') {
    // Ensure toast container exists
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Determine icon based on type
    let icon = '';
    if (type === 'success') {
        icon = '<i class="fas fa-check-circle toast-icon"></i>';
    } else if (type === 'error') {
        icon = '<i class="fas fa-exclamation-circle toast-icon"></i>';
    } else if (type === 'warning') {
        icon = '<i class="fas fa-exclamation-triangle toast-icon"></i>';
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        ${icon}
        <div class="toast-content">
            ${title ? `<div class="toast-title">${title}</div>` : ''}
            ${message ? `<div class="toast-message">${message}</div>` : ''}
        </div>
    `;
    
    container.appendChild(toast);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Low stock polling
    function checkLowStock() {
        fetch('../check_low_stock.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.low_stock && data.low_stock.length > 0) {
                    let html = '<ul style="padding-left:18px;">';
                    data.low_stock.forEach(item => {
                        html += `<li><b>${item.name}</b>: ${item.quantity} ${item.unit}</li>`;
                    });
                    html += '</ul>';
                    document.getElementById('lowStockModalBody').innerHTML =
                        '<div class="alert alert-danger">The following ingredients are low on stock:</div>' + html;
                    // Show Continue button if any ingredient has quantity > 0
                    let canContinue = data.low_stock.some(item => parseFloat(item.quantity) > 0);
                    if (canContinue) {
                        document.getElementById('continueLowStock').style.display = '';
                    } else {
                        document.getElementById('continueLowStock').style.display = 'none';
                    }
                    if (!$('#lowStockModal').hasClass('show')) {
                        $('#lowStockModal').modal('show');
                    }
                    // Optional: Add handler for continue button
                    document.getElementById('continueLowStock').onclick = function() {
                        $('#lowStockModal').modal('hide');
                    };
                }
            });
    }
    // setInterval(checkLowStock, 30000); // Optionally keep for background polling, but not on load
    // --- Product Pagination Variables ---
    const PRODUCTS_PER_PAGE = 18;
    let allProducts = [];
    let filteredProducts = [];
    let currentPage = 1;

    // Fetch all products from PHP (already rendered in the DOM)
    <?php
        $productsArr = [];
        // Join with categories to get category_type for each product - only fetch active products
        $prod = $pdo->query("SELECT p.*, c.type AS category_type, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.product_name ASC");
        if ($prod && $prod->rowCount() > 0) {
            foreach ($prod as $row) {
                $productsArr[] = [
                    'id' => $row['id'],
                    'product_name' => $row['product_name'],
                    'options' => $row['options'],
                    'category_id' => $row['category_id'],
                    'price' => $row['price'],
                    'image' => '../' . $row['image'],
                    'category_type' => $row['category_type'],
                    'category_name' => strtolower(trim($row['category_name'])),
                ];
            }
        }
    ?>
    allProducts = <?php echo json_encode($productsArr); ?>;
    
    // Sort products: Drink categories first, then Add Ons, then Meals/Food last
    // Within drinks group, all products sorted alphabetically (ignoring category grouping)
    allProducts.sort((a, b) => {
        const getCategoryOrder = (cat) => {
            cat = cat.toLowerCase().trim();
            // Drink categories
            if (cat === 'coffee' || cat === 'coffee blended' || cat === 'fruit tea/soda' || 
                cat === 'fruit tea' || cat === 'soda' || cat === 'ice blended' || 
                cat === 'milk based' || cat === 'milk base' || cat === 'milk tea' || 
                cat === 'yogurt series' || cat === 'yogurt' || cat === 'drinks') {
                return 1; // All drinks
            }
            if (cat === 'add ons' || cat === 'addons') return 2;
            return 3; // Meals, Food, Burgers, etc. (last)
        };
        
        const catOrderA = getCategoryOrder(a.category_name);
        const catOrderB = getCategoryOrder(b.category_name);
        
        if (catOrderA !== catOrderB) {
            return catOrderA - catOrderB;
        }
        
        // Within same group (drinks, add-ons, or meals), sort by product name alphabetically only
        return a.product_name.localeCompare(b.product_name);
    });
    
    filteredProducts = allProducts.slice();

    const prodList = document.getElementById('prod-list');
    const pagination = document.getElementById('product-pagination');

    // --- Product Pagination Functions ---
    function renderProducts(page = 1) {
        prodList.innerHTML = '';
        let start = (page - 1) * PRODUCTS_PER_PAGE;
        let end = start + PRODUCTS_PER_PAGE;
        let productsToShow = filteredProducts.slice(start, end);

        if (productsToShow.length === 0) {
            prodList.innerHTML = '<div class="alert alert-warning">No products available.</div>';
            return;
        }

        productsToShow.forEach(row => {
            let col = document.createElement('div');
            col.className = "col-6 col-sm-4 col-md-3 col-lg-2 mb-2 prod-item";
            col.setAttribute('data-category-id', row.category_id);
            col.setAttribute('data-product-name', row.product_name);
            col.setAttribute('data-product-id', row.id);
            col.setAttribute('data-product-price', row.price);

            // Show category badge only if 'ALL' category is selected
            const activeCategoryBtn = document.querySelector('.cat-item button.active');
            const activeCategoryId = activeCategoryBtn ? activeCategoryBtn.parentElement.getAttribute('data-id') : 'all';
            col.innerHTML = `
                <div class="prod-details">
                    <div class="prod-image">
                        <img src="${row.image}" class="rounded" alt="Product Image">
                    </div>
                    <div class="prod-name">
                        <span><strong>${row.product_name}</strong></span>
                        ${activeCategoryId === 'all' ? `<span class="product-category-badge">${row.category_name}</span>` : ''}
                    </div>
                </div>
            `;
            prodList.appendChild(col);

            // Check if this product has low stock warning (can't make 10 products)
            fetch(`check_product_stock.php?product_id=${row.id}`)
                .then(r => r.json())
                .then(prodData => {
                    if (prodData.lowStockWarning && prodData.lowStockWarning.length > 0) {
                        col.classList.add('low-stock-border');
                    }
                })
                .catch(err => {
                    console.error(`Error checking stock for product ${row.id}:`, err);
                });
        });

        // Re-bind product click events
        setTimeout(bindProductClicks, 300);
    }

    function renderPagination() {
        pagination.innerHTML = '';
        let totalPages = Math.ceil(filteredProducts.length / PRODUCTS_PER_PAGE);
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            let li = document.createElement('li');
            li.className = 'page-item' + (i === currentPage ? ' active' : '');
            let a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = i;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = i;
                renderProducts(currentPage);
                renderPagination();
            });
            li.appendChild(a);
            pagination.appendChild(li);
        }
    }

    // --- Category Group Card Click Handlers (Initial View) ---
    const categoryGroupCards = document.querySelectorAll('.category-group-card');
    categoryGroupCards.forEach(card => {
        card.addEventListener('click', function() {
            const group = this.getAttribute('data-group');
            const catList = document.getElementById('cat-list');
            const categoryContainer = document.getElementById('category-container');
            const prodListContainer = document.getElementById('prod-list-container');

            // Switch from initial card view to sidebar dropdown view
            catList.classList.remove('initial-view');
            catList.classList.add('sidebar-mode');
            catList.classList.remove('row', 'justify-content-center');
            categoryContainer.classList.add('sidebar-active');
            categoryContainer.style.width = '200px';
            categoryContainer.style.flexShrink = '0';

            // Hide all category cards, show dropdown headers
            document.querySelectorAll('.category-group-card').forEach(c => c.style.display = 'none');
            document.querySelectorAll('.category-group-header').forEach(h => h.style.display = 'flex');

            // Always show ALL button in sidebar mode
            const allBtn = document.querySelector(`.cat-item[data-id="all"] button`);
            if (allBtn) {
                allBtn.parentElement.style.display = 'block';
            }

            // Show products container
            prodListContainer.style.display = 'flex';

            // Reset dropdowns
            document.querySelectorAll('.category-group-header').forEach(h => h.classList.remove('active'));
            document.querySelectorAll('.category-subcategories').forEach(s => s.classList.remove('show'));

            // Clear active state
            document.querySelectorAll('.cat-item button').forEach(btn => btn.classList.remove('active'));

            // Handle ALL
            if (group === 'all') {
                filteredProducts = allProducts.slice();
                if (allBtn) {
                    allBtn.classList.add('active');
                }
            } else {
                // For Drinks, Meals, or Extras, expand their dropdown
                const header = document.querySelector(`.category-group-header[data-group="${group}"]`);
                const subcategories = document.getElementById(`${group}-subcategories`);

                if (header && subcategories) {
                    header.classList.add('active');
                    subcategories.classList.add('show');
                }

                // Don't filter yet, wait for subcategory selection
            }

            currentPage = 1;
            renderProducts(currentPage);
            renderPagination();
        });
    });

    // --- Dropdown Toggle for Category Groups ---
    const categoryGroupHeaders = document.querySelectorAll('.category-group-header');
    categoryGroupHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const group = this.getAttribute('data-group');
            const subcategories = document.getElementById(`${group}-subcategories`);
            
            // Toggle the dropdown
            this.classList.toggle('active');
            subcategories.classList.toggle('show');
        });
    });

    // --- Search Functionality ---
    const searchInput = document.getElementById('productSearchInput');
    const searchResultsCount = document.getElementById('searchResultsCount');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            // Reset to all products or filtered by category
            const categoryId = document.querySelector('.cat-item button.active')?.parentElement.getAttribute('data-id');
            if (categoryId && categoryId !== 'all') {
                filteredProducts = allProducts.filter(product => product.category_id == categoryId);
            } else {
                filteredProducts = allProducts.slice();
            }
            searchResultsCount.innerHTML = '';
        } else {
            // Filter products by search term
            filteredProducts = allProducts.filter(product => 
                product.product_name.toLowerCase().includes(searchTerm)
            );
            searchResultsCount.innerHTML = `Found ${filteredProducts.length} product${filteredProducts.length !== 1 ? 's' : ''}`;
        }
        
        currentPage = 1;
        renderProducts(currentPage);
        renderPagination();
    });

    // --- Category Filter ---
    const allCategoryButtons = document.querySelectorAll('.cat-item button');
    allCategoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.parentElement.getAttribute('data-id');
            
            // Switch to sidebar mode
            const catList = document.getElementById('cat-list');
            const categoryContainer = document.getElementById('category-container');
            const prodListContainer = document.getElementById('prod-list-container');
            
            // Switch from initial card view to sidebar if needed
            if (catList.classList.contains('initial-view')) {
                catList.classList.remove('initial-view');
                catList.classList.add('sidebar-mode');
                categoryContainer.classList.add('sidebar-active');
                
                // Hide all category cards, show dropdown headers
                document.querySelectorAll('.category-group-card').forEach(c => c.style.display = 'none');
                document.querySelectorAll('.category-group-header').forEach(h => h.style.display = 'flex');
                
                // Show standalone category buttons (like EXTRAS)
                document.querySelectorAll('.category-group .cat-item').forEach(item => {
                    if (item.getAttribute('data-id')) {
                        item.style.display = 'block';
                    }
                });
            }
            
            // Remove large-mode from all categories
            document.querySelectorAll('.cat-item').forEach(item => {
                item.classList.remove('large-mode');
            });
            
            // Add sidebar mode
            catList.classList.add('sidebar-mode');
            catList.classList.remove('row', 'justify-content-center');
            categoryContainer.style.width = '140px';
            categoryContainer.style.flexShrink = '0';
            
            // Show products container
            prodListContainer.style.display = 'flex';
            
            // Mark active category
            allCategoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            if (categoryId === 'all') {
                filteredProducts = allProducts.slice();
            } else {
                filteredProducts = allProducts.filter(product => product.category_id == categoryId);
            }
            currentPage = 1;
            renderProducts(currentPage);
            renderPagination();
        });
    });

    // --- Product Click Handler with Drink Size Modal ---
    // Initialize orderItems from localStorage to persist across page refreshes
    let orderItems = [];
    let inventoryAlreadyDeducted = false; // Track if inventory was already deducted for saved items
    try {
        const savedOrder = localStorage.getItem('currentOrder');
        if (savedOrder) {
            orderItems = JSON.parse(savedOrder);
            inventoryAlreadyDeducted = true; // Items restored from localStorage already have inventory deducted
            console.log('Restored order from localStorage:', orderItems);
            // Don't call updateOrderList here - will be called later
            // updateTotalAmount will also be called later
        }
    } catch (e) {
        console.error('Error loading saved order:', e);
        orderItems = [];
    }
    
    // Get category types for all products
    const categoryTypes = {};
    allProducts.forEach(p => { categoryTypes[p.id] = p.category_type || p.type; });

    // Modal elements for drink size selection
    let selectedDrinkProduct = null;
    // Modal HTML (inject only once, after DOMContentLoaded)
    if (!document.getElementById('drinkSizeModal')) {
        const drinkModal = document.createElement('div');
        drinkModal.innerHTML = `
        <div class="modal fade" id="drinkSizeModal" tabindex="-1" role="dialog" aria-labelledby="drinkSizeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="drinkSizeModalLabel">Select Drink Size</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div id="drink-modal-image" class="mb-3"></div>
                        <div id="drink-size-buttons" class="d-flex justify-content-center gap-2 flex-wrap mb-3"></div>
                        
                        <div class="mb-3">
                            <label for="sugar-level" class="form-label"><strong>Sugar Level</strong></label>
                            <select id="sugar-level" class="form-select">
                                <option value="no-sugar">No Sugar</option>
                                <option value="less-sugar">Less Sugar</option>
                                <option value="normal-sugar" selected>Normal Sugar</option>
                                <option value="more-sugar">More Sugar</option>
                            </select>
                        </div>
                        
                        <textarea id="drink-notes" class="form-control" rows="2" placeholder="Notes (e.g. no ice)"></textarea>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.appendChild(drinkModal.firstElementChild);
    }

    function bindProductClicks() {
        document.querySelectorAll('.prod-item').forEach(product => {
            product.addEventListener('click', function(e) {
                const productId = product.getAttribute('data-product-id');
                const productName = product.getAttribute('data-product-name');
                const productPriceRaw = product.getAttribute('data-product-price');
                // No more .prod-option span, so get options from allProducts
                const prodObj = allProducts.find(p => p.id == productId);
                const productOptions = prodObj && prodObj.options ? prodObj.options : '';
                const catType = prodObj && (prodObj.category_type || prodObj.type);

                // Check this product's ingredients for low stock (for all products)
                // Use 16oz as default size to ensure shared ingredients (cups, straws) are checked
                fetch(`check_product_stock.php?product_id=${productId}&size=16oz`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Stock check response:', data);
                        
                        // Check if any ingredient is out of stock (= 0)
                        if (data.outOfStock && data.outOfStock.length > 0) {
                            let html = '<ul style="padding-left:18px;">';
                            data.outOfStock.forEach(item => {
                                html += `<li><b>${item.name}</b>: OUT OF STOCK (${Math.floor(item.stock)} ${item.unit})</li>`;
                            });
                            html += '</ul>';
                            document.getElementById('lowStockModalBody').innerHTML =
                                '<div class="alert alert-danger">This product cannot be ordered - the following ingredients are OUT OF STOCK:</div>' + html;
                            document.getElementById('continueLowStock').style.display = 'none';
                            $('#lowStockModal').modal('show');
                            return;
                        }
                        
                        // If any ingredient is less than required, block and show alert (no continue)
                        if (data.insufficient && data.insufficient.length > 0) {
                            let html = '<ul style="padding-left:18px;">';
                            data.insufficient.forEach(item => {
                                html += `<li><b>${item.name}</b>: Required ${item.required} ${item.unit}, In Stock ${item.stock} ${item.unit}</li>`;
                            });
                            html += '</ul>';
                            document.getElementById('lowStockModalBody').innerHTML =
                                '<div class="alert alert-danger">The following ingredients are low on stock for this product:</div>' + html;
                            document.getElementById('continueLowStock').style.display = 'none';
                            $('#lowStockModal').modal('show');
                            return;
                        } else if (data.lowStockWarning && data.lowStockWarning.length > 0) {
                            // If ingredient can't make 10 products, show warning with continue option
                            let html = '<ul style="padding-left:18px;">';
                            data.lowStockWarning.forEach(item => {
                                html += `<li><b>${item.name}</b>: ${item.stock} ${item.unit} <span class="text-muted">(can only make ${item.can_make} products)</span></li>`;
                            });
                            html += '</ul>';
                            document.getElementById('lowStockModalBody').innerHTML =
                                '<div class="alert alert-warning">The following ingredients are low on stock for this product (cannot make 10 products):</div>' + html;
                            document.getElementById('continueLowStock').style.display = '';
                            $('#lowStockModal').modal('show');
                            // Only add to order if user clicks continue
                            document.getElementById('continueLowStock').onclick = function() {
                                $('#lowStockModal').modal('hide');
                                if (catType === 'Drinks') {
                                    showDrinkSizeModal();
                                } else {
                                    addFoodToOrder(productId, productName, productOptions, parseFloat(productPriceRaw), prodObj.category_name || '');
                                }
                            };
                        } else if (data.success) {
                            if (catType === 'Drinks') {
                                showDrinkSizeModal();
                            } else {
                                addFoodToOrder(productId, productName, productOptions, parseFloat(productPriceRaw), prodObj.category_name || '');
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error checking product stock:', err);
                        document.getElementById('lowStockModalBody').innerHTML =
                            '<div class="alert alert-warning">Could not verify stock. Please try again.</div>';
                        document.getElementById('continueLowStock').style.display = 'none';
                        $('#lowStockModal').modal('show');
                    });
                function showDrinkSizeModal() {
                    let prices = {};
                    try { prices = JSON.parse(prodObj.price); } catch (e) {}
                    selectedDrinkProduct = { id: productId, name: productName, options: productOptions, prices, category_name: prodObj.category_name || '' };
                    // Only include hot button for Coffee and Coffee Blended categories
                    const hotCategories = ['coffee', 'coffee blended'];
                    const categoryName = prodObj.category_name || '';
                    const btns = [
                        { size: '16oz', label: '16oz', price: prices['16oz'] },
                        { size: '22oz', label: '22oz', price: prices['22oz'] }
                    ];
                    // Add hot button only for specific categories
                    if (hotCategories.includes(categoryName.toLowerCase())) {
                        btns.push({ size: 'hot', label: 'Hot', price: prices['hot'] });
                    }
                    const imgDiv = document.getElementById('drink-modal-image');
                    imgDiv.innerHTML = '';
                    if (prodObj.image) {
                        const img = document.createElement('img');
                        img.src = prodObj.image;
                        img.alt = productName;
                        img.style.maxWidth = '120px';
                        img.style.maxHeight = '120px';
                        img.className = 'rounded mb-2';
                        imgDiv.appendChild(img);
                        const nameDiv = document.createElement('div');
                        nameDiv.textContent = productName;
                        nameDiv.style.fontWeight = 'bold';
                        nameDiv.style.fontSize = '1.1rem';
                        nameDiv.className = 'mt-2';
                        imgDiv.appendChild(nameDiv);
                    }
                    const btnContainer = document.getElementById('drink-size-buttons');
                    btnContainer.innerHTML = '';
                    btns.forEach(btn => {
                        let priceFloat = parseFloat(btn.price);
                        if (!isNaN(priceFloat)) {
                            const b = document.createElement('button');
                            b.className = 'btn btn-primary m-2';
                            b.textContent = `${btn.label} (₱${priceFloat.toFixed(2)})`;
                            b.onclick = function() {
                                const sugarLevel = document.getElementById('sugar-level').value;
                                const note = document.getElementById('drink-notes').value.trim();
                                
                                // Check stock for specific size before adding
                                fetch(`check_product_stock.php?product_id=${productId}&size=${btn.size}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        console.log('Size-specific stock check:', data);
                                        
                                        // Check if any ingredient is out of stock
                                        if (data.outOfStock && data.outOfStock.length > 0) {
                                            let html = '<ul style="padding-left:18px;">';
                                            data.outOfStock.forEach(item => {
                                                html += `<li><b>${item.name}</b>: OUT OF STOCK (${Math.floor(item.stock)} ${item.unit})</li>`;
                                            });
                                            html += '</ul>';
                                            document.getElementById('lowStockModalBody').innerHTML =
                                                '<div class="alert alert-danger">This product cannot be ordered in <strong>' + btn.size + '</strong> - the following ingredients are OUT OF STOCK:</div>' + html;
                                            document.getElementById('continueLowStock').style.display = 'none';
                                            $('#drinkSizeModal').modal('hide');
                                            $('#lowStockModal').modal('show');
                                            return;
                                        }
                                        
                                        // Check if available in 16oz only (for 22oz orders)
                                        if (data.availableIn16ozOnly && data.availableIn16ozOnly.length > 0) {
                                            let html = '<ul style="padding-left:18px;">';
                                            data.availableIn16ozOnly.forEach(item => {
                                                html += `<li><b>${item.name}</b>: Required ${item.required_22oz} ${item.unit} for 22oz, but only ${item.stock} ${item.unit} in stock (sufficient for 16oz which needs ${item.required_16oz} ${item.unit})</li>`;
                                            });
                                            html += '</ul>';
                                            document.getElementById('lowStockModalBody').innerHTML =
                                                '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> This product can be ordered in <strong>16oz</strong> but not in <strong>22oz</strong>:</div>' + html +
                                                '<div class="alert alert-info mt-2"><i class="fas fa-lightbulb"></i> Please select <strong>16oz</strong> instead.</div>';
                                            document.getElementById('continueLowStock').style.display = 'none';
                                            $('#drinkSizeModal').modal('hide');
                                            $('#lowStockModal').modal('show');
                                            return;
                                        }
                                        
                                        // Check if insufficient stock
                                        if (data.insufficient && data.insufficient.length > 0) {
                                            let html = '<ul style="padding-left:18px;">';
                                            data.insufficient.forEach(item => {
                                                html += `<li><b>${item.name}</b>: Required ${item.required} ${item.unit}, In Stock ${item.stock} ${item.unit}</li>`;
                                            });
                                            html += '</ul>';
                                            document.getElementById('lowStockModalBody').innerHTML =
                                                '<div class="alert alert-danger">Insufficient stock for <strong>' + btn.size + '</strong>:</div>' + html;
                                            document.getElementById('continueLowStock').style.display = 'none';
                                            $('#drinkSizeModal').modal('hide');
                                            $('#lowStockModal').modal('show');
                                            return;
                                        }
                                        
                                        // Stock is sufficient, proceed with adding to order
                                        addDrinkToOrder(selectedDrinkProduct, btn.size, priceFloat, note, sugarLevel, selectedDrinkProduct.category_name || '');
                                        // Reset form fields
                                        document.getElementById('drink-notes').value = '';
                                        document.getElementById('sugar-level').value = 'normal-sugar';
                                        $('#drinkSizeModal').modal('hide');
                                    })
                                    .catch(err => {
                                        console.error('Error checking size-specific stock:', err);
                                        document.getElementById('lowStockModalBody').innerHTML =
                                            '<div class="alert alert-warning">Could not verify stock for ' + btn.size + '. Please try again.</div>';
                                        document.getElementById('continueLowStock').style.display = 'none';
                                        $('#drinkSizeModal').modal('hide');
                                        $('#lowStockModal').modal('show');
                                    });
                            };
                            btnContainer.appendChild(b);
                        }
                    });
                    // Reset form to default values when modal is shown
                    document.getElementById('drink-notes').value = '';
                    document.getElementById('sugar-level').value = 'normal-sugar';
                    $('#drinkSizeModal').modal('show');
                }
            });
        });
    }

    function addDrinkToOrder(product, size, price, note, sugarLevel, categoryName) {
        // Add drink with size as options and notes, including sugar level
        let key = `${product.id}_${size}_${note || ''}_${sugarLevel || ''}`;
        let priceFloat = parseFloat(price);
        if (isNaN(priceFloat)) priceFloat = 0;
        let existingItem = orderItems.find(item => item.id === key);
        
        // Prepare sugar level text for notes
        const sugarLevelText = sugarLevel === 'no-sugar' ? 'No Sugar' : 
                               sugarLevel === 'less-sugar' ? 'Less Sugar' : 
                               sugarLevel === 'normal-sugar' ? 'Normal Sugar' : 
                               sugarLevel === 'more-sugar' ? 'More Sugar' : '';
        
        // Combine notes with sugar level
        let fullNote = sugarLevelText;
        if (note) {
            fullNote += '; ' + note;
        }
        
        // Check stock before adding
        console.log('ADDING DRINK - Deducting inventory for:', product.name, size, sugarLevel);
        fetch('adjust_stock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${encodeURIComponent(product.id)}&qty=-1&sugar_level=${encodeURIComponent(sugarLevel)}&size=${encodeURIComponent(size)}`
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.error || 'Insufficient stock!');
                return;
            }
            if (existingItem) {
                existingItem.qty += 1;
                existingItem.amount = existingItem.qty * priceFloat;
            } else {
                orderItems.push({
                    id: key,
                    name: product.name,
                    category_name: categoryName || '',
                    options: size,
                    size: size,
                    note: fullNote,
                    sugar_level: sugarLevel,
                    qty: 1,
                    price: priceFloat,
                    amount: priceFloat
                });
            }
            updateOrderList();
            updateTotalAmount();
        });
    }

    function addFoodToOrder(productId, productName, productOptions, productPrice, categoryName) {
        let existingItem = orderItems.find(item => item.id === productId);
        // Check stock before adding (food items default to 16oz)
        console.log('ADDING FOOD - Deducting inventory for:', productName);
        fetch('adjust_stock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${encodeURIComponent(productId)}&qty=-1&size=16oz`
        })
        .then(r => r.json())
        .then(res => {
            console.log('Adjust stock response:', res);
            if (!res.success) {
                alert('❌ ' + (res.error || 'Insufficient stock! Cannot add this product to order.'));
                console.error('Stock adjustment failed:', res.error);
                return;
            }
            if (existingItem) {
                existingItem.qty += 1;
                existingItem.amount = existingItem.qty * productPrice;
            } else {
                orderItems.push({
                    id: productId,
                    name: productName,
                    category_name: categoryName || '',
                    options: productOptions,
                    size: '16oz',
                    qty: 1,
                    price: productPrice,
                    amount: productPrice
                });
            }
            updateOrderList();
            updateTotalAmount();
        })
        .catch(err => {
            console.error('Error adjusting stock:', err);
            alert('Error: Could not process this order. Please try again.');
        });
    }

    // --- Order List and Payment Logic (your existing code, unchanged) ---
    const orderList = document.getElementById('o-list');
    const orderType = document.getElementById('order_type');
    const orderId = document.getElementById('order_id').value;
    if (orderId) {
        fetchOrderDetails(orderId);
    } else {
        // Only load from localStorage if we're NOT editing an existing order
        // (orderItems already initialized from localStorage above)
        // Just ensure the UI is rendered
        if (orderItems.length > 0) {
            updateOrderList();
            updateTotalAmount();
        }
    }

    function fetchOrderDetails(orderId) {
        fetch(`fetch_order.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    populateOrderForm(data.order, data.order_items);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching order details.');
            });
    }

    function populateOrderForm(order, orderItemsData) {
        console.log('POPULATE ORDER FORM - Loading existing order, NO inventory deduction');
        document.getElementById('order_type').value = order.order_type;
        orderItems = orderItemsData.map(item => ({
            id: item.product_id,
            name: item.product_name,
            category_name: item.category_name || '',
            options: item.options,
            size: item.size || '16oz', // Preserve size
            sugar_level: item.sugar_level, // Preserve sugar level
            note: item.note, // Preserve note
            qty: item.qty,
            price: parseFloat(item.price), 
            amount: parseFloat(item.amount) 
        }));
        updateOrderList();
        updateTotalAmount();
    }

    function updateOrderList() {
        let tbody = orderList.querySelector('tbody');
        tbody.innerHTML = ''; 

        orderItems.forEach(item => {
            let row = document.createElement('tr');
            let noteHtml = item.note ? `<div style='font-size:0.95em;color:#7a5c2e;margin-top:2px;'>Note: ${item.note}</div>` : '';
            let categoryHtml = item.category_name ? `<span style='font-size:0.85em;color:#999;font-style:italic;margin-left:5px;'>${item.category_name}</span>` : '';
            row.innerHTML = `
                <td style="text-align: left; display: flex; align-items: center; justify-content: flex-start;">
                    <button 
                        class="btn btn-sm btn-outline-secondary decrease-qty" 
                        data-id="${item.id}" 
                        style="margin-right: 3px;" 
                        ${item.qty === 1 ? 'disabled' : ''}
                    >
                        -
                    </button>
                    <span style="margin: 0 3px;">${item.qty}</span>
                    <button class="btn btn-sm btn-outline-secondary increase-qty" data-id="${item.id}" style="margin-left: 3px;">+</button>
                </td>
                <td style="text-align: left; word-wrap: break-word; min-width: 200px;">
                    <div>${item.name} (${item.options}) ${categoryHtml}</div>
                    ${noteHtml}
                </td>
                <td style="text-align: right; width: 120px;">
                    ${item.price.toFixed(2)}
                </td>
                <td style="text-align: right; width: 120px;">
                    ${item.amount.toFixed(2)}
                </td>
                <td style="text-align: center;">
                    <button class="btn btn-danger btn-sm remove-item" data-id="${item.id}">
                        <i class="fa fa-trash-alt"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        // Save to localStorage to persist across page refreshes
        try {
            localStorage.setItem('currentOrder', JSON.stringify(orderItems));
        } catch (e) {
            console.error('Error saving order to localStorage:', e);
        }
    }
    
    // Use event delegation for increase/decrease buttons to avoid duplicate listeners
    // AJAX-based quantity updates - NO PAGE RELOAD
    orderList.addEventListener('click', function(e) {
        // Handle increase quantity button
        if (e.target.closest('.increase-qty')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.increase-qty');
            const productId = button.getAttribute('data-id');
            const item = orderItems.find(item => item.id === productId);
            if (item) {
                // Get sugar level and size from item
                const sugarLevel = item.sugar_level || null;
                const size = item.size || '16oz';
                const originalProductId = item.id.split('_')[0];
                
                // Show loading state on button
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;
                
                // Build request body
                let bodyParams = `product_id=${encodeURIComponent(originalProductId)}&qty=-1&item_id=${encodeURIComponent(productId)}&size=${encodeURIComponent(size)}`;
                if (sugarLevel) {
                    bodyParams += `&sugar_level=${encodeURIComponent(sugarLevel)}`;
                }
                
                // AJAX call to update inventory and increase qty
                fetch('update_order_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyParams
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        showToast('error', 'Stock Error', res.error || 'Insufficient stock!');
                        button.innerHTML = originalText;
                        button.disabled = false;
                        return;
                    }
                    // Update item quantity locally (no page refresh)
                    item.qty += 1;
                    item.amount = item.qty * item.price;
                    updateOrderList();
                    updateTotalAmount();
                    showToast('success', 'Updated', 'Quantity increased');
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('error', 'Error', 'Failed to update quantity');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
        
        // Handle decrease quantity button
        if (e.target.closest('.decrease-qty')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.decrease-qty');
            const productId = button.getAttribute('data-id');
            const item = orderItems.find(item => item.id === productId);
            if (item && item.qty > 1) {
                // Get sugar level and size from item
                const sugarLevel = item.sugar_level || null;
                const size = item.size || '16oz';
                const originalProductId = item.id.split('_')[0];
                
                // Show loading state on button
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;
                
                // Build request body - add stock back (positive qty)
                let bodyParams = `product_id=${encodeURIComponent(originalProductId)}&qty=1&item_id=${encodeURIComponent(productId)}&size=${encodeURIComponent(size)}`;
                if (sugarLevel) {
                    bodyParams += `&sugar_level=${encodeURIComponent(sugarLevel)}`;
                }
                
                // AJAX call to restore inventory and decrease qty
                fetch('update_order_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyParams
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        showToast('error', 'Stock Error', res.error || 'Stock adjustment failed!');
                        button.innerHTML = originalText;
                        button.disabled = false;
                        return;
                    }
                    // Update item quantity locally (no page refresh)
                    item.qty -= 1;
                    item.amount = item.qty * item.price;
                    updateOrderList();
                    updateTotalAmount();
                    showToast('success', 'Updated', 'Quantity decreased');
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('error', 'Error', 'Failed to update quantity');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
    });

    // Payment modal and calculation logic
    document.getElementById('pay').addEventListener('click', function() {
        // Check if any products are selected
        if (orderItems.length === 0) {
            $('#noProductsModal').modal('show');
            return;
        }
        
        const orderType = order_type.value;
        if (!orderType) {
            $('#orderTypeModal').modal('show');
            // Store the payment action to execute after order type is selected
            window.pendingPaymentAction = 'cash';
            return;
        }
        $('#cashPaymentModal').modal('show');
    });

    function updateTotalAmount() {
        const totalAmount = orderItems.reduce((total, item) => total + item.amount, 0);
        document.getElementById('total_amount').textContent = totalAmount.toFixed(2);
    }

    const totalAmountElement = document.getElementById('totalAmount');
    const cashReceivedElement = document.getElementById('cashReceived');
    const changeDueElement = document.getElementById('changeDue');
    const processPaymentButton = document.getElementById('processPayment');
    const quickCashButtons = document.querySelectorAll('.quick-cash');

    quickCashButtons.forEach(button => {
        button.addEventListener('click', function() {
            const amount = parseFloat(this.getAttribute('data-amount'));
            cashReceivedElement.value = amount;
            calculateChangeDue();
        });
    });

    $('#cashPaymentModal').on('show.bs.modal', function (event) {
        const totalAmount = orderItems.reduce((total, item) => total + item.amount, 0);
        totalAmountElement.value = totalAmount.toFixed(2);
    });

    cashReceivedElement.addEventListener('input', calculateChangeDue);

    function calculateChangeDue() {
        const totalAmount = parseFloat(totalAmountElement.value) || 0;
        const cashReceived = parseFloat(cashReceivedElement.value) || 0;
        const changeDue = cashReceived - totalAmount;
        changeDueElement.value = changeDue >= 0 ? changeDue.toFixed(2) : '0.00';
    };

    function printReceipt(orderData) {
        orderData.username = '<?php echo $_SESSION['username'] ?? "N/A"; ?>';
        orderData.order_number = orderData.order_number || 'N/A';
        fetch('generate_receipt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.text())
        .then(receiptHtml => {
            const printWindow = window.open('', '_blank', "width=900,height=600");
            printWindow.document.write(receiptHtml);
            printWindow.document.close();
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        })
        .catch(error => {
            console.error('Error generating receipt:', error);
            alert('Failed to generate receipt.');
        });
    }

    processPaymentButton.addEventListener('click', function() {
        const totalAmount = parseFloat(totalAmountElement.value);
        const cashReceived = parseFloat(cashReceivedElement.value);
        if (cashReceived >= totalAmount) {
            const changeDue = cashReceived - totalAmount;
            const orderNotes = document.getElementById('order_notes').value.trim();
            const orderData = {
                order_id: document.getElementById('order_id').value,
                order_type: orderType.value,
                payment_type: 'cash',
                total_amount: totalAmount,
                amount_tendered: cashReceived,
                change_due: changeDue,
                order_notes: orderNotes,
                device_timestamp: getLocalDateTimeString(),
                items: orderItems
            };
            // Disable button to prevent double submission
            processPaymentButton.disabled = true;
            processPaymentButton.textContent = 'Processing...';
            
            fetch('save_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                processPaymentButton.disabled = false;
                processPaymentButton.textContent = 'Process Payment';
                
                if (data.success) {
                    orderData.order_number = data.order_number;
                    $('#cashPaymentModal').modal('hide');
                    document.getElementById('cashPaymentForm').reset();
                    changeDueElement.value = '';
                    alert('Payment successful!');
                    printReceipt(orderData);
                    orderItems = [];
                    localStorage.removeItem('currentOrder'); // Clear saved order
                    updateOrderList();
                    updateTotalAmount();
                    orderType.value = '';
                    document.getElementById('order_notes').value = '';
                } else {
                    alert('Error saving order: ' + data.message);
                }
            })
            .catch(error => {
                processPaymentButton.disabled = false;
                processPaymentButton.textContent = 'Process Payment';
                console.error('Error:', error);
                alert('Error processing payment.');
            });
        } else {
            alert('Insufficient cash received.');
        }
    });

    document.getElementById('save_order').addEventListener('click', function() {
        // Check if any products are selected
        if (orderItems.length === 0) {
            $('#noProductsModal').modal('show');
            return;
        }
        
        const orderType = order_type.value;
        if (!orderType) {
            $('#orderTypeModal').modal('show');
            // Store the payment action to execute after order type is selected
            window.pendingPaymentAction = 'save';
            return;
        }
        const orderNotes = document.getElementById('order_notes').value.trim();
        const totalAmount = parseFloat(document.getElementById('total_amount').textContent);
        const orderData = {
            order_type: orderType,
            payment_type: 'pending',
            total_amount: totalAmount,
            amount_tendered: totalAmount,
            change_due: 0,
            order_notes: orderNotes,
            device_timestamp: getLocalDateTimeString(),
            items: orderItems
        };
        fetch('save_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order saved for later payment.');
                orderItems = [];
                localStorage.removeItem('currentOrder'); // Clear saved order
                updateOrderList();
                updateTotalAmount();
                orderType.value = '';
                document.getElementById('order_notes').value = '';
            } else {
                alert('Error saving order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving order.');
        });
    });

    // Order Type Modal handlers
    document.getElementById('selectDineIn').addEventListener('click', function() {
        document.getElementById('order_type').value = 'dine-in';
        $('#orderTypeModal').modal('hide');
        // Execute pending payment action if any
        if (window.pendingPaymentAction === 'cash') {
            $('#cashPaymentModal').modal('show');
        } else if (window.pendingPaymentAction === 'save') {
            document.getElementById('save_order').click();
        } else if (window.pendingPaymentAction === 'paymongo') {
            $('#paymongoPaymentModal').modal('show');
        }
        window.pendingPaymentAction = null;
    });

    document.getElementById('selectTakeOut').addEventListener('click', function() {
        document.getElementById('order_type').value = 'take-out';
        $('#orderTypeModal').modal('hide');
        // Execute pending payment action if any
        if (window.pendingPaymentAction === 'cash') {
            $('#cashPaymentModal').modal('show');
        } else if (window.pendingPaymentAction === 'save') {
            document.getElementById('save_order').click();
        } else if (window.pendingPaymentAction === 'paymongo') {
            $('#paymongoPaymentModal').modal('show');
        }
        window.pendingPaymentAction = null;
    });

    // PayMongo Payment Handler
    document.getElementById('other_payment').addEventListener('click', function() {
        // Check if any products are selected
        if (orderItems.length === 0) {
            $('#noProductsModal').modal('show');
            return;
        }
        
        const orderType = order_type.value;
        if (!orderType) {
            $('#orderTypeModal').modal('show');
            window.pendingPaymentAction = 'paymongo';
            return;
        }
        
        const totalAmount = orderItems.reduce((total, item) => total + item.amount, 0);
        document.getElementById('paymongoTotalAmount').textContent = totalAmount.toFixed(2);
        $('#paymongoPaymentModal').modal('show');
    });

    // PayMongo payment method switcher
    document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'card') {
                document.getElementById('cardPaymentForm').style.display = 'block';
                document.getElementById('ewalletPaymentForm').style.display = 'none';
            } else {
                document.getElementById('cardPaymentForm').style.display = 'none';
                document.getElementById('ewalletPaymentForm').style.display = 'block';
            }
        });
    });

    // Format card number input
    document.getElementById('cardNumber').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });

    // Process PayMongo Payment
    document.getElementById('processPaymongoPayment').addEventListener('click', function() {
        const totalAmount = orderItems.reduce((total, item) => total + item.amount, 0);
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        
        // Validate based on payment method
        if (paymentMethod === 'card') {
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const expMonth = document.getElementById('cardExpMonth').value;
            const expYear = document.getElementById('cardExpYear').value;
            const cvc = document.getElementById('cardCvc').value;
            
            if (!cardNumber || !expMonth || !expYear || !cvc) {
                document.getElementById('paymongo-error-message').textContent = 'Please fill in all card details.';
                document.getElementById('paymongo-error').style.display = 'block';
                document.getElementById('paymongo-error').classList.add('shake');
                setTimeout(() => document.getElementById('paymongo-error').classList.remove('shake'), 500);
                return;
            }
            
            if (cardNumber.length < 13) {
                document.getElementById('paymongo-error-message').textContent = 'Invalid card number. Please check and try again.';
                document.getElementById('paymongo-error').style.display = 'block';
                document.getElementById('paymongo-error').classList.add('shake');
                setTimeout(() => document.getElementById('paymongo-error').classList.remove('shake'), 500);
                return;
            }
        }
        
        // Show loading
        document.getElementById('paymongo-loading').style.display = 'block';
        document.getElementById('paymongo-content').style.display = 'none';
        document.getElementById('paymongo-error').style.display = 'none';
        this.disabled = true;
        
        // For e-wallets, use Source API
        if (paymentMethod === 'gcash' || paymentMethod === 'grab_pay') {
            const orderNumber = 'ORD-' + Date.now();
            
            fetch('create_paymongo_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    amount: totalAmount,
                    order_number: orderNumber,
                    payment_type: paymentMethod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to create payment source');
                }
                
                // Save order with paid status (payment was successfully processed)
                return savePaymongoOrder(data.source_id, totalAmount, orderNumber, 'paid')
                    .then(() => {
                        // Store order info for reference
                        sessionStorage.setItem('pending_paymongo_order', JSON.stringify({
                            source_id: data.source_id,
                            order_number: orderNumber,
                            amount: totalAmount
                        }));
                        
                        // Open checkout URL in new window
                        const checkoutWindow = window.open(data.checkout_url, 'PayMongoCheckout', 'width=800,height=600');
                        
                        // Listen for payment result
                        window.addEventListener('message', function(event) {
                            if (event.data.type === 'paymongo_payment_result') {
                                if (event.data.status === 'success') {
                                    document.getElementById('paymongo-loading').style.display = 'none';
                                    $('#paymongoPaymentModal').modal('hide');
                                    alert('Payment completed successfully!');
                                    
                                    // Clear the order
                                    orderItems = [];
                                    localStorage.removeItem('currentOrder'); // Clear saved order
                                    updateOrderList();
                                    updateTotalAmount();
                                    orderType.value = '';
                                    document.getElementById('order_notes').value = '';
                                    
                                    sessionStorage.removeItem('pending_paymongo_order');
                                } else {
                                    throw new Error('Payment was not completed');
                                }
                                document.getElementById('processPaymongoPayment').disabled = false;
                            }
                        });
                        
                        // If user closes the window without completing
                        const checkInterval = setInterval(() => {
                            if (checkoutWindow.closed) {
                                clearInterval(checkInterval);
                                document.getElementById('paymongo-loading').style.display = 'none';
                                document.getElementById('paymongo-content').style.display = 'block';
                                document.getElementById('paymongo-error-message').textContent = 'Payment window was closed. Please try again if you want to complete the payment.';
                                document.getElementById('paymongo-error').style.display = 'block';
                                document.getElementById('processPaymongoPayment').disabled = false;
                            }
                        }, 1000);
                    });
            })
            .catch(error => {
                console.error('Payment error:', error);
                document.getElementById('paymongo-loading').style.display = 'none';
                document.getElementById('paymongo-content').style.display = 'block';
                document.getElementById('paymongo-error-message').textContent = error.message || 'Payment failed. Please try again.';
                document.getElementById('paymongo-error').style.display = 'block';
                document.getElementById('paymongo-error').classList.add('shake');
                setTimeout(() => document.getElementById('paymongo-error').classList.remove('shake'), 500);
                document.getElementById('processPaymongoPayment').disabled = false;
            });
            
            return; // Exit early for e-wallets
        }
        
        // For card payment, create payment intent
        fetch('create_paymongo_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                amount: totalAmount,
                order_number: 'ORD-' + Date.now()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to create payment intent');
            }
            
            // For card payment, create payment method and attach
            if (paymentMethod === 'card') {
                const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const expMonth = parseInt(document.getElementById('cardExpMonth').value);
                const expYear = parseInt(document.getElementById('cardExpYear').value);
                const cvc = document.getElementById('cardCvc').value;
                
                return createCardPaymentMethod(cardNumber, expMonth, expYear, cvc)
                    .then(pmData => {
                        if (!pmData.success) {
                            throw new Error('Failed to create payment method');
                        }
                        return attachPaymentMethod(data.payment_intent_id, pmData.payment_method_id);
                    })
                    .then(() => {
                        return savePaymongoOrder(data.payment_intent_id, totalAmount, data.order_number);
                    });
            }
        })
        .then(() => {
            // Payment processed successfully
            document.getElementById('paymongo-loading').style.display = 'none';
            $('#paymongoPaymentModal').modal('hide');
            alert('Payment processed successfully!');
            
            // Clear the order
            orderItems = [];
            localStorage.removeItem('currentOrder'); // Clear saved order
            updateOrderList();
            updateTotalAmount();
            orderType.value = '';
            document.getElementById('order_notes').value = '';
            
            // Reset form
            document.getElementById('cardNumber').value = '';
            document.getElementById('cardExpMonth').value = '';
            document.getElementById('cardExpYear').value = '';
            document.getElementById('cardCvc').value = '';
        })
        .catch(error => {
            console.error('Payment error:', error);
            document.getElementById('paymongo-loading').style.display = 'none';
            document.getElementById('paymongo-content').style.display = 'block';
            document.getElementById('paymongo-error-message').textContent = error.message || 'Payment failed. Please try again.';
            document.getElementById('paymongo-error').style.display = 'block';
            document.getElementById('paymongo-error').classList.add('shake');
            setTimeout(() => document.getElementById('paymongo-error').classList.remove('shake'), 500);
            document.getElementById('processPaymongoPayment').disabled = false;
        });
    });

    // Helper function to create card payment method
    function createCardPaymentMethod(cardNumber, expMonth, expYear, cvc) {
        return fetch('https://api.paymongo.com/v1/payment_methods', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Basic ' + btoa('pk_test_ErAW5ewZ6ynSjc2wweutsvZ1:')
            },
            body: JSON.stringify({
                data: {
                    attributes: {
                        type: 'card',
                        details: {
                            card_number: cardNumber,
                            exp_month: expMonth,
                            exp_year: expYear,
                            cvc: cvc
                        }
                    }
                }
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.data && data.data.id) {
                return {
                    success: true,
                    payment_method_id: data.data.id
                };
            } else {
                return {
                    success: false,
                    error: data.errors || 'Failed to create payment method'
                };
            }
        });
    }

    // Helper function to attach payment method
    function attachPaymentMethod(paymentIntentId, paymentMethodId) {
        return fetch('attach_payment_method.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                payment_intent_id: paymentIntentId,
                payment_method_id: paymentMethodId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to attach payment method');
            }
            return data;
        });
    }

    // Helper function to save order with PayMongo reference
    function savePaymongoOrder(paymentIntentId, totalAmount, orderNumber, paymentStatus = 'paid') {
        const orderNotes = document.getElementById('order_notes').value.trim();
        const orderData = {
            order_type: order_type.value,
            payment_type: 'paymongo',
            total_amount: totalAmount,
            amount_tendered: totalAmount,
            change_due: 0,
            order_notes: orderNotes,
            paymongo_reference: paymentIntentId,
            order_number: orderNumber,
            payment_status: paymentStatus,
            device_timestamp: getLocalDateTimeString(),
            items: orderItems
        };
        
        return fetch('save_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to save order');
            }
            return data;
        });
    }

    // Use event delegation for remove button clicks to avoid multiple listener attachments
    // AJAX-based item removal - NO PAGE RELOAD
    orderList.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.remove-item');
            const productId = button.getAttribute('data-id');
            const item = orderItems.find(item => item.id === productId);
            if (!item) return;
            
            if (confirm('Are you sure you want to remove this item?')) {
                // Show loading state on button
                const originalHTML = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;
                
                // Extract product_id and size from the item
                const originalProductId = item.id.split('_')[0];
                const sugarLevel = item.sugar_level || 'normal-sugar'; // Use stored sugar level
                const size = item.size || '16oz'; // Get size from item
                
                // Build request body to restore all inventory for this item
                let bodyParams = `product_id=${encodeURIComponent(originalProductId)}&qty=${item.qty}&size=${encodeURIComponent(size)}`;
                if (sugarLevel) {
                    bodyParams += `&sugar_level=${encodeURIComponent(sugarLevel)}`;
                }
                
                // AJAX call to restore inventory and delete item
                fetch('delete_order_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyParams
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        showToast('error', 'Deletion Error', res.error || 'Failed to remove item');
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                        return;
                    }
                    // Remove item from order locally (no page refresh)
                    orderItems = orderItems.filter(i => i.id !== productId);
                    updateOrderList();
                    updateTotalAmount();
                    showToast('success', 'Removed', 'Item removed from order');
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('error', 'Error', 'Failed to remove item');
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                });
            }
        }
    });

    // Initial render
    renderProducts(currentPage);
    renderPagination();
});
</script>