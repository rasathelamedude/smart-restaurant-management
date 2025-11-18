<?php
require_once('config.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if we have username and password both
    if (!empty($username) && !empty($password)) {
        $connection = getConnection();

        // Prepare sql statement to fetch user
        $statement = $connection->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $statement->bind_param('s', $username);
        $statement->execute();

        $query_result = $statement->get_result();
        $user = $query_result->fetch_assoc();

        // Check if the user was found
        if ($user) {
            // Check if password provided is correct
            if (password_verify($password, $user['password'])) {
                // create session | login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // redirect user based on their role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        break;
                    case 'waiter':
                        header('Location: waiter_interface.php');
                        break;
                    case 'kitchen':
                        header('Location: kitchen_display.php');
                        break;
                }

                exit();
            } else {
                $error = "Password is incorrect!";
            }
        } else {
            $error = "Username not found";
        }

        $statement->close();
        $connection->close();
    } else {
        $error = 'Both username and password are required!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart Restaurant System</title>
    <link rel="stylesheet" href="styles/login.css">
</head>

<body>
    <div class="login-container">
        <h1>Smart Restaurant</h1>
        <p class="subtitle">Ordering & Management System</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username" id="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password" id="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="demo-accounts">
            <h3>Demo Accounts: </h3>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>Waiter:</strong> waiter1 / admin123</p>
            <p><strong>Kitchen:</strong> kitchen1 / admin123</p>
        </div>
    </div>
</body>

</html>