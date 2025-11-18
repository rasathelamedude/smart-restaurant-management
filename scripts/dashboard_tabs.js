// Change the tabs on the dashboard
function switchTab(tab) {
  // remove active class from all buttons
  const tabs = document.querySelectorAll(".tab");
  tabs.forEach((t) => t.classList.remove("active"));

  // remove active class from all content containers
  const contentContainers = document.querySelectorAll(".tab-content");
  contentContainers.forEach((c) => c.classList.remove("active"));

  // Add active class to the button that was clicked
  event.target.classList.add("active");

  // Add active class to the selected content container
  document.getElementById(tab + "-tab").classList.add("active");
}

// Display form for adding a menu item
function openAddItemModal() {
  document.getElementById("itemModalTitle").textContent = "Add Menu Item"; // set title
  document.getElementById("itemAction").value = "add_item"; // set action

  // sets default values
  document.getElementById("itemId").value = "";
  document.getElementById("itemName").value = "";
  document.getElementById("itemDescription").value = "";
  document.getElementById("itemPrice").value = "";
  document.getElementById("itemCategory").value = "Appetizer";
  document.getElementById("itemAvailable").checked = true;

  document.getElementById("itemModal").classList.add("active"); // Show the modal
}

// Display form for editing a menu item
function openEditItemModal(item) {
  document.getElementById("itemModalTitle").textContent = "Edit Menu Item"; // set title
  document.getElementById("itemAction").value = "update_item"; // set action

  // populate fields with item data
  document.getElementById("itemId").value = item.id;
  document.getElementById("itemName").value = item.name;
  document.getElementById("itemDescription").value = item.description;
  document.getElementById("itemPrice").value = item.price;
  document.getElementById("itemCategory").value = item.category;
  document.getElementById("itemAvailable").checked = item.available == 1;

  document.getElementById("itemModal").classList.add("active"); // Show the modal
}

// Close the form for adding/editing menu items
function closeItemModal() {
  document.getElementById("itemModal").classList.remove("active");
}

// Display form for adding a user
function openAddUserModal() {
  document.getElementById("userModal").classList.add("active");
}

// Close the form for adding a user
function closeUserModal() {
  document.getElementById("userModal").classList.remove("active");
}

window.onclick = function (event) {
  if (event.target.classList.contains("modal")) {
    event.target.classList.remove("active");
  }
};
