<?php
session_start();
require 'db_config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}


if (isset($_GET['id'])) {
    $strategy_id = intval($_GET['id']);
    
    
    if ($stmt = $conn->prepare("SELECT * FROM py_strategies WHERE id = ?")) {
        $stmt->bind_param("i", $strategy_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $strategy = $result->fetch_assoc();
        $stmt->close();
    }

    
    if (!$strategy) {
        echo "Strategy not found.";
        exit();
    }
} else {
    echo "No strategy ID provided.";
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = htmlspecialchars($_POST['name']);
    $param_1 = htmlspecialchars($_POST['param_1']);
    $param_2 = htmlspecialchars($_POST['param_2']);
    $param_3 = htmlspecialchars($_POST['param_3']);
    $param_4 = htmlspecialchars($_POST['param_4']);
    $param_5 = htmlspecialchars($_POST['param_5']);

    
    if ($stmt = $conn->prepare("UPDATE py_strategies SET name=?, param_1=?, param_2=?, param_3=?, param_4=?, param_5=? WHERE id=?")) {
        $stmt->bind_param("ssssssi", $name, $param_1, $param_2, $param_3, $param_4, $param_5, $strategy_id);
        $stmt->execute();
        $stmt->close();
        header("Location: strategy.php"); 
        exit();
    } else {
        echo "Error updating strategy: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Strategy</title>
    <style>
       
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        form {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <h2>Edit Strategy</h2>
    <form method="post">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($strategy['name']); ?>" required>
        
        <label for="param_1">Parameter 1:</label>
        <input type="text" name="param_1" value="<?php echo htmlspecialchars($strategy['param_1']); ?>" required>
        
        <label for="param_2">Parameter 2:</label>
        <input type="text" name="param_2" value="<?php echo htmlspecialchars($strategy['param_2']); ?>" required>
        
        <label for="param_3">Parameter 3:</label>
        <input type="text" name="param_3" value="<?php echo htmlspecialchars($strategy['param_3']); ?>" required>
        
        <label for="param_4">Parameter 4:</label>
        <input type="text" name="param_4" value="<?php echo htmlspecialchars($strategy['param_4']); ?>" required>
        
        <label for="param_5">Parameter 5:</label>
        <input type="text" name="param_5" value="<?php echo htmlspecialchars($strategy['param_5']); ?>" required>
        
        <button type="submit">Update Strategy</button>
    </form>
</body>
</html>
