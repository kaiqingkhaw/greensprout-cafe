//Declare global variables
        let emptyTracking, trackingContent;

        document.addEventListener('DOMContentLoaded', function() {
            // Restaurant location
            const restaurantLocation = "Lot NO. 251, Jalan Bukit Bintang,55100 Kuala Lumpur, Malaysia";
 
            
            // Fetch products from server
function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
// A failed or missing photo must not leave an empty card.
function mountDishImage(container, source) {
    const fallback = 'assets/images/dish-placeholder.svg';
    const image = document.createElement('img');
    image.alt = ''; image.loading = 'lazy'; image.decoding = 'async';
    image.src = fallback;
    try {
        const url = new URL(source, location.href);
        if (source && ['http:', 'https:'].includes(url.protocol)) image.src = url.href;
    } catch {}
    image.addEventListener('error', () => { image.src = fallback; }, {once:true});
    container.prepend(image);
}
let products = [];
let catalogState = "loading";

async function fetchProducts() {
    catalogState = 'loading';
    displayProducts();
    try {
        const response = await GS.fetch('get_products.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        products = (await response.json()).map(product => ({
            ...product,
            price: Math.round(Number(product.price) * (product.discounted ? 1 - Number(product.discountPercent) / 100 : 1) * 100) / 100
        }));
        catalogState = 'ready';
        displayProducts();
        const requestedId = new URLSearchParams(location.search).get('product');
        if (requestedId) {
            const requested = products.find(product => String(product.id) === requestedId);
            if (requested) openProductModal(requested);
            else showNotification('This dish is no longer available. Browse our current menu.');
        }
    } catch (error) {
        console.error('Error fetching products:', error);
        products = [];
        catalogState = 'error';
        displayProducts();
    }
}

            // DOM Elements
            const productsGrid = document.getElementById('productsGrid');
            const productModal = document.getElementById('productModal');
            const closeModal = document.querySelector('.close-modal');
            const modalProductName = document.getElementById('modalProductName');
            const modalProductPrice = document.getElementById('modalProductPrice');
            const modalProductDescription = document.getElementById('modalProductDescription');
            const productBreadcrumb = document.getElementById('productBreadcrumb');
            const quantityInput = document.getElementById('quantity');
            const requestInput = document.getElementById('request');
            const addToCartBtn = document.getElementById('addToCart');
            const cartNotification = document.getElementById('cartNotification');
            const prevProductBtn = document.getElementById('prevProduct');
            const nextProductBtn = document.getElementById('nextProduct');
            const searchInput = document.getElementById('searchInput');
            const sortMenu = document.getElementById('sortMenu');
            const clearFilters = document.getElementById('clearFilters');
            const menuResultCount = document.getElementById('menuResultCount');
            const categoryFilters = {
                all: document.getElementById('all-categories'),
                breakfast: document.getElementById('breakfast'),
                mains: document.getElementById('mains'),
                drinks: document.getElementById('drinks'),
                desserts: document.getElementById('desserts')
            };
            const specialFilters = {
                featured: document.getElementById('featured'),
                discounted: document.getElementById('discounted')
            };
            const menuPage = document.getElementById('menu-page');
            const cartPage = document.getElementById('cart-page');
            const invoicePage = document.getElementById('invoice-page');
            const trackingPage = document.getElementById('tracking-page');
            const menuBtn = document.getElementById('menu-btn');
            const cartBtn = document.getElementById('cart-btn');
            const invoiceBtn = document.getElementById('invoice-btn');
            const trackingBtn = document.getElementById('tracking-btn');
            const cartCount = document.getElementById('cart-count');
            const cartItems = document.getElementById('cart-items');
            const totalItems = document.getElementById('total-items');
            const subtotalEl = document.getElementById('subtotal');
            const serviceEl = document.getElementById('service');
            const deliveryEl = document.getElementById('delivery');
            const totalEl = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkout-btn');
            const emptyInvoice = document.getElementById('empty-invoice');
            const invoiceDetails = document.getElementById('invoice-details');
            const progressBar = document.getElementById('progress-bar');
            const deliveryTime = document.getElementById('delivery-time');
            const backToTop = document.getElementById('backToTop');
            const discountCode = document.getElementById('discountCode');
            const applyDiscountBtn = document.getElementById('applyDiscount');
            const discountMessage = document.getElementById('discountMessage');
            const discountRow = document.getElementById('discountRow');
            const discountAmount = document.getElementById('discount');
            const confirmationModal = document.getElementById('confirmationModal');
            const confirmRemove = document.getElementById('confirmRemove');
            const cancelRemove = document.getElementById('cancelRemove');
            const loader = document.getElementById('loader');
            const charCounter = document.querySelector('.special-request small');
            const backToMenuTracking = document.getElementById('back-to-menu-tracking');
            const shopNowBtn = document.getElementById('shop-now');
            const invoiceStatusMessage = document.getElementById('invoice-status-message');
            
            // New elements for delivery form
            const cartStep = document.getElementById('cart-step');
            const deliveryStep = document.getElementById('delivery-step');
            const paymentStep = document.getElementById('payment-step');
            const backToCartBtn = document.getElementById('back-to-cart');
            const saveDeliveryBtn = document.getElementById('save-delivery');
            const backToDeliveryBtn = document.getElementById('back-to-delivery');
            const payNowBtn = document.getElementById('pay-now');
            const paymentCards = document.querySelectorAll('.payment-card');

            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            const fullNameInput = document.getElementById('fullName');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const addressInput = document.getElementById('address');

                       
            // Tracking page elements
            emptyTracking = document.getElementById('empty-tracking');
            trackingContent = document.getElementById('tracking-content');
            const trackingShopNow = document.getElementById('tracking-shop-now');
             // Fetch and display the user's most recent order
        async function trackLatestOrder() {
            try {
                const response = await GS.fetch('get_latest_order.php');
                if (!response.ok) {
                    emptyTracking.style.display = 'block';
                    trackingContent.style.display = 'none';
                    return;
                }
                const orderDetails = await response.json();
lastCompletedOrder = restoreOrder(orderDetails);
                emptyTracking.style.display = 'none';
                trackingContent.style.display = 'block';
                updateTrackingInfo();
            } catch (err) {
                console.error('Failed to fetch latest order:', err);
                emptyTracking.style.display = 'block';
                trackingContent.style.display = 'none';
            }
        }
            
            // Current product in modal
            let currentProductIndex = 0;
            let selectedProduct = null;
            let filteredProducts = [];
            let cart = [];
            let checkoutKey = crypto.randomUUID();
            try {
                const draft = JSON.parse(sessionStorage.getItem('greensprout-cart') || 'null');
                cart = GreenSproutCatalog.restoreCart(draft);
                if (typeof draft?.key === 'string' && /^[a-zA-Z0-9-]{16,64}$/.test(draft.key)) checkoutKey = draft.key;
            } catch { /* An invalid draft must not block the menu. */ }
            let currentPage = 'menu';
            let trackingTimer = null;
            let trackingBusy = false;
            let trackingEpoch = 0;
            let pageInitialized = false;
            let discountApplied = false;
            let discountValue = 0;
            let itemToRemove = null;
            let removeTrigger = null;
            let currentCartStep = 1;
            let paymentMethod = 'cash';
            let deliveryInfo = {
                fullName: '',
                email: '',
                phone: '',
                addressType: 'home',
                address: '',
                specialInstructions: '',
                saveAddress: false,
                distance: 0,
                deliveryFee: 5.00
            };

            // Store completed orders
            let completedOrders = [];
            let lastCompletedOrder = null;

            // Initialize the page
            fetchProducts();
            updateCartCount();
            menuBtn.classList.add('active');
            showPage('menu');
            setupCartSteps();
            GS.fetch('check_session.php').then(r => r.json()).then(session => {
                if (!session.loggedIn) return;
                fullNameInput.value = session.user.name || '';
                emailInput.value = session.user.email || '';
                phoneInput.value = session.user.phone || '';
                addressInput.value = session.user.address || '';
            }).catch(() => {});


            // Check if the URL has a track_order parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('view') === 'cart') showPage('cart');
            if (urlParams.get('view') === 'checkout') {
                showPage('cart');
                if (cart.length) beginCheckout();
            }
            const trackOrderNumber = urlParams.get('track_order');
            if (trackOrderNumber) {
                trackSpecificOrder(trackOrderNumber);
            } else if (['invoice', 'tracking'].includes(urlParams.get('view'))) {
                showPage(urlParams.get('view'));
            }
            
            // Get and display the tracking page based on the order number
            async function trackSpecificOrder(orderNumber) {
                try {
                    const response = await GS.fetch(`get_order_details.php?order_number=${encodeURIComponent(orderNumber)}`);
                    if (!response.ok) {
                        GS.notify('Order not found or you do not have permission to view it.');
                        return;
                    }
                    const orderDetails = await response.json();
lastCompletedOrder = restoreOrder(orderDetails);
                    completedOrders.push(lastCompletedOrder);
                    showPage(urlParams.get('view') === 'invoice' ? 'invoice' : 'tracking');
                } catch (err) {
                    GS.notify('Failed to load order details.');
                }
            }
            
            async function beginCheckout() {
                if (!cart.length) { GS.notify('Your cart is empty.'); return; }
                checkoutBtn.disabled = true;
                try {
                    const response = await GS.fetch('check_session.php');
                    if (!response.ok) throw new Error('Unable to check your session. Please try again.');
                    const session = await response.json();
                    if (!session.loggedIn) {
                        location.assign('login.html?next=checkout');
                        return;
                    }
                    showCartStep(2);
                } catch (error) { GS.notify(error.message); }
                finally { checkoutBtn.disabled = false; }
            }

            // Setup cart step navigation
            function setupCartSteps() {
                // Set initial step
                showCartStep(1);
                
                // Event listeners for step navigation
                checkoutBtn.addEventListener('click', beginCheckout);
                
                backToCartBtn.addEventListener('click', () => showCartStep(1));
                saveDeliveryBtn.addEventListener('click', saveDeliveryInfo);
                backToDeliveryBtn.addEventListener('click', () => showCartStep(2));
                payNowBtn.addEventListener('click', processPayment);
                
                paymentCards.forEach(card => card.addEventListener('click', () => {
                    paymentMethod = card.dataset.method;
                    DemoPayment.select(paymentMethod);
                    paymentCards.forEach(option => {
                        const selected = option === card;
                        option.classList.toggle('selected', selected);
                        option.setAttribute('aria-pressed', String(selected));
                    });
                }));
                
                // Address input for distance calculation
                addressInput.addEventListener('input', calculateDeliveryFee);
            }
            
            // Show specific cart step
            function showCartStep(step) {
                currentCartStep = step;
                if (step === 3 && window.DemoPayment) window.DemoPayment.setContext(totalEl.textContent, JSON.stringify(cart));
                
                // Hide all steps
                cartStep.classList.remove('active');
                deliveryStep.classList.remove('active');
                paymentStep.classList.remove('active');
                
                // Update step indicators
                step1.classList.remove('active');
                step2.classList.remove('active');
                step3.classList.remove('active');
                
                // Show the requested step
                if (step === 1) {
                    cartStep.classList.add('active');
                    step1.classList.add('active');
                } else if (step === 2) {
                    deliveryStep.classList.add('active');
                    step2.classList.add('active');
                } else if (step === 3) {
                    paymentStep.classList.add('active');
                    step3.classList.add('active');
                }
            }
            
            // Calculate delivery fee based on address
            function calculateDeliveryFee() {
                document.getElementById('distance-value').textContent = 'Kuala Lumpur';
                document.getElementById('delivery-fee').textContent = 'RM5.00';
                deliveryInfo.distance = null;
                deliveryInfo.deliveryFee = 5;
                deliveryInfo.estimatedDeliveryMinutes = 45;
            }
            
            // Save delivery information
            function saveDeliveryInfo() {
                deliveryInfo.fullName = fullNameInput.value.trim();
                deliveryInfo.email = emailInput.value.trim();
                deliveryInfo.phone = phoneInput.value.trim();
                deliveryInfo.addressType = document.getElementById('addressType').value;
                deliveryInfo.address = addressInput.value.trim();
                deliveryInfo.specialInstructions = document.getElementById('specialInstructions').value;
                deliveryInfo.saveAddress = document.getElementById('saveAddress').checked;
                
                if (!deliveryInfo.fullName) { GS.notify('Please enter your full name'); fullNameInput.focus(); return; }
                if (!deliveryInfo.email) { GS.notify('Please enter your email'); emailInput.focus(); return; }
                if (!deliveryInfo.phone) { GS.notify('Please enter your phone number'); phoneInput.focus(); return; }
                if (!deliveryInfo.address) { GS.notify('Please enter your address'); addressInput.focus(); return; }
                
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(deliveryInfo.email)) { GS.notify('Please enter a valid email address'); emailInput.focus(); return; }
                
                const phoneDigits = deliveryInfo.phone.replace(/\D/g, '');
                if (!/^\+?[0-9 ()-]+$/.test(deliveryInfo.phone) || phoneDigits.length < 8 || phoneDigits.length > 15) { GS.notify('Enter a phone number with 8–15 digits.'); phoneInput.focus(); return; }
                
                if (deliveryInfo.address.length < 10 || !deliveryInfo.address.toLowerCase().includes('kuala lumpur')) {
                    GS.notify('Sorry, we only deliver within Kuala Lumpur. Please enter a valid Kuala Lumpur address.');
                    addressInput.focus();
                    return;
                }
                
                // Saved addresses are persisted by the order endpoint with customer consent.
                deliveryEl.textContent = "RM" + deliveryInfo.deliveryFee.toFixed(2);
                updateCartTotal();
                showCartStep(3);
            }
            
            // Process payment
            async function processPayment() {
                if (payNowBtn.disabled) return;
                if (!cart.length) { GS.notify('Your cart is empty.'); return; }
                if (!DemoPayment.validate(paymentMethod)) return;
                payNowBtn.disabled = true;
                // Create a deep copy of the order
                const orderCopy = {
                    cart: JSON.parse(JSON.stringify(cart)),
                    deliveryInfo: JSON.parse(JSON.stringify(deliveryInfo)),
                    discountApplied: discountApplied,
                    discountValue: discountValue,
                    paymentMethod: paymentMethod,
                    orderDate: new Date(),
                    orderNumber: 'GS-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000)
                };
                // Calculate the total price
                const subtotal = orderCopy.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const serviceCharge = Math.round(subtotal * 5) / 100;
                const deliveryFee = orderCopy.deliveryInfo.deliveryFee;
                let total = subtotal + serviceCharge + deliveryFee - (orderCopy.discountApplied ? orderCopy.discountValue : 0);
                // Send to the backend
                try {
                    const response = await GS.fetch('create_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            order_number: orderCopy.orderNumber,
                            order_date: orderCopy.orderDate.toLocaleString('sv-SE', { hour12: false }),
                            items: orderCopy.cart,
                            delivery_info: orderCopy.deliveryInfo,
                            save_address: orderCopy.deliveryInfo.saveAddress,
                            request_key: checkoutKey,
                            payment_method: orderCopy.paymentMethod,
                            total: total,
                            discount_code: orderCopy.discountApplied ? 'ORGANIC10' : '',
                            status: 'pending',
                            estimated_delivery_minutes: orderCopy.deliveryInfo.estimatedDeliveryMinutes || 45
                        })
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        GS.notify(result.error || 'Order failed to save. Please try again.');
                        return;
                    }
                    // Use the server-authoritative order number and total.
                    orderCopy.orderNumber = result.order_number;
                    total = Number(result.total);
                    orderCopy.serverTotals = result.breakdown;
                    orderCopy.cart = result.items;
                    orderCopy.discountValue = Number(result.breakdown.discount);
                    orderCopy.deliveryInfo.deliveryFee = Number(result.breakdown.delivery);
                } catch (err) {
                    GS.notify('Unable to save the order. Please retry; the checkout identifier prevents duplicates.');
                    return;
                } finally { payNowBtn.disabled = false; }
                // Store the completed order (Also retained locally, compatible with front-end processes)
                checkoutKey = crypto.randomUUID();
                completedOrders.push(orderCopy);
                DemoPayment.clear();
                lastCompletedOrder = orderCopy;
                cart = [];
                discountApplied = false;
                discountValue = 0;
                updateCartCount();
                showPage('invoice');
                generateInvoice();
            }

            // Display products on page load
            function displayProducts() {
                if (catalogState !== 'ready') {
                    loader.style.display = catalogState === 'loading' ? 'block' : 'none';
                    productsGrid.style.display = catalogState === 'loading' ? 'none' : 'grid';
                    productsGrid.replaceChildren();
                    menuResultCount.textContent = catalogState === 'loading' ? 'Loading menu…' : 'Menu temporarily unavailable';
                    if (catalogState === 'error') {
                        const message = document.createElement('div');
                        message.className = 'catalog-message';
                        message.setAttribute('role', 'status');
                        message.textContent = 'We couldn’t load the menu. Please try again in a moment.';
                        const retry = document.createElement('button');
                        retry.type = 'button'; retry.textContent = 'Try again';
                        retry.addEventListener('click', fetchProducts);
                        message.append(retry); productsGrid.append(message);
                    }
                    return;
                }
                // Show loader
                loader.style.display = 'block';
                productsGrid.style.display = 'none';
                
                {
                    productsGrid.innerHTML = '';
                    filteredProducts = [];
                    
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    
                    // Filter products based on selected categories, special offers, and search term
                    const sortedProducts = [...products].sort((a,b) => {
                        if (sortMenu.value === 'name') return a.name.localeCompare(b.name);
                        if (sortMenu.value === 'price-low') return a.price - b.price;
                        if (sortMenu.value === 'price-high') return b.price - a.price;
                        return Number(b.featured) - Number(a.featured) || a.name.localeCompare(b.name);
                    });
                    sortedProducts.forEach(product => {
                        let shouldShow = false;
                        
                        // Check category filters
                        if (categoryFilters.all.checked ||
                            (product.category === "Breakfast" && categoryFilters.breakfast.checked) ||
                            (product.category === "Mains" && categoryFilters.mains.checked) ||
                            (product.category === "Drinks" && categoryFilters.drinks.checked) ||
                            (product.category === "Desserts" && categoryFilters.desserts.checked)) {
                            
                            // Check special offer filters
                            let passesSpecialFilter = true;
                            if (specialFilters.featured.checked && !product.featured) {
                                passesSpecialFilter = false;
                            }
                            if (specialFilters.discounted.checked && !product.discounted) {
                                passesSpecialFilter = false;
                            }
                            
                            if (passesSpecialFilter) {
                                // Check search term - improved search logic
                                if (GreenSproutCatalog.matches(product, searchTerm)) {
                                    filteredProducts.push(product);
                                    
                                    const productCard = document.createElement('div');
                                    productCard.className = 'product-card';
                        productCard.tabIndex = 0;
                        productCard.setAttribute('role', 'button');
                        productCard.setAttribute('aria-label', 'View ' + product.name);
                        productCard.addEventListener('keydown', event => {
                            if (event.target === productCard && (event.key === 'Enter' || event.key === ' ')) {
                                event.preventDefault(); productCard.click();
                            }
                        });
                                    
                                    let featuredBadge = '';
                                    if (product.featured) {
                                        featuredBadge = '<div class="featured-badge">Featured</div>';
                                    }
                                    
                                    let discountBadge = '';
                                    if (product.discounted) {
                                        discountBadge = '<div class="discount-badge">' + Number(product.discountPercent) + '% OFF</div>';
                                    }
                                    
                                    // If both featured and discounted, show discount badge on top, featured below
                                    if (product.featured && product.discounted) {
                                        featuredBadge = '<div class="featured-badge" style="top: 45px;">Featured</div>';
                                    }
                                    
                                    productCard.innerHTML = `
                                        <div class="product-image">
                                            ${featuredBadge}
                                            ${discountBadge}

                                        </div>
                                        <div class="product-info">
                                            <h3>${escapeHTML(product.name)} <span class="product-price">RM${product.price.toFixed(2)}</span></h3>
                                            <p>${escapeHTML(product.description.slice(0, 100))}${product.description.length > 100 ? "…" : ""}</p>
                                            <div class="product-action">View details <span aria-hidden="true">→</span></div>
                                        </div>
                                    `;
                                    
                                    mountDishImage(productCard.querySelector('.product-image'), product.image);
                                    productCard.addEventListener('click', () => openProductModal(product));
                                    

                                    
                                    productsGrid.appendChild(productCard);
                                }
                            }
                        }
                    });
                    
                    // Show message if no products found
                    if (filteredProducts.length === 0) {
                        productsGrid.innerHTML = '<p class="no-products" style="text-align:center;padding:40px;color:#777;">No products match your filters. Try adjusting your selections.</p>';
                    }
                    menuResultCount.textContent = filteredProducts.length === 1 ? '1 dish shown' : `${filteredProducts.length} dishes shown`;
                    
                    // Hide loader and show grid
                    loader.style.display = 'none';
                    productsGrid.style.display = 'grid';
                    if (selectedProduct) {
                        currentProductIndex = filteredProducts.findIndex(item => item.id === selectedProduct.id);
                        prevProductBtn.disabled = currentProductIndex <= 0;
                        nextProductBtn.disabled = currentProductIndex < 0 || currentProductIndex >= filteredProducts.length - 1;
                    }
                }
            }

            // Open product modal
            function openProductModal(product) {
                currentProductIndex = filteredProducts.findIndex(p => p.id === product.id);
                updateModalContent(product);
                productModal.style.display = 'block';
                closeModal.focus();
                document.body.style.overflow = 'hidden';
            }

            // Update modal content
            function updateModalContent(product) {
                selectedProduct = product;
                prevProductBtn.disabled = currentProductIndex <= 0;
                nextProductBtn.disabled = currentProductIndex < 0 || currentProductIndex >= filteredProducts.length - 1;
                modalProductName.textContent = product.name;
                modalProductPrice.textContent = `RM${product.price.toFixed(2)}`;
                modalProductDescription.textContent = product.description;
                document.querySelectorAll('#productModal .nutrition-item').forEach((element, index) => {
                    const fields = ['calories', 'protein', 'carbs', 'fats', 'fiber'];
                    const field = fields[index];
                    if (field) element.textContent = field.charAt(0).toUpperCase() + field.slice(1) + ': ' + (product[field] ?? 'Not provided') + (field === 'calories' ? ' kcal' : ' g');
                });
                productBreadcrumb.textContent = product.name;
                quantityInput.value = 1;
                requestInput.value = '';
                updateCharCount();
            }

            // Update character count
            function updateCharCount() {
                const maxLength = 200;
                const currentLength = requestInput.value.length;
                const remaining = maxLength - currentLength;
                charCounter.textContent = `${remaining} characters remaining`;
                
                if (remaining < 20) {
                    charCounter.style.color = '#ff6b6b';
                } else {
                    charCounter.style.color = '#777';
                }
            }

            // Close modal
            function closeProductModal() {
                productModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                productsGrid.querySelectorAll('.product-card')[currentProductIndex]?.focus();
            }

            // Navigate to previous product
            function showPreviousProduct() {
                if (currentProductIndex > 0) {
                    currentProductIndex--;
                    updateModalContent(filteredProducts[currentProductIndex]);
                }
            }

            // Navigate to next product
            function showNextProduct() {
                if (currentProductIndex < filteredProducts.length - 1) {
                    currentProductIndex++;
                    updateModalContent(filteredProducts[currentProductIndex]);
                }
            }

            // Add to cart function
            function addToCart() {
                const product = selectedProduct;
                if (!product) { GS.notify('Choose a menu item first.'); return; }
                const quantity = Math.min(20, Math.max(1, parseInt(quantityInput.value) || 1));
                const specialRequest = requestInput.value.trim();
                
                // Check if product is already in cart
                const existingItemIndex = cart.findIndex(item => item.id === product.id);
                const previousQuantity = existingItemIndex >= 0 ? cart[existingItemIndex].quantity : 0;
                const addedQuantity = Math.min(quantity, 20 - previousQuantity);
                if (addedQuantity === 0) { GS.notify('You already have the maximum of 20 of this item in your cart.'); return; }
                
                if (existingItemIndex >= 0) {
                    // Update existing item
                    cart[existingItemIndex].quantity = Math.min(20, cart[existingItemIndex].quantity + quantity);
                    cart[existingItemIndex].specialRequest = specialRequest || cart[existingItemIndex].specialRequest;
                } else {
                    // Add new item to cart
                    cart.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        image: product.image,
                        quantity: quantity,
                        specialRequest: specialRequest
                    });
                }
                
                // Update cart count
                updateCartCount();
                
                // Close modal
                closeProductModal();
                
                // Update cart if on cart page
                if (currentPage === 'cart') {
                    renderCart();
                }
                
                // Show notification
                showNotification(`${addedQuantity} ${escapeHTML(product.name)} added to cart!${addedQuantity < quantity ? ' Maximum 20 per item.' : ''}`);
            }

            // Show notification
            function showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'cart-notification';
                notification.setAttribute('role', 'status');
                notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
                document.body.appendChild(notification);
                
                notification.style.display = 'flex';
                setTimeout(() => {
                    notification.style.display = 'none';
                    document.body.removeChild(notification);
                }, 3000);
            }

            // Update cart count
            function updateCartCount() {
                const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
                cartCount.textContent = itemCount;
                const headerCount = document.getElementById('header-cart-count');
                if (headerCount) headerCount.textContent = itemCount;
                try { sessionStorage.setItem('greensprout-cart', JSON.stringify({cart, key:checkoutKey})); } catch { /* Optional cart draft. */ }
            }

            // Render cart items
            function renderCart() {
                cartItems.innerHTML = '';
                
                if (cart.length === 0) {
                    cartItems.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Your cart is empty. Add some delicious organic food!</p>
                        </div>
                    `;
                    totalItems.textContent = '0';
                    subtotalEl.textContent = 'RM0.00';
                    serviceEl.textContent = 'RM0.00';
                    discountRow.style.display = 'none';
                    totalEl.textContent = 'RM5.00';
                    document.getElementById('cart-summary').style.display = 'none';
                    checkoutBtn.disabled = true;
                    return;
                }
                
                document.getElementById('cart-summary').style.display = 'block';
                checkoutBtn.disabled = false;
                
                updateCartTotal();
                
                cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    
                    const cartItem = document.createElement('div');
                    cartItem.className = 'cart-item';
                    cartItem.innerHTML = `
                        <div class="item-image"></div>
                        <div class="item-details">
                            <div class="item-name">${escapeHTML(item.name)}</div>
                            <div class="item-price">RM${item.price.toFixed(2)}</div>
                            ${item.specialRequest ? `<div class="item-request"><small><i class="fas fa-sticky-note"></i> Note: ${escapeHTML(item.specialRequest)}</small></div>` : ''}
                            <div class="item-quantity">
                                <button class="quantity-btn minus" data-index="${index}" aria-label="Decrease ${escapeHTML(item.name)} quantity" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="20" aria-label="${escapeHTML(item.name)} quantity" data-index="${index}">
                                <button class="quantity-btn plus" data-index="${index}" aria-label="Increase ${escapeHTML(item.name)} quantity" ${item.quantity >= 20 ? 'disabled' : ''}>+</button>
                                <button class="remove-btn" data-index="${index}" aria-label="Remove ${escapeHTML(item.name)}" title="Remove item"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    `;
                    
                    mountDishImage(cartItem.querySelector('.item-image'), item.image);
                    cartItems.appendChild(cartItem);
                });
                
                // Add event listeners to cart controls
                document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
                    btn.addEventListener('click', () => updateCartItem(parseInt(btn.dataset.index), -1));
                });
                
                document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
                    btn.addEventListener('click', () => updateCartItem(parseInt(btn.dataset.index), 1));
                });
                
                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', (e) => {
                        const index = parseInt(e.target.dataset.index);
                        const newQuantity = parseInt(e.target.value) || 1;
                        updateCartItem(index, 0, newQuantity);
                    });
                });
                
                document.querySelectorAll('.remove-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        itemToRemove = parseInt(btn.dataset.index);
                        removeTrigger = btn;
                        confirmationModal.style.display = 'flex';
                        cancelRemove.focus();
                    });
                });
            }
            
            // Update cart total
            function updateCartTotal() {
                let totalCount = 0;
                let subtotal = 0;
                
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    totalCount += item.quantity;
                });
                
                const serviceCharge = Math.round(subtotal * 5) / 100;
                const deliveryFee = deliveryInfo.deliveryFee || 5.00;
                let total = subtotal + serviceCharge + deliveryFee;
                
                // Apply discount if valid
                if (discountApplied) {
                    discountValue = Math.min(10, subtotal);
                    total -= discountValue;
                    discountRow.style.display = 'flex';
                    discountAmount.textContent = `-RM${discountValue.toFixed(2)}`;
                } else {
                    discountRow.style.display = 'none';
                }
                
                totalItems.textContent = totalCount;
                subtotalEl.textContent = `RM${subtotal.toFixed(2)}`;
                serviceEl.textContent = `RM${serviceCharge.toFixed(2)}`;
                deliveryEl.textContent = `RM${deliveryFee.toFixed(2)}`;
                totalEl.textContent = `RM${total.toFixed(2)}`;
            }

            // Update cart item quantity
            function updateCartItem(index, change, newQuantity = null) {
                if (newQuantity !== null) {
                    cart[index].quantity = Math.min(20, Math.max(1, Number.isFinite(newQuantity) ? Math.floor(newQuantity) : 1));
                } else {
                    cart[index].quantity = Math.min(20, cart[index].quantity + change);
                    if (cart[index].quantity < 1) cart[index].quantity = 1;
                }
                
                // Update cart display
                renderCart();
                updateCartCount();
            }

            // Remove item from cart
            function removeCartItem() {
                if (itemToRemove !== null) {
                    cart.splice(itemToRemove, 1);
                    renderCart();
                    updateCartCount();
                    confirmationModal.style.display = 'none';
                    itemToRemove = null;
                }
            }

            // Generate invoice
            function restoreOrder(record) {
                return {
                    orderNumber: record.order_number, orderDate: new Date(record.order_date.replace(' ', 'T')),
                    cart: JSON.parse(record.items), deliveryInfo: record.deliveryInfo,
                    serverTotals: record.breakdown, paymentMethod: record.payment_method || 'cash',
                    discountApplied: Number(record.breakdown?.discount || 0) > 0,
                    discountValue: Number(record.breakdown?.discount || 0)
                };
            }
            async function generateInvoice() {
                if (!lastCompletedOrder) {
                    try {
                        const response = await GS.fetch('get_latest_order.php?include_completed=1');
                        if (response.ok) lastCompletedOrder = restoreOrder(await response.json());
                    } catch { invoiceStatusMessage.textContent = 'Unable to load your invoice. Please retry.'; }
                }
                // Hide the empty invoice message by default
                emptyInvoice.style.display = 'none';
                invoiceDetails.style.display = 'none';
                
                // If no completed order, show empty invoice message
                if (!lastCompletedOrder) {
                    emptyInvoice.style.display = 'block';
                    invoiceStatusMessage.textContent = "Order Invoice";
                    return;
                }
                
                // If we have a completed order, show the invoice details
                emptyInvoice.style.display = 'none';
                invoiceDetails.style.display = 'block';
                
                // Render invoice details
                renderInvoiceDetails(lastCompletedOrder);
            }

            // Render invoice details
            function renderInvoiceDetails(order) {
                invoiceDetails.innerHTML = '';
                if (!order.deliveryInfo) {
                    const notice = document.createElement('p');
                    notice.textContent = 'The detailed checkout receipt is available only in the session where you placed the order. Your saved items, total, and status are available in your profile.';
                    const link = document.createElement('a');
                    link.href = 'profile.php';
                    link.textContent = 'View order history';
                    invoiceDetails.append(notice, link);
                    return;
                }
                
                // Calculate totals
                const subtotal = order.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const serviceCharge = Math.round(subtotal * 5) / 100;
                const deliveryFee = order.deliveryInfo.deliveryFee;
                let total = order.serverTotals ? Number(order.serverTotals.total) : subtotal + serviceCharge + deliveryFee - (order.discountApplied ? order.discountValue : 0);
                
                // Format payment method
                const paymentMethodText = {
                    'card_demo': 'Card (demo — no charge processed)',
                    'ewallet_demo': 'E-wallet (demo — no charge processed)',
                    'card': 'Credit/Debit Card',
                    'paypal': 'PayPal',
                    'cash': 'Cash on Delivery'
                }[order.paymentMethod] || order.paymentMethod;
                
                // Create invoice HTML
                const invoiceHTML = `
                    <div class="invoice-details">
                        <div class="detail-box">
                            <h3><i class="fas fa-user"></i> Customer Information</h3>
                            <p><strong>Name:</strong> ${escapeHTML(order.deliveryInfo.fullName)}</p>
                            <p><strong>Email:</strong> ${escapeHTML(order.deliveryInfo.email)}</p>
                            <p><strong>Phone:</strong> ${escapeHTML(order.deliveryInfo.phone)}</p>
                            <p><strong>Address:</strong> ${escapeHTML(order.deliveryInfo.address)}</p>
                            <p><strong>Special Instructions:</strong> ${escapeHTML(order.deliveryInfo.specialInstructions || 'None')}</p>
                        </div>
                        
                        <div class="detail-box">
                            <h3><i class="fas fa-receipt"></i> Order Information</h3>
                            <p><strong>Order #:</strong> ${order.orderNumber}</p>
                            <p><strong>Order Date:</strong> ${order.orderDate.toLocaleString()}</p>
                            <p><strong>Payment Method:</strong> ${paymentMethodText}</p>
                        </div>
                    </div>
                    
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${order.cart.map(item => `
                                <tr>
                                    <td>${escapeHTML(item.name)}${item.specialRequest ? '<br><small>' + escapeHTML(item.specialRequest) + '</small>' : ''}</td>
                                    <td>${item.quantity}</td>
                                    <td>RM${item.price.toFixed(2)}</td>
                                    <td>RM${(item.price * item.quantity).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div class="invoice-total">
                        <div class="total-row">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>RM${subtotal.toFixed(2)}</span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee:</span>
                                <span>RM${deliveryFee.toFixed(2)}</span>
                            </div>
                            <div class="summary-row">
                                <span>Service Charge (5%):</span>
                                <span>RM${serviceCharge.toFixed(2)}</span>
                            </div>
                            ${order.discountApplied ? `
                            <div class="summary-row">
                                <span>Discount:</span>
                                <span>-RM${order.discountValue.toFixed(2)}</span>
                            </div>
                            ` : ''}
                            <div class="summary-row summary-total">
                                <span>Total:</span>
                                <span>RM${total.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="thank-you">
                        <h3>Thank you for your order!</h3>
                        <p>Your demo order has been received. View its latest status on the tracking page.</p>
                        <button id="track-invoice-order" class="back-to-menu"><i class="fas fa-truck"></i> Track Order</button>
                        <button id="back-to-menu-invoice" class="back-to-menu"><i class="fas fa-home"></i> Back to Menu</button>
                    </div>
                `;
                
                invoiceDetails.innerHTML = invoiceHTML;
                
                // Add event listeners for the buttons
                document.getElementById('track-invoice-order')?.addEventListener('click', () => {
                    showPage('tracking');
                });
                
                document.getElementById('back-to-menu-invoice')?.addEventListener('click', () => {
                    showPage('menu');
                });
            }

            // Apply discount
            function applyDiscountCode() {
                const code = discountCode.value.trim().toUpperCase();
                if (code === 'ORGANIC10') {
                    discountApplied = true;
                    discountValue = 10;
                    discountMessage.textContent = 'Discount applied: RM10 off your order!';
                    discountMessage.style.display = 'block';
                    discountMessage.style.color = '#2e8b57';
                    renderCart();
                } else if (code === '') {
                    discountMessage.textContent = 'Please enter a discount code';
                    discountMessage.style.display = 'block';
                    discountMessage.style.color = '#ff6b6b';
                } else {
                    discountMessage.textContent = 'Invalid discount code';
                    discountMessage.style.display = 'block';
                    discountMessage.style.color = '#ff6b6b';
                }
            }

            // Poll the saved order state; never invent delivery transitions.
            function stopTracking() {
                clearTimeout(trackingTimer);
                trackingTimer = null;
                trackingEpoch++;
            }
            async function updateTrackingInfo() {
                if (!lastCompletedOrder || currentPage !== 'tracking' || trackingBusy) return;
                clearTimeout(trackingTimer);
                trackingBusy = true;
                const number = lastCompletedOrder.orderNumber;
                const epoch = trackingEpoch;
                let terminal = false;
                document.getElementById('tracking-order-number').textContent = 'Order #: ' + number;
                try {
                    const response = await GS.fetch('get_order_details.php?order_number=' + encodeURIComponent(number), {cache:'no-store', signal: AbortSignal.timeout(8000)});
                    if (!response.ok) {
                        if (response.status === 401 || response.status === 404) terminal = true;
                        throw new Error(response.status === 401 ? 'Please sign in again to track this order.' : response.status === 404 ? 'This order is unavailable for your account.' : 'Connection interrupted. Retrying automatically…');
                    }
                    const order = await response.json();
                    if (epoch !== trackingEpoch || number !== lastCompletedOrder.orderNumber) return;
                    const view = GreenSproutTracking.view(order.status);
                    terminal = view.terminal;
                    ['placed','preparing','ontheway','delivered'].forEach((name,i) => {
                        document.getElementById('status-' + name).classList.toggle('active', view.index >= i);
                        document.getElementById('status-time-' + name).textContent = '';
                    });
                    document.getElementById('delivery-time').textContent = view.label;
                    document.getElementById('tracking-explanation').textContent = view.description;
                    progressBar.style.width = view.progress + '%';
                    const rider = document.getElementById('rider-marker');
                    rider.hidden = order.status === 'cancelled';
                    rider.style.left = view.position + '%';
                    rider.setAttribute('aria-label', view.label + ' (status illustration, not GPS)');
                    document.getElementById('tracking-update').textContent = 'Updated ' + new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'}) + (terminal ? ' · Final status' : ' · Auto-refresh every 5 seconds');
                } catch (error) {
                    if (epoch === trackingEpoch) document.getElementById('tracking-update').textContent = error.message;
                } finally {
                    trackingBusy = false;
                    if (currentPage === 'tracking' && !document.hidden && (!terminal || epoch !== trackingEpoch)) trackingTimer = setTimeout(updateTrackingInfo, 5000);
                }
            }
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) stopTracking();
                else if (currentPage === 'tracking') updateTrackingInfo();
            });
            window.addEventListener('pagehide', stopTracking);

            // Switch pages
            function showPage(page) {
                stopTracking();
                menuPage.style.display = 'none';
                cartPage.style.display = 'none';
                invoicePage.style.display = 'none';
                trackingPage.style.display = 'none';
                
                if (pageInitialized) requestAnimationFrame(() => revealCurrentSection());
                pageInitialized = true;
                // Update active button
                document.querySelectorAll('.nav-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                if (page === 'menu') {
                    menuPage.style.display = 'block';
                    currentPage = 'menu';
                    menuBtn.classList.add('active');
                    displayProducts();
                } else if (page === 'cart') {
                    cartPage.style.display = 'block';
                    currentPage = 'cart';
                    cartBtn.classList.add('active');
                    renderCart();
                    showCartStep(1);
                } else if (page === 'invoice') {
                    invoicePage.style.display = 'block';
                    currentPage = 'invoice';
                    invoiceBtn.classList.add('active');
                    generateInvoice();
                } else if (page === 'tracking') {
                    trackingPage.style.display = 'block';
                    currentPage = 'tracking';
                    trackingBtn.classList.add('active');
                    
                    // If we have a local order, show it. Otherwise, fetch the latest one.
                    if (lastCompletedOrder) {
                        emptyTracking.style.display = 'none';
                        trackingContent.style.display = 'block';
                        updateTrackingInfo();
                    } else {
                        trackLatestOrder();
                    }
                }
            }

            // Keep the active section below the fixed header.
            function revealCurrentSection() {
                const section = {menu:menuPage,cart:cartPage,invoice:invoicePage,tracking:trackingPage}[currentPage];
                if (section) section.scrollIntoView({block:'start',behavior:'smooth'});
            }

            // Back to top functionality
            function handleScroll() {
                if (window.scrollY > 300) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            }
            
            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Event listeners
            closeModal.addEventListener('click', closeProductModal);
            productModal.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeProductModal();
                if (event.key === 'Tab') {
                    const controls = [...productModal.querySelectorAll('button, input, textarea, a')].filter(el => !el.disabled && el.getClientRects().length);
                    const first = controls[0], last = controls[controls.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                }
            });
            window.addEventListener('click', (e) => {
                if (e.target === productModal) {
                    closeProductModal();
                }
                if (e.target === confirmationModal) {
                    confirmationModal.style.display = 'none';
                    itemToRemove = null;
                }
            });

            prevProductBtn.addEventListener('click', showPreviousProduct);
            nextProductBtn.addEventListener('click', showNextProduct);
            addToCartBtn.addEventListener('click', addToCart);
            
            // Filter change listeners
            Object.values(categoryFilters).forEach(filter => {
                filter.addEventListener('change', displayProducts);
            });
            
            // Special filter change listeners
            Object.values(specialFilters).forEach(filter => {
                filter.addEventListener('change', displayProducts);
            });
            sortMenu.addEventListener('change', displayProducts);
            clearFilters.addEventListener('click', () => {
                searchInput.value = '';
                categoryFilters.all.checked = true;
                Object.values(specialFilters).forEach(filter => { filter.checked = false; });
                sortMenu.value = 'featured';
                displayProducts(); searchInput.focus();
            });
            
            // Popular choices click listeners
            document.querySelectorAll('.popular-section a').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productName = link.textContent;
                    const product = products.find(p => p.name === productName);
                    if (product) {
                        openProductModal(product);
                    }
                });
            });
            
            // Search input listener
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(displayProducts, 300);
            });
            
            // Navigation listeners
            menuBtn.addEventListener('click', () => showPage('menu'));
            cartBtn.addEventListener('click', () => showPage('cart'));
            invoiceBtn.addEventListener('click', () => showPage('invoice'));
            trackingBtn.addEventListener('click', () => showPage('tracking'));
            
            // Back to menu button
            shopNowBtn.addEventListener('click', () => {
                showPage('menu');
            });

            // Back to top
            window.addEventListener('scroll', handleScroll);
            backToTop.addEventListener('click', scrollToTop);
            
            // Discount code
            applyDiscountBtn.addEventListener('click', applyDiscountCode);
            discountCode.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    applyDiscountCode();
                }
            });
            
            // Confirmation modal
            confirmRemove.addEventListener('click', () => { removeCartItem(); cartBtn.focus(); });
            cancelRemove.addEventListener('click', () => {
                confirmationModal.style.display = 'none';
                itemToRemove = null;
                removeTrigger?.focus();
            });
            confirmationModal.addEventListener('keydown', event => {
                if (event.key === 'Escape') { event.preventDefault(); cancelRemove.click(); }
                if (event.key === 'Tab') {
                    event.preventDefault();
                    (document.activeElement === cancelRemove ? confirmRemove : cancelRemove).focus();
                }
            });
            
            // Character counter
            requestInput.addEventListener('input', updateCharCount);
            
            // Back to menu from tracking page
            backToMenuTracking.addEventListener('click', () => showPage('menu'));
            
            // Shop now from empty tracking page
            trackingShopNow.addEventListener('click', () => showPage('menu'));
            
            // Refresh tracking button
            document.getElementById('refresh-tracking').addEventListener('click', () => {
                if (lastCompletedOrder) {
                    updateTrackingInfo();
                } else {
                    trackLatestOrder();
                }
            });
        });
