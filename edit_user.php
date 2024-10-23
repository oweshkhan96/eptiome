<?php
session_start();
require 'db_config.php';

$user = [
    'username' => '',
    'email' => '',
    'user_type' => 'normal'
];

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT username, email, user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $user_result = $stmt->get_result();

        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
        } else {
            die("User not found.");
        }
    } else {
        die("Query failed: " . $stmt->error);
    }
} else {
    die("No user ID provided.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $user_type, $user_id);

    if ($stmt->execute()) {
        $message = "<p class='alert success'>User details updated successfully!</p>";
        $redirect = true;
    } else {
        $message = "<p class='alert error'>Error updating user details: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Epitome</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 65px;
            left: 0;
            background-color: #343a40;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }
        .sidebar a {
            padding: 15px 30px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
            transition: background-color 0.3s ease;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            margin-top: 60px;
            margin-left: 125px;
            padding: 20px;
            width: calc(100% - 250px);
            overflow-y: auto;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }
        h2 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .alert {
            text-align: center;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
    </style>
</head>
<body>

    <main class="main-content">
        <div class="container">
            <h2>Edit User Details</h2>

            <?php if (isset($message)) echo $message; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="user_type">User Type:</label>
                    <select name="user_type" id="user_type" required>
                        <option value="admin" <?php echo (isset($user['user_type']) && $user['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="normal" <?php echo (isset($user['user_type']) && $user['user_type'] === 'normal') ? 'selected' : ''; ?>>Normal</option>
                    </select>
                </div>
                <input type="submit" value="Update User" class="btn">
            </form>

            <?php if (isset($redirect) && $redirect): ?>
                <script>
                    // Redirecting to user_control.php after a successful update
                    window.location.href = 'user_control.php';
                </script>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
