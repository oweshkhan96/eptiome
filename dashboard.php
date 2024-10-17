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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }

        .main-content {
            margin-left: 250px;
            margin-top: 50px;
            padding: 30px;
            background-color: #f8f9fa;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .main-content h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
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

        .time-span {
            margin-bottom: 20px;
        }

        .timespan-selector {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .timespan-selector label {
            margin-right: 10px;
            font-weight: bold;
        }

        .timespan-selector select {
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .watchlist-selector {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .watchlist-selector label {
            margin-right: 10px;
            font-weight: bold;
        }

        .watchlist-selector select {
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>Welcome to the Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Select an option from the sidebar to manage different aspects of your application.</p>

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

        <div class="chart-container">
            <canvas id="stockChart"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetchAndUpdateChart();
            setInterval(fetchAndUpdateChart, 60000);
        });

        let chart;

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

            if (chart) {
                chart.data.datasets[0].data = chartData;
                chart.data.datasets[0].label = `${symbol} Stock Prices`;
                chart.data.datasets[0].segment.borderColor = ctx => segmentColors[ctx.p0DataIndex];
                chart.update();
            } else {
                const ctx = document.getElementById('stockChart').getContext('2d');

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
                                    minRotation: 45,
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Price'
                                },
                                beginAtZero: false,
                            }
                        },
                        plugins: {
                            zoom: {
                                pan: {
                                    enabled: true,
                                    mode: 'xy',
                                },
                                zoom: {
                                    wheel: {
                                        enabled: true,
                                    },
                                    pinch: {
                                        enabled: true,
                                    },
                                    mode: 'xy',
                                },
                            },
                        }
                    }
                });
            }
        }

    </script>
</body>
</html>
