<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['stock_symbol'])) {
    $stockSymbol = strtoupper(trim($_POST['stock_symbol']));

    $checkStmt = $conn->prepare("SELECT * FROM watchlist WHERE user_id = ? AND stock_symbol = ?");
    $checkStmt->bind_param("is", $userId, $stockSymbol);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO watchlist (user_id, stock_symbol) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $stockSymbol);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Stock added to watchlist successfully.";
        } else {
            $_SESSION['message'] = "Error adding stock to watchlist: " . $conn->error;
        }
    } else {
        $_SESSION['message'] = "Stock already exists in your watchlist.";
    }
}

if (isset($_GET['remove_id'])) {
    $removeId = intval($_GET['remove_id']);
    $deleteStmt = $conn->prepare("DELETE FROM watchlist WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $removeId, $userId);
    if ($deleteStmt->execute()) {
        $_SESSION['message'] = "Stock removed from watchlist.";
    } else {
        $_SESSION['message'] = "Error removing stock from watchlist: " . $conn->error;
    }
}

$watchlistStmt = $conn->prepare("SELECT * FROM watchlist WHERE user_id = ?");
$watchlistStmt->bind_param("i", $userId);
$watchlistStmt->execute();
$watchlistResult = $watchlistStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Watchlist - Epitome</title>
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

        .alert {
            margin-top: 20px;
            font-size: 16px;
            color: red;
        }

        form {
            margin-bottom: 20px;
        }

        input[type="text"] {
            padding: 10px;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .watchlist-table {
            width: 100%;
            border-collapse: collapse;
        }

        .watchlist-table th, .watchlist-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .watchlist-table th {
            background-color: #f2f2f2;
        }

        .remove-button {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .remove-button:hover {
            background-color: darkred;
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
                    $profile_picture = htmlspecialchars($user['profile_picture']);
                    echo "<img src='$profile_picture' alt='Profile Picture' class='profile-img'>";
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
        <h2>Your Watchlist</h2>
        
        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        ?>

        <form method="POST" action="watchlist.php">
            <input type="text" name="stock_symbol" placeholder="Enter stock symbol (e.g. AAPL)" required>
            <button type="submit">Add to Watchlist</button>
        </form>

        <table class="watchlist-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Stock Symbol</th>
                    <th>Added On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($watchlistResult->num_rows > 0) {
                    while ($row = $watchlistResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['stock_symbol']) . "</td>";
                        echo "<td>" . $row['added_on'] . "</td>";
                        echo '<td><a href="?remove_id=' . $row['id'] . '" class="remove-button">Remove</a></td>';
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No stocks in your watchlist.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
