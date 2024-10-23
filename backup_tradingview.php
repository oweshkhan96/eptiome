<?php
session_start();

// Include database configuration
require 'db_config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch user's watchlist stocks from the database
$user_id = $_SESSION['user_id'];
$watchlist = [];

// Prepare the SQL statement to fetch watchlist stocks
$query = "SELECT stock_symbol FROM watchlist WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $watchlist[] = $row['stock_symbol'];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Epitome</title>
    <script src="https://s3.tradingview.com/tv.js"></script>
    <style type="text/css">
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #2E2E2E;
            color: #ffffff;
        }
        .main-content {
            padding: 20px;
        }
        .watchlist-selector {
            margin-bottom: 20px;
        }
        select {
            padding: 10px;
            font-size: 16px;
            background-color: #444;
            color: #ffffff;
            border: 1px solid #666;
            border-radius: 5px;
        }
        #chartContainer {
            height: 400px;
            background-color: #000;
            border: none;
            box-shadow: none;
        }
    </style>
</head>
<body>

    <div class="main-content">
        <h2>Welcome to the Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Select a stock from your watchlist to view its chart.</p>

        <div class="watchlist-selector">
            <label for="stockSymbol">Select Stock:</label>
            <select id="stockSymbol" onchange="fetchAndUpdateChart()">
                <option value="">--Select Stock--</option>
                <?php foreach ($watchlist as $symbol): ?>
                    <option value="<?php echo htmlspecialchars($symbol); ?>"><?php echo htmlspecialchars($symbol); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="chart-container">
            <div id="chartContainer"></div>
        </div>
    </div>

    <script>
        let currentSymbol = null;
        let widget = null;

        function fetchAndUpdateChart() {
            const symbol = document.getElementById('stockSymbol').value;

            if (!symbol) {
                console.error('No stock symbol selected');
                if (widget) {
                    widget.remove();
                }
                return;
            }

            if (currentSymbol !== symbol) {
                currentSymbol = symbol;

                if (widget) {
                    widget.remove();
                }

                widget = new TradingView.widget({
                    "container_id": "chartContainer",
                    "width": "100%",
                    "height": "400",
                    "symbol": symbol,
                    "interval": "D",
                    "timezone": "exchange",
                    "theme": "Dark",
                    "style": "1",
                    "locale": "en",
                    "toolbar_bg": "#2E2E2E",
                    "enable_publishing": false,
                    "allow_symbol_change": true,
                    "hideideas": true,
                    "studies": [
                        "MACD@tv-basicstudies",
                        "RSI@tv-basicstudies"
                    ],
                    "autosize": true
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const stockDropdown = document.getElementById('stockSymbol');

            if (stockDropdown.options.length > 1) {
                stockDropdown.selectedIndex = 1;
                fetchAndUpdateChart();
            }
        });
    </script>

</body>
</html>
