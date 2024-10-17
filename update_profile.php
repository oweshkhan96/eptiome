<?php
session_start();
require 'db_config.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $updatedData = [];
    $updateQuery = "UPDATE users SET ";

    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        $updatedData[] = "username = '$username'";
    }

    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        $updatedData[] = "email = '$email'";
    }

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $updatedData[] = "password = '$password'";
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);

        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $updatedData[] = "profile_picture = '$target_file'";
            } else {
                $_SESSION['message'] = "Sorry, there was an error uploading your file.";
            }
        } else {
            $_SESSION['message'] = "File is not an image.";
        }
    }

    if (!empty($updatedData)) {
        $updateQuery .= implode(", ", $updatedData) . " WHERE id = $userId";

        if ($conn->query($updateQuery) === TRUE) {
            $_SESSION['message'] = "Profile updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating profile: " . $conn->error;
        }
    } else {
        $_SESSION['message'] = "No changes were made.";
    }
} else {
    $_SESSION['message'] = "You are not logged in.";
}

header("Location: profile.php");
exit();
?>
