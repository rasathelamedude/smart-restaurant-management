<?php
require_once('config.php');

// Check if user is a waiter
requireRole('waiter');

// Create a database connection
$connection = getConnection();

// Listen for post requests for when waiter orders
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $cart = json_decode($_POST['cart'], true);
    $waiter_id = $_SESSION['user_id'];
    $table_id = $_POST['table_id'];
    $csrf_token = $_POST['csrf_token'];

    // Check if the CSRF token is valid
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $csrf_token) {
        die('Invalid CSRF token. Cannot perform action.');
    }

    if (!empty($cart)) {
        // Create order
        $statement = $connection->prepare("INSERT INTO orders (table_id, waiter_id, status, total_amount) VALUES (?, ?, 'pending', 0)");
        $statement->bind_param('ii', $table_id, $waiter_id);
        $statement->execute();
        $order_id = $connection->insert_id;
        $statement->close();

        // Add order_items that was ordered and calculate total
        $total_amount = 0.00;
        foreach ($cart as $item) {
            $statement = $connection->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");

            $statement->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);

            $statement->execute();
            $statement->close();

            $total_amount += $item['quantity'] * $item['price'];
        }

        // Update order total
        $statement = $connection->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
        $statement->bind_param("di", $total_amount, $order_id);
        $statement->execute();
        $statement->close();

        // Update the table's status
        $statement = $connection->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $statement->bind_param("i", $table_id);
        $statement->execute();
        $statement->close();

        echo json_encode(['success' => true, 'order_id' => $order_id]);
        exit();
    }
}

$menu_items = $connection->query("SELECT * FROM menu_items ORDER BY category, name");

$tables = $connection->query("SELECT * FROM tables ORDER BY table_number");

$orders_statement = $connection->prepare(
    "SELECT orders.*, tables.table_number
            FROM orders
            JOIN tables ON orders.table_id = tables.id
            WHERE orders.waiter_id = ?
            AND DATE(orders.order_time) = CURDATE()
            ORDER BY orders.order_time DESC"
);

$orders_statement->bind_param('i', $_SESSION['user_id']);
$orders_statement->execute();
$my_orders = $orders_statement->get_result();
$orders_statement->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles/waiter_interface.css">
    <title>Waiter inter</title>
</head>

<body>
    <div class="header">
        <h1>Waiter interface</h1>
        <div class="user-info">
            <span>Welcome back <?php echo htmlspecialchars($_SESSION['username']) ?></span>
            <a class="btn-logout" href="logout.php">Logout</a>
        </div>
    </div>

    <!-- Page container -->
    <div class="container">
        <!-- Grid content -->
        <div class="main-content">
            <!-- Tables -->
            <div class="card">
                <h2>Select Table</h2>
                <div class="table-selector">
                    <?php while ($table = $tables->fetch_assoc()): ?>
                        <button class="table-btn <?php echo $table['status'] === 'occupied' ? "occupied" : "" ?>"
                            onclick="selectTable(<?php echo $table['id'] ?>, <?php echo $table['table_number'] ?>)"
                            data-table-id="<?php echo $table['id'] ?>">
                            Table <?php echo $table['table_number'] ?>
                        </button>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Menu items -->
            <div class="card">
                <h2>Menu</h2>
                <div class="menu-categories">
                    <button class="category-btn active" onclick="filterCategory('all')">All</button>
                    <button class="category-btn" onclick="filterCategory('Appetizer')">Appetizers</button>
                    <button class="category-btn" onclick="filterCategory('Main Dish')">Main Dish</button>
                    <button class="category-btn" onclick="filterCategory('Dessert')">Desserts</button>
                    <button class="category-btn" onclick="filterCategory('Beverage')">Beverages</button>
                </div>

                <div class="menu-grid">
                    <?php while ($item = $menu_items->fetch_assoc()): ?>
                        <div class="menu-item" data-category="<?php echo $item['category'] ?>"
                            onclick='addToCart(<?php echo json_encode($item) ?>)'>
                            <h3><?php echo htmlspecialchars($item['name']) ?></h3>
                            <p class="description"><?php echo htmlspecialchars($item['description']) ?></p>
                            <div class="price">
                                $<?php echo number_format($item['price'], 2) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Current orders for the logged in waiter -->
            <div class="card">
                <h2>My Orders Today</h2>
                <div class="orders-list">
                    <?php if ($my_orders->num_rows > 0): ?>
                        <?php while ($order = $my_orders->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <span class="order-id">
                                        Order: #<?php echo $order['id'] ?> - Table
                                        <?php echo $order['table_number']; ?>
                                    </span>
                                    <span class="status-badge status-<?php echo $order['status'] ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    Amount: $<?php echo number_format($order['total_amount'], 2) ?>
                                </div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">
                                    <?php echo date('H:i', strtotime($order['order_time'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #666; text-align: center;">No orders yet today</p>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- Sidebar with cart details -->
        <div class="sidebar">
            <!-- Cart -->
            <div class="cart">
                <h2>Order Cart</h2>

                <div id="selectedTable" class="alert alert-warning" style="display: none;">
                    Selected: <strong id="tableDisplay"></strong>
                </div>

                <div class="cart-items" id="cartItems">
                    <p style="color: #666; text-align: center;">Cart is empty</p>
                </div>

                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cartTotal">$0.00</span>
                </div>

                <button class="btn btn-primary" id="placeOrderBtn" onclick="placeOrder()" disabled>
                    Place Order
                </button>
                <button class="btn btn-danger" onclick="clearCart()">Clear Cart</button>
            </div>
        </div>
    </div>

    <script>
        const CSFR_TOKEN = "<?php echo $_SESSION['csrf_token']; ?>";
    </script>
    <script src="./scripts/waiter_interface.js" defer></script>
</body>

</html>