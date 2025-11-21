let selectedTableId;
let selectedTableNumber;
let cart = [];

// Filtering menu items
function filterCategory(category) {
  const categoryButtons = document.querySelectorAll(".category-btn");
  categoryButtons.forEach((button) => {
    button.classList.remove("active");
  });
  event.target.classList.add("active");

  const menuItems = document.querySelectorAll(".menu-item");
  menuItems.forEach((item) => {
    if (item.dataset.category === category || category === "all") {
      item.style.display = "block";
    } else {
      item.style.display = "none";
    }
  });
}

// Selecting a table number
function selectTable(tableId, tableNumber) {
  selectedTableId = tableId;
  selectedTableNumber = tableNumber;

  const tableButtons = document.querySelectorAll(".table-btn");

  tableButtons.forEach((button) => {
    button.classList.remove("selected");
  });
  event.target.classList.add("selected");

  document.getElementById("selectedTable").style.display = "block";
  document.getElementById("tableDisplay").textContent =
    "Table " + selectedTableNumber;

  updateCartView();
}

// Adding a menu item to cart
function addToCart(item) {
  // check if item is already in cart
  const existingItem = cart.find((cartItem) => cartItem.id === item.id);

  // if so only update the quantity;
  if (existingItem) {
    existingItem.quantity++;
  } else {
    cart.push({ ...item, quantity: 1 });
  }

  // update the cart
  updateCartView();
}

// Removing a menu item from cart
function removeFromCart(itemId) {
  cart = cart.filter((item) => Number(item.id) !== Number(itemId));

  updateCartView();
}

// Updating the quantity of a menu item
function updateQuantity(itemId, amountChange) {
  const item = cart.find((item) => Number(item.id) === Number(itemId));

  if (item) {
    item.quantity += amountChange;

    if (item.quantity <= 0) {
      removeFromCart(itemId);
    } else {
      updateCartView();
    }
  }
}

// Removing the items from cart
function clearCart() {
  if (confirm("Do you want to clear the cart?")) {
    cart = [];
    updateCartView();
  }
}

//
function updateCartView() {
  const cartItems = document.getElementById("cartItems");
  const cartTotal = document.getElementById("cartTotal");
  const orderButton = document.getElementById("placeOrderBtn");

  if (cart.length === 0) {
    cartItems.innerHTML =
      '<p style="color: #666; text-align: center;">Cart is empty</p>';
    cartTotal.textContent = "$0.00";
    orderButton.disabled = true;
  } else {
    let html = "";
    let total = 0;

    cart.forEach((item) => {
      const itemTotal = item.price * item.quantity;
      total += itemTotal;

      html += `
        <div class="cart-item">
          <div class="cart-item-info">
            <div class="cart-item-name">${item.name}</div>
            <div class="cart-item-price">
              $${item.price} × ${item.quantity} = $${itemTotal.toFixed(2)}
            </div>
          </div>
          <div class="cart-item-controls">
            <button class="qty-btn" onclick="updateQuantity(${
              item.id
            }, -1)">-</button>
            <span>${item.quantity}</span>
            <button class="qty-btn" onclick="updateQuantity(${
              item.id
            }, 1)">+</button>
          </div>
        </div>
      `;
    });

    cartItems.innerHTML = html;
    cartTotal.textContent = "$" + total.toFixed(2);
    orderButton.disabled = !selectedTableId;
  }
}

// Placing an order
async function placeOrder() {
  // check cart is not empty and table is selected
  if (!selectedTableId || cart.length === 0) {
    alert("Please select a table and add items to the cart.");
    return;
  }

  // create a form element
  const formData = new FormData();
  formData.append("place_order", "1");
  formData.append("table_id", selectedTableId);
  formData.append("cart", JSON.stringify(cart));

  // make a post request to the server to create the order
  try {
    const response = await fetch("waiter_interface.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    // clear the cart on success && de-select the table
    if (result.success) {
      alert(`Order #${result.order_id} placed successfully.`);

      cart = [];
      selectedTableId = null;
      selectedTableNumber = null;

      // remove selected class from all table buttons
      const tableButtons = document.querySelectorAll(".table-btn");
      tableButtons.forEach((button) => {
        button.classList.remove("selected");
      });
      document.getElementById("selectedTable").style.display = "none";
      updateCartView();

      // reload the page
      setTimeout(() => location.reload(), 1000);
    } else {
      alert("Failed to place order.");
    }
  } catch (error) {
    console.error("Error: ", error);
    alert("Failed to place order.");
  }
}

// Auto reload page to get orders
// setInterval(() => {
//   window.location.reload();
// }, 30000);
