// Update order counts;
function updateCounts() {
  const pending = document.querySelectorAll(".order-card.pending").length;
  const preparing = document.querySelectorAll(".order-card.preparing").length;

  document.getElementById("pendingCount").textContent = pending;
  document.getElementById("preparingCount").textContent = preparing;
}

async function updateStatus(order_id, new_status) {
  const form = new FormData();
  form.append("order_id", order_id);
  form.append("new_status", new_status);

  try {
    const response = await fetch("kitchen_display.php", {
      method: "POST",
      body: form,
    });

    const result = await response.json();

    if (result.success) {
      // Remove order card
      const card = document.querySelector(`[data-order-id="${order_id}"]`);
      card.style.opacity = "0";
      card.style.transform = "scale(0.8)";

      // Refresh after the animations
      setTimeout(() => {
        location.reload;
      }, 300);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Failed to update order status");
  }
}

updateCounts();

// Auto-refresh every 15 seconds
setInterval(() => {
  location.reload();
}, 15000);
