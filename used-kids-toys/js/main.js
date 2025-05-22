// Main JavaScript file for Used Kids Toys and More

// DOM Elements
const navToggle = document.getElementById("nav-toggle")
const nav = document.querySelector("nav")
const themeToggle = document.getElementById("theme-toggle")
const backToTopBtn = document.getElementById("back-to-top")
const toastContainer = document.getElementById("toast-container")

// Initialize the site
document.addEventListener("DOMContentLoaded", () => {
  // Setup mobile navigation
  setupMobileNav()

  // Setup theme toggle
  setupThemeToggle()

  // Setup back to top button
  setupBackToTop()

  // Update cart count
  updateCartCount()

  // Setup product filters if on products page
  if (document.getElementById("filter-form")) {
    setupFilters()
  }

  // Setup cart functionality if on cart page
  if (document.getElementById("cart-items")) {
    loadCart()
  }

  // Setup product gallery if on product detail page
  if (document.querySelector(".product-gallery")) {
    setupProductGallery()
  }

  // Setup wishlist functionality
  setupWishlist()

  // Setup age range slider if it exists
  if (document.querySelector(".age-range-slider")) {
    setupAgeRangeSlider()
  }

  // Setup recently viewed products
  trackRecentlyViewed()
})

// Mobile Navigation
function setupMobileNav() {
  if (!navToggle) return

  navToggle.addEventListener("click", () => {
    nav.classList.toggle("active")
    navToggle.textContent = nav.classList.contains("active") ? "Close" : "Menu"
  })

  // Close mobile nav when clicking outside
  document.addEventListener("click", (event) => {
    if (nav.classList.contains("active") && !nav.contains(event.target) && event.target !== navToggle) {
      nav.classList.remove("active")
      navToggle.textContent = "Menu"
    }
  })
}

// Theme Toggle (Dark/Light Mode)
function setupThemeToggle() {
  if (!themeToggle) return

  // Check for saved theme preference or respect OS preference
  const prefersDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches
  const savedTheme = localStorage.getItem("theme")

  if (savedTheme === "dark" || (!savedTheme && prefersDarkMode)) {
    document.body.classList.add("dark-mode")
    themeToggle.innerHTML = '<i class="fas fa-sun"></i>'
  } else {
    themeToggle.innerHTML = '<i class="fas fa-moon"></i>'
  }

  themeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode")

    if (document.body.classList.contains("dark-mode")) {
      localStorage.setItem("theme", "dark")
      themeToggle.innerHTML = '<i class="fas fa-sun"></i>'
    } else {
      localStorage.setItem("theme", "light")
      themeToggle.innerHTML = '<i class="fas fa-moon"></i>'
    }
  })
}

// Back to Top Button
function setupBackToTop() {
  if (!backToTopBtn) return

  window.addEventListener("scroll", () => {
    if (window.pageYOffset > 300) {
      backToTopBtn.classList.add("visible")
    } else {
      backToTopBtn.classList.remove("visible")
    }
  })

  backToTopBtn.addEventListener("click", () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    })
  })
}

// Toast Notifications
function showToast(type, title, message, duration = 3000) {
  if (!toastContainer) return

  const toast = document.createElement("div")
  toast.className = `toast toast-${type}`

  let icon = ""
  switch (type) {
    case "success":
      icon = '<i class="fas fa-check-circle"></i>'
      break
    case "error":
      icon = '<i class="fas fa-exclamation-circle"></i>'
      break
    case "warning":
      icon = '<i class="fas fa-exclamation-triangle"></i>'
      break
    default:
      icon = '<i class="fas fa-info-circle"></i>'
  }

  toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close">&times;</button>
    `

  toastContainer.appendChild(toast)

  // Auto remove after duration
  const timeout = setTimeout(() => {
    toast.remove()
  }, duration)

  // Close button
  toast.querySelector(".toast-close").addEventListener("click", () => {
    clearTimeout(timeout)
    toast.remove()
  })
}

// Cart Functions
function updateCartCount() {
  const cartCountElements = document.querySelectorAll(".cart-count")
  if (cartCountElements.length === 0) return

  // Get cart from localStorage
  const cart = JSON.parse(localStorage.getItem("cart")) || []
  const count = cart.reduce((total, item) => total + item.quantity, 0)

  // Update all cart count elements
  cartCountElements.forEach((element) => {
    element.textContent = count
    element.style.display = count > 0 ? "inline-flex" : "none"
  })
}

function addToCart(productId, quantity = 1) {
  // Fetch product details
  fetch(`php/products.php?id=${productId}`)
    .then((response) => response.json())
    .then((product) => {
      if (!product) {
        showToast("error", "Error", "Product not found")
        return
      }

      // Get current cart
      const cart = JSON.parse(localStorage.getItem("cart")) || []

      // Check if product already in cart
      const existingItemIndex = cart.findIndex((item) => item.id === product.id)

      if (existingItemIndex !== -1) {
        // Update quantity if already in cart
        cart[existingItemIndex].quantity += quantity
      } else {
        // Add new item to cart
        cart.push({
          id: product.id,
          name: product.name,
          price: product.price,
          image: product.image,
          quantity: quantity,
        })
      }

      // Save updated cart
      localStorage.setItem("cart", JSON.stringify(cart))

      // Update cart count
      updateCartCount()

      // Show success message
      showToast("success", "Added to Cart", `${product.name} has been added to your cart.`)
    })
    .catch((error) => {
      console.error("Error adding to cart:", error)
      showToast("error", "Error", "Failed to add product to cart")
    })
}

function loadCart() {
  const cartItemsContainer = document.getElementById("cart-items")
  const cartTotalElement = document.getElementById("cart-total")

  if (!cartItemsContainer || !cartTotalElement) return

  // Get cart from localStorage
  const cart = JSON.parse(localStorage.getItem("cart")) || []

  if (cart.length === 0) {
    cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <a href="products.html" class="btn">Start Shopping</a>
            </div>
        `
    cartTotalElement.textContent = "£0.00"
    return
  }

  // Calculate total
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0)

  // Render cart items
  cartItemsContainer.innerHTML = ""
  cart.forEach((item) => {
    const itemTotal = (item.price * item.quantity).toFixed(2)

    cartItemsContainer.innerHTML += `
            <div class="cart-item" data-id="${item.id}">
                <img src="images/${item.image}" alt="${item.name}">
                <div class="cart-item-info">
                    <h3>${item.name}</h3>
                    <div class="price">£${Number.parseFloat(item.price).toFixed(2)}</div>
                    <div class="item-total">Total: £${itemTotal}</div>
                </div>
                <div class="cart-item-actions">
                    <div class="quantity-control">
                        <button class="quantity-btn decrease">-</button>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="99">
                        <button class="quantity-btn increase">+</button>
                    </div>
                    <button class="remove-item">Remove</button>
                </div>
            </div>
        `
  })

  // Update total
  cartTotalElement.textContent = `£${total.toFixed(2)}`

  // Setup quantity controls
  setupCartControls()
}

function setupCartControls() {
  // Quantity increase/decrease
  document.querySelectorAll(".quantity-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const cartItem = this.closest(".cart-item")
      const productId = cartItem.dataset.id
      const quantityInput = cartItem.querySelector(".quantity-input")
      let quantity = Number.parseInt(quantityInput.value)

      if (this.classList.contains("decrease")) {
        quantity = Math.max(1, quantity - 1)
      } else {
        quantity = Math.min(99, quantity + 1)
      }

      quantityInput.value = quantity
      updateCartItemQuantity(productId, quantity)
    })
  })

  // Quantity input change
  document.querySelectorAll(".quantity-input").forEach((input) => {
    input.addEventListener("change", function () {
      const cartItem = this.closest(".cart-item")
      const productId = cartItem.dataset.id
      let quantity = Number.parseInt(this.value)

      // Validate quantity
      if (isNaN(quantity) || quantity < 1) {
        quantity = 1
      } else if (quantity > 99) {
        quantity = 99
      }

      this.value = quantity
      updateCartItemQuantity(productId, quantity)
    })
  })

  // Remove item
  document.querySelectorAll(".remove-item").forEach((button) => {
    button.addEventListener("click", function () {
      const cartItem = this.closest(".cart-item")
      const productId = cartItem.dataset.id
      removeCartItem(productId)
    })
  })
}

function updateCartItemQuantity(productId, quantity) {
  // Get current cart
  const cart = JSON.parse(localStorage.getItem("cart")) || []

  // Find the item
  const itemIndex = cart.findIndex((item) => item.id == productId)

  if (itemIndex !== -1) {
    // Update quantity
    cart[itemIndex].quantity = quantity

    // Save updated cart
    localStorage.setItem("cart", JSON.stringify(cart))

    // Reload cart display
    loadCart()

    // Update cart count
    updateCartCount()
  }
}

function removeCartItem(productId) {
  // Get current cart
  let cart = JSON.parse(localStorage.getItem("cart")) || []

  // Remove the item
  cart = cart.filter((item) => item.id != productId)

  // Save updated cart
  localStorage.setItem("cart", JSON.stringify(cart))

  // Reload cart display
  loadCart()

  // Update cart count
  updateCartCount()

  // Show toast
  showToast("success", "Removed", "Item removed from cart")
}

// Product Filters
function setupFilters() {
  const filterForm = document.getElementById("filter-form")

  filterForm.addEventListener("submit", (e) => {
    e.preventDefault()

    // Get filter values
    const category = document.getElementById("category").value
    const priceMin = document.getElementById("price-min").value
    const priceMax = document.getElementById("price-max").value
    const search = document.getElementById("search").value
    const ageMin = document.getElementById("age-min")?.value
    const ageMax = document.getElementById("age-max")?.value
    const condition = document.getElementById("condition")?.value

    // Build filters object
    const filters = {}
    if (category) filters.category = category
    if (priceMin) filters.price_min = priceMin
    if (priceMax) filters.price_max = priceMax
    if (search) filters.search = search
    if (ageMin) filters.age_min = ageMin
    if (ageMax) filters.age_max = ageMax
    if (condition) filters.condition = condition

    // Load products with filters
    loadProducts(filters)

    // Update URL with filters for bookmarking/sharing
    updateURLWithFilters(filters)
  })
}

function loadProducts(filters = {}) {
  const productGrid = document.getElementById("product-grid")
  if (!productGrid) return

  // Show loading
  productGrid.innerHTML = '<div class="loading">Loading products...</div>'

  // Build query string from filters
  const queryParams = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value) queryParams.append(key, value)
  })

  // Fetch products with filters
  fetch(`php/products.php?${queryParams.toString()}`)
    .then((response) => response.json())
    .then((products) => {
      if (products.length === 0) {
        productGrid.innerHTML = `
                    <div class="no-results">
                        <p>No products found matching your criteria.</p>
                        <button class="btn" onclick="resetFilters()">Reset Filters</button>
                    </div>
                `
        return
      }

      // Render products
      productGrid.innerHTML = ""
      products.forEach((product) => {
        const isNew = isNewProduct(product.created_at)
        const isLowStock = product.stock <= 3

        productGrid.innerHTML += `
                    <div class="product" data-id="${product.id}">
                        <div class="product-image-container">
                            ${isNew ? '<span class="product-badge new-badge">New</span>' : ""}
                            ${isLowStock ? '<span class="product-badge stock-badge">Low Stock</span>' : ""}
                            <img src="images/${product.image}" alt="${product.name}">
                            <button class="wishlist-icon" data-id="${product.id}">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h3>${product.name}</h3>
                            <div class="condition">${product.condition}</div>
                            <div class="price">£${Number.parseFloat(product.price).toFixed(2)}</div>
                            <div class="product-actions">
                                <button class="btn view-product" data-id="${product.id}">View Details</button>
                                <button class="btn btn-accent add-to-cart" data-id="${product.id}">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `
      })

      // Setup product interactions
      setupProductInteractions()
    })
    .catch((error) => {
      console.error("Error loading products:", error)
      productGrid.innerHTML = '<div class="error">Error loading products. Please try again later.</div>'
    })
}

function updateURLWithFilters(filters) {
  // Build query string from filters
  const queryParams = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value) queryParams.append(key, value)
  })

  // Update URL without reloading page
  const newURL = `${window.location.pathname}?${queryParams.toString()}`
  history.pushState({}, "", newURL)
}

function resetFilters() {
  // Reset form inputs
  document.getElementById("category").value = ""
  document.getElementById("price-min").value = ""
  document.getElementById("price-max").value = ""
  document.getElementById("search").value = ""

  if (document.getElementById("age-min")) {
    document.getElementById("age-min").value = ""
  }

  if (document.getElementById("age-max")) {
    document.getElementById("age-max").value = ""
  }

  if (document.getElementById("condition")) {
    document.getElementById("condition").value = ""
  }

  // Load all products
  loadProducts()

  // Update URL
  history.pushState({}, "", window.location.pathname)
}

function isNewProduct(createdAt) {
  const productDate = new Date(createdAt)
  const now = new Date()
  const daysDifference = Math.floor((now - productDate) / (1000 * 60 * 60 * 24))
  return daysDifference <= 7 // Consider products added in the last 7 days as new
}

function setupProductInteractions() {
  // View product details
  document.querySelectorAll(".view-product").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.dataset.id
      showProductDetail(productId)
    })
  })

  // Add to cart
  document.querySelectorAll(".add-to-cart").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.dataset.id
      addToCart(productId)
    })
  })

  // Wishlist toggle
  document.querySelectorAll(".wishlist-icon").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.stopPropagation()
      const productId = this.dataset.id
      toggleWishlist(productId, this)
    })

    // Check if product is in wishlist
    const productId = button.dataset.id
    const wishlist = JSON.parse(localStorage.getItem("wishlist")) || []
    if (wishlist.includes(productId)) {
      button.classList.add("active")
      button.innerHTML = '<i class="fas fa-heart"></i>'
    }
  })
}

// Product Detail
function showProductDetail(productId) {
  // Add to recently viewed
  addToRecentlyViewed(productId)

  // Redirect to product detail page
  window.location.href = `product-detail.html?id=${productId}`
}

// Wishlist Functions
function setupWishlist() {
  // Initialize wishlist in localStorage if it doesn't exist
  if (!localStorage.getItem("wishlist")) {
    localStorage.setItem("wishlist", JSON.stringify([]))
  }
}

function toggleWishlist(productId, button) {
  let wishlist = JSON.parse(localStorage.getItem("wishlist")) || []

  if (wishlist.includes(productId)) {
    // Remove from wishlist
    wishlist = wishlist.filter((id) => id !== productId)
    button.classList.remove("active")
    button.innerHTML = '<i class="far fa-heart"></i>'
    showToast("success", "Removed", "Product removed from wishlist")
  } else {
    // Add to wishlist
    wishlist.push(productId)
    button.classList.add("active")
    button.innerHTML = '<i class="fas fa-heart"></i>'
    showToast("success", "Added", "Product added to wishlist")
  }

  localStorage.setItem("wishlist", JSON.stringify(wishlist))
}

// Age Range Slider
function setupAgeRangeSlider() {
  const slider = document.querySelector(".age-range-slider")
  const minHandle = slider.querySelector(".min-handle")
  const maxHandle = slider.querySelector(".max-handle")
  const sliderFill = slider.querySelector(".slider-fill")
  const minValue = slider.querySelector(".min-value")
  const maxValue = slider.querySelector(".max-value")
  const minInput = document.getElementById("age-min")
  const maxInput = document.getElementById("age-max")

  const sliderWidth = slider.querySelector(".slider").offsetWidth
  const handleWidth = minHandle.offsetWidth

  // Min and max age values
  const minAge = 0
  const maxAge = 12

  // Set initial positions
  let minPos = 0
  let maxPos = sliderWidth

  // Update slider visuals
  function updateSlider() {
    // Calculate positions as percentage of slider width
    const minPercent = (minPos / sliderWidth) * 100
    const maxPercent = (maxPos / sliderWidth) * 100

    // Update fill position
    sliderFill.style.left = `${minPercent}%`
    sliderFill.style.width = `${maxPercent - minPercent}%`

    // Update handle positions
    minHandle.style.left = `${minPercent}%`
    maxHandle.style.left = `${maxPercent}%`

    // Calculate and update values
    const minAgeValue = Math.round((minPercent / 100) * (maxAge - minAge) + minAge)
    const maxAgeValue = Math.round((maxPercent / 100) * (maxAge - minAge) + minAge)

    minValue.textContent = minAgeValue
    maxValue.textContent = maxAgeValue

    // Update hidden inputs
    minInput.value = minAgeValue
    maxInput.value = maxAgeValue
  }

  // Handle drag events for min handle
  minHandle.addEventListener("mousedown", (e) => {
    e.preventDefault()

    function moveHandler(e) {
      const sliderRect = slider.querySelector(".slider").getBoundingClientRect()
      let newPos = e.clientX - sliderRect.left

      // Constrain position
      newPos = Math.max(0, Math.min(newPos, maxPos - handleWidth))

      minPos = newPos
      updateSlider()
    }

    function upHandler() {
      document.removeEventListener("mousemove", moveHandler)
      document.removeEventListener("mouseup", upHandler)
    }

    document.addEventListener("mousemove", moveHandler)
    document.addEventListener("mouseup", upHandler)
  })

  // Handle drag events for max handle
  maxHandle.addEventListener("mousedown", (e) => {
    e.preventDefault()

    function moveHandler(e) {
      const sliderRect = slider.querySelector(".slider").getBoundingClientRect()
      let newPos = e.clientX - sliderRect.left

      // Constrain position
      newPos = Math.max(minPos + handleWidth, Math.min(newPos, sliderWidth))

      maxPos = newPos
      updateSlider()
    }

    function upHandler() {
      document.removeEventListener("mousemove", moveHandler)
      document.removeEventListener("mouseup", upHandler)
    }

    document.addEventListener("mousemove", moveHandler)
    document.addEventListener("mouseup", upHandler)
  })

  // Initialize slider
  updateSlider()
}

// Product Gallery
function setupProductGallery() {
  const mainImage = document.querySelector(".gallery-main img")
  const thumbnails = document.querySelectorAll(".gallery-thumbnail")

  thumbnails.forEach((thumbnail) => {
    thumbnail.addEventListener("click", function () {
      // Update main image
      mainImage.src = this.querySelector("img").src

      // Update active thumbnail
      thumbnails.forEach((t) => t.classList.remove("active"))
      this.classList.add("active")
    })
  })
}

// Recently Viewed Products
function trackRecentlyViewed() {
  // Get product ID from URL if on product detail page
  const urlParams = new URLSearchParams(window.location.search)
  const productId = urlParams.get("id")

  if (productId) {
    addToRecentlyViewed(productId)
  }

  // Load recently viewed products if container exists
  const recentlyViewedContainer = document.getElementById("recently-viewed-products")
  if (recentlyViewedContainer) {
    loadRecentlyViewed(recentlyViewedContainer)
  }
}

function addToRecentlyViewed(productId) {
  let recentlyViewed = JSON.parse(localStorage.getItem("recentlyViewed")) || []

  // Remove if already in list
  recentlyViewed = recentlyViewed.filter((id) => id !== productId)

  // Add to beginning of list
  recentlyViewed.unshift(productId)

  // Keep only the last 6 items
  recentlyViewed = recentlyViewed.slice(0, 6)

  // Save to localStorage
  localStorage.setItem("recentlyViewed", JSON.stringify(recentlyViewed))
}

function loadRecentlyViewed(container) {
  const recentlyViewed = JSON.parse(localStorage.getItem("recentlyViewed")) || []

  if (recentlyViewed.length === 0) {
    container.style.display = "none"
    return
  }

  // Get current product ID to exclude from recently viewed
  const urlParams = new URLSearchParams(window.location.search)
  const currentProductId = urlParams.get("id")

  // Filter out current product
  const filteredIds = recentlyViewed.filter((id) => id !== currentProductId)

  if (filteredIds.length === 0) {
    container.style.display = "none"
    return
  }

  // Fetch products by IDs
  const productsGrid = container.querySelector(".grid")
  productsGrid.innerHTML = '<div class="loading">Loading recently viewed products...</div>'

  // Build query string with IDs
  const queryParams = new URLSearchParams()
  queryParams.append("ids", filteredIds.join(","))

  fetch(`php/products.php?${queryParams.toString()}`)
    .then((response) => response.json())
    .then((products) => {
      if (products.length === 0) {
        container.style.display = "none"
        return
      }

      // Render products
      productsGrid.innerHTML = ""
      products.forEach((product) => {
        productsGrid.innerHTML += `
                    <div class="product" data-id="${product.id}">
                        <div class="product-image-container">
                            <img src="images/${product.image}" alt="${product.name}">
                            <button class="wishlist-icon" data-id="${product.id}">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h3>${product.name}</h3>
                            <div class="price">£${Number.parseFloat(product.price).toFixed(2)}</div>
                            <button class="btn view-product" data-id="${product.id}">View Details</button>
                        </div>
                    </div>
                `
      })

      // Setup product interactions
      setupProductInteractions()
    })
    .catch((error) => {
      console.error("Error loading recently viewed products:", error)
      container.style.display = "none"
    })
}
