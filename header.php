<?php
require 'db_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT profile_picture, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>

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

<style>
    .profile-icon-container a {
        text-decoration: none;
        color: inherit;
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

    .header .company-name {
        font-size: 26px;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .header .profile-icon-container {
        display: flex;
        align-items: center;
        margin-right: 30px;
    }

    .header .profile-icon {
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

    .header .profile-icon img {
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
    }

    .logout-button:hover {
        background-color: darkred;
    }
</style>
