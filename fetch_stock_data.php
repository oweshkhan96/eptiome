<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$stocks = isset($_GET['symbol']) ? escapeshellarg($_GET['symbol']) : '';
$timeSpan = $_GET['timespan'] ?? '1d';

if (empty($stocks)) {
    echo json_encode(['error' => 'No stock symbol provided']);
    exit();
}

$daysMapping = [
    '1d' => 1,
    '5d' => 5,
    '1mo' => 30,
    '3mo' => 90,
    '1y' => 365
];

$days = $daysMapping[$timeSpan] ?? null;


if ($days === null) {
    echo json_encode(['error' => 'Invalid timespan provided']);
    exit();
}


$pythonPath = "C:\\Users\\owesh\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$scriptPath = "d:\\Softwares\\Xampp\\htdocs\\test\\fetch_stock_data.py";


$command = escapeshellcmd("$pythonPath $scriptPath $stocks $days");
$output = shell_exec($command);


if ($output === null) {
    echo json_encode(['error' => 'Failed to execute Python script']);
    exit();
}


$jsonOutput = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Failed to retrieve historical prices. Output: ' . htmlspecialchars($output)]);
    exit();
}

echo json_encode($jsonOutput);
?>
