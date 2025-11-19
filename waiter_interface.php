<?php
require_once('config.php');
$connection = getConnection();

// Check if the user on the page is a waiter
requireRole('waiter');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $table_id = intval($_POST['table_id']);
    $waiter_id = intval($_SESSION['user_id']);
    $cart = json_decode($_POST['cart'], true);

    if (!empty($cart)) {
        // Create the new order
        $statement = $connection->prepare("INSERT INTO orders (table_id, waiter_id, status,total_amount) VALUES (?, ?, 'pending', 0)");
        $statement->bind_param('ii', $table_id, $waiter_id);
        $statement->execute();
        $order_id = $connection->insert_id;
        $statement->close();

        // Create the order items and calculate amount
        $total = 0.00;
        foreach ($cart as $item) {
            $statement = $connection->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");

            $statement->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
            $statement->execute();
            $statement->close();

            $total += $item['price'] * $item['quantity'];
        }

        // Update order's total amount
        $statement = $connection->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
        $statement->bind_param('di', $total, $order_id);
        $statement->execute();
        $statement->close();

        // Update table's status
        $statement = $connection->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $statement->bind_param('i', $table_id);
        $statement->execute();
        $statement->close();
    }
}

// Get all menu items;
$menu_items = $connection->query(
    'SELECT * FROM menu_items 
            WHERE available = 1 
            ORDER BY category, name'
);

// Get all tables in the restaurant
$tables = $connection->query('SELECT * FROM tables ORDER BY table_number');

// Get all the orders for the logged in waiter
$my_orders = $connection->query(
    "SELECT orders.*, tables.table_number
            FROM orders
            JOIN tables ON orders.table_id = tables.id
            WHERE orders.waiter_id = {$_SESSION['user_id']}
            AND DATE(orders.order_time) = CURDATE()
            ORDER BY orders.order_time DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/waiter_interface.css">
    <title>Waiter Interface | Smart Restaurant</title>
</head>

<body>
    <!-- Navigation bar -->
    <div class="header">
        <h1>Waiter Interface</h1>
        <div class="user-info">
            <span>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="login.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Container | whole page except navbar -->
    <div class="container">
        <!-- Tables \ Menu \ And current orders container -->
        <div class="main-content">
            <!-- tables card -->
            <div class="card">
                <h2>Select Table</h2>
                <div class="table-selector">
                    <?php while ($table = $tables->fetch_assoc()): ?>
                        <button class="table-btn <?php echo $table['status'] === 'occupied' ? 'occupied' : '' ?>"
                            onclick="selectTable(<?php echo $table['id'] ?>, <?php echo $table['table_number'] ?>)"
                            data-table-id="<?php echo $table['id'] ?>">
                            Table <?php echo $table['table_number'] ?>
                        </button>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Menu card -->
            <div class="card">
                <h2>Menu</h2>
                <div class="menu-grid">
                    <?php
                    $menu_items->data_seek(0);
                    while ($item = $menu_items->fetch_assoc()):
                        ?>
                        <div class="menu-item" data-category="<?php echo $item['category']; ?>"
                            onclick='addToCart(<?php echo json_encode($item); ?>)'>
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Current orders card -->
            <div class="card">
                <h2>My Orders Today</h2>
                <div class="orders-list">
                    <?php if ($my_orders->num_rows > 0): ?>
                        <?php while ($order = $my_orders->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <span class="order-id">Order #<?php echo $order['id']; ?> - Table
                                        <?php echo $order['table_number']; ?></span>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div>Amount: $<?php echo number_format($order['total_amount'], 2); ?></div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">
                                    <?php echo date('H:i', strtotime($order['order_time'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #666; text-align: center;">No orders yet today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Side bar order details -->
        <div class="sidebar">
            <div class="cart">
                <h2>Order Cart</h2>

                <div class="alert alert-warning" id="selectedTable" style="display: none;">
                    Selected: <strong id="tableDisplay"></strong>
                </div>

                <div class="cart-itmes" id="cartItems">
                    <p style="color: #666; text-align: center;">Cart is empty</p>
                </div>

                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cartTotal">$0.00</span>
                </div>

                <button class="btn btn-primary" id="placeOrderBtn" onclick="placeOrder()" disabled>
                    Place Order
                </button>
                <button class="btn btn-danger" onclick="clearCart()">
                    Clear Cart
                </button>
            </div>
        </div>
    </div>

    <script src="scripts/waiter_interface.js"></script>
</body>

</html>