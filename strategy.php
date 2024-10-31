<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "
    <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Access Denied</title>
            <script>
                setTimeout(function(){
                    window.location.href = 'dashboard.php';
                }, 3000); 
            </script>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    background-color: #f8f9fa;
                }
                .message-box {
                    text-align: center;
                    border: 1px solid #343a40;
                    padding: 30px;
                    background-color: #fff;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    border-radius: 8px;
                }
                .message-box h1 {
                    color: #dc3545;
                }
                .message-box p {
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class='message-box'>
                <h1>Access Denied</h1>
                <p>Only admins can access this page.</p>
                <p>You will be redirected to the dashboard in a few seconds.</p>
            </div>
        </body>
    </html>";
    exit(); 
}


$result = $conn->query("SELECT * FROM py_strategies");
if (!$result) {
    die("Error fetching strategies: " . htmlspecialchars($conn->error));
}


$user = [];
if (isset($_SESSION['user_id'])) {
    if ($stmt = $conn->prepare("SELECT profile_picture, username FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user = $user_result->fetch_assoc();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategies Control - Epitome</title>
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
        <div class="company-name">Epitome</div>
        <div class="profile-icon-container">
            <a href="profile.php">
                <div class="profile-icon">
                    <?php 
                    if (!empty($user['profile_picture'])) {
                        echo "<img src='" . htmlspecialchars($user['profile_picture']) . "' alt='Profile Picture' class='profile-img'>";
                    } else {
                        echo strtoupper(htmlspecialchars($user['username'][0]));
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
        <h2>Strategies Control Panel</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>par_1</th>
                    <th>par_2</th>
                    <th>par_3</th>
                    <th>par_4</th>
                    <th>par_5</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['param_1']); ?></td>
                            <td><?php echo htmlspecialchars($row['param_2']); ?></td>
                            <td><?php echo htmlspecialchars($row['param_3']); ?></td>
                            <td><?php echo htmlspecialchars($row['param_4']); ?></td>
                            <td><?php echo htmlspecialchars($row['param_5']); ?></td>
                            <td>
                                <a href="edit_strategy.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="edit-button">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No strategies found.</td>
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
