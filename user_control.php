<?php
session_start();
require 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $message = "<p class='alert success'>User deleted successfully!</p>";
    } else {
        $message = "<p class='alert error'>Error deleting user: " . htmlspecialchars($stmt->error) . "</p>";
    }
}

$result = $conn->query("SELECT * FROM users");

if (!$result) {
    die("Error fetching users: " . htmlspecialchars($conn->error));
}

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT profile_picture, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Control - Epitome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            min-height: 100vh;
            padding-top: 60px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .company-name {
            font-size: 26px;
            font-weight: 600;
        }

        .profile-icon-container {
            display: flex;
            align-items: center;
            margin-right: 30px; 
        }

        .profile-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #6c757d;
            display: inline-block;
            text-align: center;
            color: white;
            font-size: 20px;
            cursor: pointer;
            overflow: hidden;
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .logout-button {
            margin-left: 20px; 
            background-color: red;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none; 
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .logout-button:hover {
            background-color: darkred; 
        }

        .sidebar {
            width: 250px; 
            background-color: #343a40;
            padding: 20px;
            color: white;
            height: calc(100vh - 60px); 
            position: fixed; 
            overflow-y: auto; 
            transition: width 0.3s; 
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px; 
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .container {
            margin-left: 290px; 
            padding: 10px;
            flex: 1; 
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: margin-left 0.3s; 
        }

        h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #343a40;
        }

        p {
            margin-bottom: 20px; 
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #343a40;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        form {
            display: inline;
        }

        input[type="submit"] {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #c82333;
        }

        .edit-button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .edit-button:hover {
            background-color: #0056b3; 
        }

        .alert {
            margin-top: 20px;
            font-size: 16px;
        }

        .alert.success {
            color: #28a745; 
        }

        .alert.error {
            color: #dc3545; 
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .container {
                margin-left: 220px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            Epitome
        </div>
        <div class="profile-icon-container">
            <a href="profile.php">
                <div class="profile-icon">
                    <?php 
                    if (!empty($user['profile_picture'])) {
                        $profile_picture = htmlspecialchars($user['profile_picture']);
                        echo "<img src='$profile_picture' alt='Profile Picture' class='profile-img'>";
                    } else {
                        echo strtoupper($user['username'][0]); 
                    }
                    ?>
                </div>
            </a>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </div>

    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="container">
        <h2>User Control Panel</h2>

        <?php if (isset($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="edit-button">Edit</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                <input type="submit" name="delete_user" value="Delete">
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>
