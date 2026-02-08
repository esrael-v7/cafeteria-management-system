/* ================= GLOBAL STATE ================= */
let cart = [];
let orders = [];
let orderHistory = [];
let currentPage = "home";
let currentRole = null;
let currentUser = null;
let menuItems = []; // loaded from DB via getMenu.php

// DOM Elements (will be initialized when DOM is ready)
let authUser, authPass, roleSelect, authTitle, authSubtitle;
let loginBtnAuth, registerBtnAuth, loginSwitch;
let loginBtn, logoutBtn;
let saved = null;

// Initialize DOM references when page loads
document.addEventListener("DOMContentLoaded", () => {
  authUser = document.getElementById("authUser");
  authPass = document.getElementById("authPass");
  roleSelect = document.getElementById("roleSelect");
  authTitle = document.getElementById("authTitle");
  authSubtitle = document.getElementById("authSubtitle");
  loginBtnAuth = document.getElementById("loginBtnAuth");
  registerBtnAuth = document.getElementById("registerBtnAuth");
  loginSwitch = document.getElementById("loginSwitch");
  loginBtn = document.getElementById("loginBtn");
  logoutBtn = document.getElementById("logoutBtn");
  
  // Get saved session from localStorage
  const savedStr = localStorage.getItem("cafeteria_session");
  if (savedStr) {
    try {
      saved = JSON.parse(savedStr);
    } catch (e) {
      saved = null;
    }
  }
  
  // Load menu first, then initialize pages
  loadMenu()
    .catch(err => console.error("Menu load error:", err))
    .finally(() => {
      showPage("home");
      updateCartCount();
    });

  const searchInput = document.getElementById("searchInput");
  if(searchInput){
    searchInput.addEventListener("keydown", function(e){
      if(e.key === "Enter"){ e.preventDefault(); handleSearch(this.value); }
    });
  }

  if(saved){
    currentRole = saved.role;
    currentUser = saved.username;
    closeAuth();
    hideAllPages();
    if (loginBtn) loginBtn.style.display = "none";
    if (logoutBtn) logoutBtn.style.display = "inline";
    showPage(saved.role === "admin" ? "admin" : "home");
  } else {
    openLogin();
  }
});

/* ================= MENU API (DB) ================= */
async function loadMenu() {
  const res = await fetch("getMenu.php?active=1");
  if (!res.ok) throw new Error("Failed to load menu");
  const data = await res.json();
  if (!data.success) throw new Error(data.message || "Menu API error");

  // Normalize DB fields to the same shape the UI expects
  menuItems = (data.items || []).map(it => ({
    id: Number(it.id),
    category: it.category,
    name: it.name,
    price: Number(it.price),
    desc: it.description || "",
    img: it.image_path || ""
  }));
}

/* ================= PAGE NAVIGATION ================= */
function showPage(pageId) {

  // allow navigation if not logged in yet
  if (!currentRole && pageId !== "home") return;

  if (currentRole === "admin" && pageId !== "admin") {
    alert("Admins can only access admin page");
    return;
  }

  if (currentRole === "customer" && pageId === "admin") {
    alert("Access denied");
    return;
  }

  document.querySelectorAll(".page").forEach(p =>
    p.classList.remove("active")
  );

  const page = document.getElementById(pageId);
  if (page) {
    page.classList.add("active");
    currentPage = pageId;
  }

  if (pageId === "cart") renderCart();
  if (pageId === "admin") {
  renderAdminMenu();
  renderAdminOrders();
  setTimeout(updateAdminDashboard, 50);
}

  if (pageId === "kitchen") renderKitchenOrders();
  if (pageId === "history") renderOrderHistory();

  renderMenuByCategory("regular", "regularFoods");
  renderMenuByCategory("fast", "fastFoods");
  renderMenuByCategory("dessert", "desserts");
  renderMenuByCategory("hot", "hotDrinks");
  renderMenuByCategory("cold", "coldDrinks");
}



/* ================= MENU RENDER ================= */
function renderMenuByCategory(category, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";

  menuItems
    .filter(item => item.category === category)
    .forEach(item => {
      const div = document.createElement("div");
      div.className = "menu-item";
      div.innerHTML = `
        <img src="${item.img}" alt="${item.name}">
        <h4>${item.name}</h4>
        <p>${item.desc}</p>
        <p><strong>${item.price} ETB</strong></p>
        <button onclick="addToCart(${item.id})">Add to Cart</button>
      `;
      container.appendChild(div);
    });
}

/* ================= CART ================= */
function addToCart(id) {
  const item = menuItems.find(i => i.id === id);
  if (!item) return;

  const existing = cart.find(c => c.id === id);
  if (existing) existing.qty++;
  else cart.push({ ...item, qty: 1 });

  updateCartCount();
  renderCart();
}

function updateCartCount() {
  const cartCountEl = document.getElementById("cartCount");
  if (!cartCountEl) return;

  cartCountEl.innerText = cart.reduce((sum, i) => sum + i.qty, 0);
}
function renderCart() {
  const cartItems = document.getElementById("cartItems");
  if (!cartItems) return;

  cartItems.innerHTML = "";
  cart.forEach(item => {
    const div = document.createElement("div");
    div.className = "cart-item";
    div.innerHTML = `
      ${item.name} 
      <button onclick="changeQty(${item.id}, -1)">−</button>
      ${item.qty}
      <button onclick="changeQty(${item.id}, 1)">+</button>
      <button onclick="removeItem(${item.id})">❌</button>
      <span style="float:right">${(item.price * item.qty).toFixed(2)} ETB</span>
    `;
    cartItems.appendChild(div);
  });

  updateCartTotals();
}

/* ================= CHECKOUT ================= */
function placeOrder() {
  if(cart.length === 0){ alert("Cart empty"); return; }

  const payment = document.querySelector('input[name="payment"]:checked')?.value;
  if(!payment){ alert("Select payment method"); return; }

  if(!currentUser) {
    alert("Please login first");
    return;
  }

  // Send only IDs + qty to the server; server uses DB prices (prevents tampering)
  const orderData = {
    username: currentUser,
    items: cart.map(i => ({ id: i.id, qty: i.qty })),
    payment
  };

  fetch("placeOrder.php", {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body: JSON.stringify(orderData)
  })
  .then(res => {
    if (!res.ok) {
      throw new Error("Network error");
    }
    return res.json();
  })
  .then(data=>{
    if(!data.success){ 
      alert(data.message); 
      return; 
    }
    cart = []; 
    updateCartCount(); 
    renderCart();
    alert(`Order confirmed! Total: ${data.total.toFixed(2)} ETB`);
    showPage("home");
  })
  .catch(err=>{
    console.error("Order error:", err);
    alert("Order failed. Check console for details.");
  });
}


/* ================= KITCHEN ================= */
function renderKitchenOrders() {
  fetch("getOrders.php")
    .then(res=>res.json())
    .then(data=>{
      const kitchenList = document.getElementById("kitchenOrders");
      if (!kitchenList) return;
      kitchenList.innerHTML = "";

      data.orders.filter(o=>o.status==="Pending").forEach(order=>{
        const div = document.createElement("div");
        div.className="item-card";
        div.innerHTML=`
          <h4>Order #${order.id}</h4>
          <p>${order.items.map(i=>`${i.name} x ${i.qty}`).join(", ")}</p>
          <p>Status: <b>${order.status}</b></p>
          <button onclick="completeOrder(${order.id})">Mark Ready</button>
        `;
        kitchenList.appendChild(div);
      });
    });
}

function completeOrder(id){
  fetch("completeOrder.php", {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body: JSON.stringify({orderId:id})
  })
  .then(res=>res.json())
  .then(()=>renderKitchenOrders());
}


/* ================= ADMIN ================= */
function renderAdminMenu() {
  const adminList = document.getElementById("adminMenuList");
  if (!adminList) return;

  adminList.innerHTML = "<p>Loading menu...</p>";

  fetch("getMenu.php?active=0")
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        adminList.innerHTML = "<p>Failed to load menu.</p>";
        return;
      }

      const items = (data.items || []).map(it => ({
        id: Number(it.id),
        name: it.name,
        price: Number(it.price),
        category: it.category,
        active: Number(it.active ?? 1)
      }));

      adminList.innerHTML = "";
      if (items.length === 0) {
        adminList.innerHTML = "<p>No menu items.</p>";
        return;
      }

      items.forEach(item => {
        const div = document.createElement("div");
        div.innerHTML = `
          ${item.name} - ${item.price} ETB (${item.category})
          <button onclick="deleteMenuItem(${item.id})">Delete</button>
        `;
        adminList.appendChild(div);
      });
    })
    .catch(err => {
      console.error("Admin menu load error:", err);
      adminList.innerHTML = "<p>Error loading menu.</p>";
    });
}
function updateAdminDashboard() {
  fetch("getOrders.php")
    .then(res => res.json())
    .then(data => {
      const orders = data.orders || [];
      
      const totalOrdersEl = document.getElementById("totalOrders");
      const totalRevenueEl = document.getElementById("totalRevenue");
      const pendingOrdersEl = document.getElementById("pendingOrders");
      const completedOrdersEl = document.getElementById("completedOrders");

      if (!totalOrdersEl) return;

      const totalOrders = orders.length;
      const totalRevenue = orders.reduce((sum, o) => sum + (parseFloat(o.total) || 0), 0);
      const pendingOrders = orders.filter(o => o.status === "Pending").length;
      const completedOrders = orders.filter(o => o.status === "Ready" || o.status === "Completed").length;

      totalOrdersEl.innerHTML = `Total Orders Today<br><b>${totalOrders}</b>`;
      totalRevenueEl.innerHTML = `Total Revenue<br><b>${totalRevenue.toFixed(2)} ETB</b>`;
      pendingOrdersEl.innerHTML = `Pending Orders<br><b>${pendingOrders}</b>`;
      completedOrdersEl.innerHTML = `Completed Orders<br><b>${completedOrders}</b>`;
    })
    .catch(err => console.error("Dashboard error:", err));
}

function renderAdminOrders() {
  fetch("getOrders.php")
    .then(res => res.json())
    .then(data => {
      const adminOrdersEl = document.getElementById("adminOrders");
      if (!adminOrdersEl) return;
      
      adminOrdersEl.innerHTML = "";
      const orders = data.orders || [];
      
      if (orders.length === 0) {
        adminOrdersEl.innerHTML = "<p>No orders yet.</p>";
        return;
      }
      
      orders.forEach(order => {
        const div = document.createElement("div");
        div.className = "item-card";
        div.style.marginBottom = "15px";
        div.style.padding = "15px";
        
        const itemsList = order.items && order.items.length > 0 
          ? order.items.map(i => `${i.item_name} x ${i.quantity}`).join(", ")
          : "No items";
        
        div.innerHTML = `
          <h4>Order #${order.id}</h4>
          <p><strong>Customer:</strong> ${order.username}</p>
          <p><strong>Items:</strong> ${itemsList}</p>
          <p><strong>Total:</strong> ${parseFloat(order.total || 0).toFixed(2)} ETB</p>
          <p><strong>Payment:</strong> ${order.payment || 'N/A'}</p>
          <p><strong>Status:</strong> <span style="color: ${order.status === 'Pending' ? '#e74c3c' : '#27ae60'}; font-weight: bold;">${order.status}</span></p>
          <p><strong>Date:</strong> ${order.created_at || 'N/A'}</p>
          ${order.status === 'Pending' ? `<button onclick="updateOrderStatus(${order.id}, 'Ready')" style="margin-top: 10px; padding: 8px 15px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer;">Mark as Ready</button>` : ''}
          ${order.status === 'Ready' ? `<button onclick="updateOrderStatus(${order.id}, 'Completed')" style="margin-top: 10px; padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">Mark as Completed</button>` : ''}
        `;
        adminOrdersEl.appendChild(div);
      });
    })
    .catch(err => {
      console.error("Admin orders error:", err);
      const adminOrdersEl = document.getElementById("adminOrders");
      if (adminOrdersEl) {
        adminOrdersEl.innerHTML = "<p>Error loading orders.</p>";
      }
    });
}

function updateOrderStatus(orderId, newStatus) {
  fetch("completeOrder.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({orderId: orderId, status: newStatus})
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      renderAdminOrders();
      updateAdminDashboard();
    } else {
      alert(data.message || "Failed to update order status");
    }
  })
  .catch(err => {
    console.error("Update order error:", err);
    alert("Error updating order status");
  });
}




function addMenuItem() {
  const name = document.getElementById("newItemName")?.value;
  const price = document.getElementById("newItemPrice")?.value;
  if (!name || !price) return;

  fetch("addMenuItem.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({
      category: "regular",
      name,
      description: "New menu item",
      price: Number(price),
      image_path: ""
    })
  })
    .then(res => res.json())
    .then(async data => {
      if (!data.success) {
        alert(data.message || "Failed to add menu item");
        return;
      }
      await loadMenu().catch(()=>{});
      renderAdminMenu();
    })
    .catch(err => {
      console.error("Add menu item error:", err);
      alert("Error adding menu item");
    });
}

function deleteMenuItem(id) {
  fetch("deleteMenuItem.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({ id })
  })
    .then(res => res.json())
    .then(async data => {
      if (!data.success) {
        alert(data.message || "Failed to delete menu item");
        return;
      }
      await loadMenu().catch(()=>{});
      renderAdminMenu();
    })
    .catch(err => {
      console.error("Delete menu item error:", err);
      alert("Error deleting menu item");
    });
}

/* ================= ORDER HISTORY ================= */
function renderOrderHistory(){
  fetch(`getOrders.php?user=${currentUser}`)
    .then(res=>res.json())
    .then(data=>{
      const div = document.getElementById("orderHistory");
      if(!div) return;
      div.innerHTML="";
      data.orders.forEach(order=>{
        const card=document.createElement("div");
        card.className="item-card";
        card.innerHTML=`
          <h4>Order #${order.id}</h4>
          <p>${order.created_at}</p>
          <p>Total: ${order.total} ETB</p>
          <p>Status: ${order.status}</p>
          <button onclick="printReceipt(${order.id})">Print Receipt</button>
        `;
        div.appendChild(card);
      });
    });
}
/* ================= RECEIPT ================= */
function printReceipt(id) {
  const order = orderHistory.find(o => o.id === id);
  if (!order) return;

  let receipt = `
CAFETERIA RECEIPT
--------------------
Order ID: ${order.id}
Time: ${order.time}

`;

  order.items.forEach(i => {
    receipt += `${i.name} x ${i.qty} = ${i.price * i.qty} ETB\n`;
  });

  receipt += `
--------------------
TOTAL: ${order.total} ETB
Thank you!
`;

  const w = window.open("", "", "width=400,height=600");
  w.document.write(`<pre>${receipt}</pre>`);
  w.print();
}

function updateCartTotals() {
  let subtotal = cart.reduce((sum, i) => sum + i.price * i.qty, 0);
  const tax = subtotal * 0.15;
  const total = subtotal + tax;

  document.getElementById("subtotal").innerText = subtotal.toFixed(2) + " ETB";
  document.getElementById("tax").innerText = tax.toFixed(2) + " ETB";
  document.getElementById("total").innerText = total.toFixed(2) + " ETB";
}

function changeQty(id, amount) {
  const item = cart.find(i => i.id === id);
  if (!item) return;

  item.qty += amount;
  if (item.qty <= 0) cart = cart.filter(i => i.id !== id);

  updateCartCount();
  renderCart();
}

function removeItem(id) {
  cart = cart.filter(i => i.id !== id);
  updateCartCount();
  renderCart();
}


/* ===== SUPER ADMIN (FIXED) ===== */
const ADMIN_USERNAME = "admin";
const ADMIN_PASSWORD = "admin123";


/* OPEN LOGIN */
function openLogin() {
  document.getElementById("authOverlay").classList.remove("hidden");
  document.body.classList.add("blurred");
  hideAllPages();
}



/* HIDE ALL PAGES */
function hideAllPages() {
  document.querySelectorAll(".page").forEach(p => p.classList.remove("active"));
}

function switchAuth(mode) {
  // 🔹 Get role dropdown
  const adminOption = roleSelect.querySelector('option[value="admin"]');

  // 🔐 ADMIN OPTION VISIBILITY
  if (mode === "register") {
    // Register page → ONLY CUSTOMER
    adminOption.style.display = "none";
    roleSelect.value = "customer";
  } else {
    // Login page → CUSTOMER + ADMIN
    adminOption.style.display = "block";
  }

  // 🚫 Prevent admin registration (extra safety)
  if (mode === "register" && roleSelect.value === "admin") {
    alert("Admins cannot register");
    roleSelect.value = "customer";
    return;
  }

  // 📝 Titles
  authTitle.innerText =
    mode === "login" ? "Welcome Back" : "Create Customer Account";

  authSubtitle.innerText =
    mode === "login"
      ? "Login to your account"
      : "Register as a customer";

  // 🔘 Buttons
  loginBtnAuth.classList.toggle("hidden", mode === "register");
  registerBtnAuth.classList.toggle("hidden", mode === "login");

  // 🔄 Switch texts
  document.querySelector(".auth-switch").classList.toggle(
    "hidden",
    mode === "register"
  );

  loginSwitch.classList.toggle("hidden", mode === "login");
}


/* SUBMIT LOGIN */
/* LOGIN */
function submitAuth() {
  const username = authUser.value.trim();
  const password = authPass.value.trim();

  if(!username || !password){
    alert("Fill all fields");
    return;
  }

  fetch("login.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({username, password})
  })
  .then(res => res.json())
  .then(data => {
    if(!data.success){
      alert(data.message);
      return;
    }

    currentRole = data.user.role;
    currentUser = data.user.username;

    closeAuth();
    loginBtn.style.display = "none";
    logoutBtn.style.display = "inline";

    hideAllPages();
    showPage(currentRole === "admin" ? "admin" : "home");
  })
  .catch(err => console.log(err));
}



function logout() {
  currentRole = null;
  logoutBtn.style.display = "none";
  loginBtn.style.display = "inline";
  hideAllPages();
  openLogin();
}


function closeAuth() {
  document.getElementById("authOverlay").classList.add("hidden");
  document.body.classList.remove("blurred");
  authUser.value = "";
  authPass.value = "";
}

/* REGISTER */
function registerUser() {
  // Get elements directly as fallback
  const userInput = authUser || document.getElementById("authUser");
  const passInput = authPass || document.getElementById("authPass");

  if (!userInput || !passInput) {
    alert("Error: Form elements not loaded. Please refresh the page.");
    console.error("authUser or authPass is null");
    return;
  }

  const username = userInput.value.trim();
  const password = passInput.value.trim();

  if(!username || !password){
    alert("Fill all fields");
    return;
  }

  console.log("Attempting registration for:", username);

  fetch("register.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({username, password})
  })
  .then(res => {
    console.log("Response status:", res.status);
    if (!res.ok) {
      throw new Error("Network response was not ok: " + res.status);
    }
    return res.json();
  })
  .then(data => {
    console.log("Registration response:", data);
    alert(data.message);
    if(data.success){
      if (typeof switchAuth === 'function') {
        switchAuth("login");
      }
      userInput.value = "";
      passInput.value = "";
    }
  })
  .catch(err => {
    console.error("Registration error:", err);
    alert("Registration failed: " + err.message);
  });
}




function scrollToCategory(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: "smooth" });
}
/* ================= SEARCH FUNCTION ================= */
const searchInput = document.getElementById("searchInput");

if (searchInput) {
  searchInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      handleSearch(this.value);
    }
  });
}

function handleSearch(query) {
  if (!query.trim()) return;

  const searchText = query.toLowerCase();
  const foundItem = menuItems.find(item => item.name.toLowerCase().includes(searchText));
  if (!foundItem) { alert("Item not found"); return; }

  showPage("menu");

  const categoryMap = {
    regular: "regularFoods",
    fast: "fastFoods",
    dessert: "desserts",
    hot: "hotDrinks",
    cold: "coldDrinks"
  };
  const containerId = categoryMap[foundItem.category];

  setTimeout(() => scrollToCategory(containerId), 300);
}

/* ================= BEST SELLER NAV ================= */
function goToItem(name) {
  const item = menuItems.find(i => i.name.toLowerCase().includes(name.toLowerCase()));
  if (!item) return;

  showPage("menu");
  const categoryMap = {
    regular: "regularFoods",
    fast: "fastFoods",
    dessert: "desserts",
    hot: "hotDrinks",
    cold: "coldDrinks"
  };
  setTimeout(() => scrollToCategory(categoryMap[item.category]), 300);
}

function scrollToCategory(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: "smooth" });
}
