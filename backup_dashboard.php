<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$watchlist = [];

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
    <script type="text/javascript" src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <style type="text/css">
        html, body, #chartContainer {
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
            padding: 0;
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
        let intervalId = null;
        let currentSymbol = null;

        function fetchAndUpdateChart() {
            const symbol = document.getElementById('stockSymbol').value;

            if (!symbol) {
                clearInterval(intervalId);
                return;
            }

            if (currentSymbol !== symbol) {
                currentSymbol = symbol;
                clearInterval(intervalId);
                intervalId = setInterval(fetchAndUpdateChart, 60000);
            }

            fetch(`fetch_stock_data.php?symbol=${symbol}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('chartContainer').innerHTML = "<p style='color: red;'>Error fetching data for the selected stock.</p>";
                        return;
                    }
                    loadStockChart(data.historicalPrices);
                })
                .catch(error => {
                    console.error('Fetch operation failed:', error);
                });
        }

        function loadStockChart(stockData) {
            if (!Array.isArray(stockData) || stockData.length === 0) {
                document.getElementById('chartContainer').innerHTML = "<p style='color: red;'>No data available for the selected stock.</p>";
                return;
            }

            const dataPoints = stockData.map(price => ({
                x: new Date(price.time),
                y: [price.open, price.high, price.low, price.close]
            }));

            const chart = new CanvasJS.Chart("chartContainer", {
                backgroundColor: "#000",
                title: {
                    text: stockData[0].symbol + " Stock Prices (Per Minute)",
                    fontFamily: "Times New Roman",
                    fontColor: "#ffffff"
                },
                zoomEnabled: true,
                exportEnabled: true,
                axisY: {
                    includeZero: false,
                    title: "Price (₹)",
                    titleFontColor: "#ffffff",
                    labelFontColor: "#ffffff"
                },
                axisX: {
                    title: "Time",
                    valueFormatString: "DD MMM, YYYY HH:mm",
                    labelAngle: -45,
                    labelFontColor: "#ffffff",
                    interval: calculateInterval(stockData),
                    intervalType: calculateIntervalType(stockData)
                },
                data: [{
                    type: "candlestick",
                    risingColor: "green",
                    fallingColor: "red",
                    dataPoints: dataPoints,
                    borderColor: "transparent",
                    borderThickness: 0
                }]
            });

            chart.addEventListener("zoomComplete", function () {
                const zoomedData = chart.data[0].dataPoints;
                if (zoomedData.length > 0) {
                    chart.axisX[0].interval = calculateInterval(zoomedData);
                    chart.axisX[0].intervalType = calculateIntervalType(zoomedData);
                }
                chart.render();
            });

            chart.render();
        }

        function calculateInterval(stockData) {
            const length = stockData.length;
            if (length <= 10) return 1;
            if (length <= 60) return 5;
            return 15;
        }

        function calculateIntervalType(stockData) {
            const length = stockData.length;
            if (length <= 10) return "minute";
            if (length <= 60) return "minute";
            return "minute";
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
