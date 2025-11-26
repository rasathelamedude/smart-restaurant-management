<?php
require_once("config.php");

requireRole('kitchen');
$connection = getConnection();

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    $statement = $connection->prepare(
        "UPDATE orders SET status = ? WHERE id = ?"
    );
    $statement->bind_param('si', $new_status, $order_id);
    $statement->execute();
    $statement->close();

    echo json_encode(['success' => true]);
    exit();
}

// Data fetching for the page
$pending_orders_num = $connection->query(
    query: "SELECT COUNT(*) as count FROM orders WHERE status='pending' AND DATE(order_time) = CURDATE() ORDER BY order_time ASC"
);

$preparing_orders_num = $connection->query(
    "SELECT COUNT(*) as count FROM orders WHERE status='preparing' AND DATE(order_time) = CURDATE() ORDER BY order_time ASC"
);

$completed_orders_num = $connection->query(
    "SELECT COUNT(*) as count FROM orders WHERE status='completed' AND DATE(order_time) = CURDATE() ORDER BY order_time ASC"
);

$orders = $connection->query(
    "
        SELECT orders.*, tables.table_number, users.username as waiter_name
        FROM orders
        JOIN tables ON orders.table_id = tables.id 
        JOIN users ON orders.waiter_id = users.id
        WHERE orders.status IN ('pending', 'preparing')
        ORDER BY orders.order_time ASC
    "
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles/kitchen_display.css">
    <title></title>
</head>

<body>
    <!-- Navbar -->
    <div class="header">
        <h1>Kitchen Display System</h1>
        <div class="header-info">
            <div class="clock" id="clock"></div>
            <div class="user-info">Kitchen Staff: <?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Status Cards -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value" id="pendingCount">
                    <?php echo $pending_orders_num->fetch_assoc()['count'] ?? 0; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">In Preparation</div>
                <div class="stat-value" id="preparingCount">
                    <?php echo $preparing_orders_num->fetch_assoc()['count'] ?? 0 ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Completed Today</div>
                <div class="stat-value">
                    <?php echo $completed_orders_num->fetch_assoc()['count'] ?? 0 ?>
                </div>
            </div>
        </div>

        <!-- Current Orders -->
        <div class="orders-grid" id="ordersGrid">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <?php
                    $order_id = $order['id'];

                    // Get order items
                    $order_items = $connection->query(
                        "SELECT order_items.quantity as item_quantity, menu_items.name as item_name
                                FROM order_items
                                JOIN menu_items ON order_items.menu_item_id = menu_items.id
                                WHERE order_items.order_id = $order_id
                                "
                    );
                    ?>
                    <!-- Each card -->
                    <div class="order-card <?php echo $order['status']; ?>" data-order-id="<?php echo $order['id']; ?>">
                        <!-- Order header and metadata -->
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-number">
                                    Order #<?php echo $order['id']; ?>
                                </div>
                                <div class="order-meta">
                                    Table <?php echo $order['table_number']; ?> •
                                    <?php echo $order['waiter_name']; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Order items -->
                        <div class="order-items">
                            <?php while ($item = $order_items->fetch_assoc()): ?>
                                <div class="order-item">
                                    <span class="item-name">
                                        <?php echo htmlspecialchars($item['item_name']) ?>
                                    </span>
                                    <span class="item-quantity">
                                        x<?php echo htmlspecialchars($item['item_quantity']) ?>
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="order-actions">
                            <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn btn-prepare" onclick='updateStatus(<?php echo $order["id"] ?>, "preparing")'>
                                    Start Preparing
                                </button>
                            <?php elseif ($order['status'] === 'preparing'): ?>
                                <button class="btn btn-complete" onclick='updateStatus(<?php echo $order["id"] ?>, "completed")'>
                                    Mark Complete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h2>No pending orders at the moment</h2>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="./scripts/kitchen_interface.js"></script>
</body>

</html>