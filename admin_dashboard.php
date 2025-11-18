<?php
require_once('config.php');

// Check if the user is an admin;
requireRole('admin');

// Establish DB connection
$connection = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action user tries to do
    $action = $_POST['action'];

    if (isset($action)) {
        // Figure out which action we're doing
        switch ($action) {
            // Add item request
            case 'add_item':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $float_price = floatval($_POST['price']); // convert to float
                $category = $_POST['category'];
                $available = isset($_POST['available']) ? 1 : 0; // 1 for available, 0 for not available

                // prepare the sql statment
                $statement = $connection->prepare(
                    'INSERT INTO menu_items (name, description, price, category, available) VALUES (?, ?, ?, ?, ?)'
                );

                $statement->bind_param(
                    'ssdsi',
                    $name,
                    $description,
                    $float_price,
                    $category,
                    $available
                );

                // execute the statment
                $statement->execute();
                $statement->close();
                break;

            // Update item request
            case 'update_item':
                $name = $_POST['name'];
                $description = $_POST['description'] ?? '';
                $float_price = floatval($_POST['price']); // convert to float
                $category = $_POST['category'];
                $available = isset($_POST['available']) ? 1 : 0; // 1 for available, 0 for not available
                $id = intval($_POST['item_id']); // convert to int

                $statement = $connection->prepare(
                    'UPDATE menu_items SET 
                                name = ?, 
                                description = ?, 
                                price = ?, 
                                category = ?, 
                                available = ? 
                            WHERE id = ?'
                );

                $statement->bind_param(
                    'ssdsii',
                    $name,
                    $description,
                    $float_price,
                    $category,
                    $available,
                    $id
                );

                $statement->execute();
                $statement->close();
                break;

            // Delete item request
            case 'delete_item':
                $item_id = intval($_POST['item_id']);

                $statement = $connection->prepare('DELETE FROM menu_items WHERE id = ?');

                $statement->bind_param('i', $item_id);

                $statement->execute();
                $statement->close();
                break;

            // Add user request
            case 'add_user':
                $username = $_POST['username'];
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $_POST['role'];

                $statement = $connection->prepare(
                    'INSERT INTO users (username, password, role) VALUES (?, ?, ?)'
                );

                $statement->bind_param('sss', $username, $hashed_password, $role);

                $statement->execute();
                $statement->close();
                break;

            // Delete user request
            case 'delete_user':
                $user_id = intval($_POST['user_id']);

                $statement = $connection->prepare('DELETE FROM users WHERE id = ?');

                $statement->bind_param('i', $user_id);
                $statement->execute();
                $statement->close();
                break;
        }
    }
}

// Fetch data needed for the page;
$menu_items = $connection->query("SELECT * FROM menu_items ORDER BY name");
$users = $connection->query("SELECT * FROM users WHERE role != 'admin' ORDER BY username");
$orders = $connection->query("SELECT o.*, u.username as waiter_name, t.table_number FROM orders o JOIN users u ON o.waiter_id = u.id JOIN tables t ON o.table_id = t.id ORDER BY o.order_time DESC LIMIT 20");
$stats = $connection->query("SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(total_amount) as total_revenue
    FROM orders WHERE DATE(order_time) = CURDATE()")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Smart Restaurant System</title>
    <link rel="stylesheet" href="styles/admin_dashboard.css">
</head>

<body>
    <!-- Navbar -->
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="login.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Body -->
    <div class="container">
        <!-- Statistics cards -->
        <div class="stats">
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <div class="value">
                    <?php echo $stats['total_orders'] ?? 0; ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Compeleted Orders</h3>
                <div class="value">
                    <?php echo $stats['completed_orders'] ?? 0; ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Today's Revenue</h3>
                <div class="value">
                    $<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?>
                </div>
            </div>
        </div>

        <!-- Tab switching buttons -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('menu')">Menu Management</button>
            <button class="tab" onclick="switchTab('staff')">Staff Management</button>
            <button class="tab" onclick="switchTab('orders')">Orders</button>
        </div>

        <!-- Menu tab with menu_items -->
        <div id="menu-tab" class="tab-content active">
            <div class="card">
                <h2>Menu Items</h2>
                <button class="btn btn-primary" onclick="openAddItemModal()">+ Add New Item</button>
                <table style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $menu_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['available'] ? 'Yes' : "No" ?></td>
                                <td>
                                    <button class="btn btn-primary"
                                        onclick='openEditItemModal(<?php echo json_encode($item) ?>)'>
                                        Edit
                                    </button>

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger"
                                            onclick="return confirm('Delete this item?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Staff tab with users data -->
        <div id="staff-tab" class="tab-content">
            <div class="card">
                <h2>Staff Accounts</h2>
                <button onclick="openAddUserModal()" class="btn btn-primary">+ Add Staff Member</button>

                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']) ?></td>
                                <td><?php echo ucfirst($user['role']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id'] ?>">
                                        <button type="submit" class="btn btn-danger"
                                            onclick="return confirm('Delete this user?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Orders tab with order details -->
        <div id="orders-tab" class="tab-content">
            <div class="card">
                <h2>Recent Orders</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Table</th>
                            <th>Waiter</th>
                            <th>Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>Table <?php echo $order['table_number']; ?></td>
                                <td><?php echo htmlspecialchars($order['waiter_name']); ?></td>
                                <td><?php echo date("H:i", strtotime($order['order_time'])); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status'] ?>">
                                        <?php echo ucfirst($order['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add / Edit Item Modal -->
        <div id="itemModal" class="modal">
            <div class="modal-content">
                <h2 id="itemModalTitle">Add Menu Item</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_item" id="itemAction">
                    <input type="hidden" name="item_id" id="itemId">

                    <div class="form-group">
                        <label for="itemName">Name</label>
                        <input type="text" id="itemName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="itemDescription">Description</label>
                        <textarea name="description" id="itemDescription"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="itemPrice">Price ($)</label>
                        <input type="number" step="0.01" id="itemPrice" name="price" required>
                    </div>

                    <div class="form-group">
                        <label for="itemCategory">Category</label>
                        <select name="category" id="itemCategory" required>
                            <option value="Appetizer">Appetizer</option>
                            <option value="Main Course">Main Course</option>
                            <option value="Dessert">Dessert</option>
                            <option value="Beverage">Beverage</option>
                        </select>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="available" id="itemAvailable" value="1" checked>
                        <label for="itemAvailable" style="margin: 0;">Available</label>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn" onclick="closeItemModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Modal -->
        <div id="userModal" class="modal">
            <div class="modal-content">
                <h2>Add Staff Member</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" id="role" required>
                            <option value="waiter">Waiter</option>
                            <option value="kitchen">Kitchen Staff</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-success">Add User</button>
                        <button type="button" class="btn" onclick="closeUserModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="scripts/dashboard_tabs.js" defer></script>
</body>

</html>

<?php $connection->close(); ?>