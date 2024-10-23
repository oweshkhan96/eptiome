<?php
session_start();

require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$currentDay = date('w');
$isMarketClosed = ($currentDay == 0 || $currentDay == 6);

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


$strategies = [];
$query = "SELECT id, name FROM strategies";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $strategies[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Epitome</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 270px;
            margin-top: 50px;
            padding: 30px;
            background-color: #f8f9fa;
            flex-grow: 1;
        }

        .chart-container {
            position: relative;
            height: 600px;
            width: 100%;
            margin-top: 20px;
            overflow: auto;
        }

        canvas {
            width: 100% !important;
            height: auto !important;
        }

        .watchlist-selector,
        .timespan-selector,
        .strategy-selector {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>Welcome to the Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <h3 style="color: #4CAF50;">NSE stock data is in real time and BSE stock data is 15min delayed please use trading view for bse stocks</h3>
        <?php if ($isMarketClosed): ?>
            <div class="market-closed-message" style="color: red; font-weight: bold; margin-bottom: 20px;">
                The market is currently closed. Please check back on Monday.
            </div>
        <?php else: ?>
            <div class="watchlist-selector">
                <label for="stockSymbol">Select Stock:</label>
                <select id="stockSymbol" onchange="fetchAndUpdateChart()">
                    <option value="">--Select Stock--</option>
                    <?php foreach ($watchlist as $symbol): ?>
                        <option value="<?php echo htmlspecialchars($symbol); ?>"><?php echo htmlspecialchars($symbol); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="timespan-selector">
                <label for="timespan">Select Time Span:</label>
                <select id="timespan" onchange="fetchAndUpdateChart()">
                    <option value="1d">1 Day</option>
                    <option value="5d">5 Days</option>
                    <option value="1mo">1 Month</option>
                </select>
            </div>

            <div class="strategy-selector">
                <label for="strategy">Select Trading Strategy:</label>
                <select id="strategy" onchange="updateStrategy()">
                    <option value="">--Select Strategy--</option>
                    <?php foreach ($strategies as $strategy): ?>
                        <option value="<?php echo htmlspecialchars($strategy['id']); ?>"><?php echo htmlspecialchars($strategy['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="chart-container">
                <canvas id="stockChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let chart;

        document.addEventListener('DOMContentLoaded', () => {
            fetchAndUpdateChart();
            setInterval(fetchAndUpdateChart, 60000);
        });

        function fetchAndUpdateChart() {
            const symbol = document.getElementById('stockSymbol').value;
            const timespan = document.getElementById('timespan').value;

            if (!symbol) {
                console.error('No stock symbol provided');
                return;
            }

            fetch(`fetch_stock_data.php?symbol=${symbol}&timespan=${timespan}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    if (Array.isArray(data.historicalPrices)) {
                        updateChartRealTime(data.historicalPrices, symbol);
                    } else {
                        console.error('Fetched data is not an array:', data);
                    }
                })
                .catch(error => {
                    console.error('Fetch operation failed:', error);
                });
        }

        function updateChartRealTime(historicalPrices, symbol) {
            const chartData = historicalPrices.map(price => ({
                x: new Date(price.time),
                y: price.close
            }));

            const segmentColors = historicalPrices.map((price, index) => {
                if (index === 0) return 'rgba(0, 255, 38, 1)';
                const previousPrice = historicalPrices[index - 1].close;
                return price.close >= previousPrice ? 'rgba(0, 255, 38, 1)' : 'rgba(255, 0, 0, 1)';
            });

            const ctx = document.getElementById('stockChart').getContext('2d');

            if (chart) {
                chart.data.datasets[0].data = chartData;
                chart.data.datasets[0].label = `${symbol} Stock Prices`;
                chart.data.datasets[0].segment.borderColor = ctx => segmentColors[ctx.p0DataIndex];
                chart.update();
            } else {
                chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: `${symbol} Stock Prices`,
                            data: chartData,
                            segment: {
                                borderColor: ctx => segmentColors[ctx.p0DataIndex],
                            },
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'minute',
                                    displayFormats: {
                                        minute: 'MMM d, yyyy h:mm a',
                                    },
                                    tooltipFormat: 'MMM d, yyyy h:mm a',
                                },
                                title: {
                                    display: true,
                                    text: 'Time'
                                },
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 30,
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Price (₹)'
                                },
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + value;
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Price: ₹' + context.parsed.y;
                                    }
                                }
                            },
                            zoom: {
                                pan: {
                                    enabled: true,
                                    mode: 'xy'
                                },
                                zoom: {
                                    wheel: {
                                        enabled: true,
                                    },
                                    pinch: {
                                        enabled: true
                                    },
                                    mode: 'xy'
                                }
                            }
                        }
                    }
                });
            }
        }

        function updateStrategy() {
            const strategyId = document.getElementById('strategy').value;
            console.log('Selected Strategy ID:', strategyId);
        }
    </script>

</body>
</html>
