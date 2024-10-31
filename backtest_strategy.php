<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$strategies = [];
if ($stmt = $conn->prepare("SELECT * FROM py_strategies")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $strategies[] = $row;
    }
    $stmt->close();
}

$backtest_result = null;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strategy_id = filter_input(INPUT_POST, 'strategy', FILTER_VALIDATE_INT);
    $ticker = filter_input(INPUT_POST, 'ticker', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);

    if (!$strategy_id || empty($ticker) || empty($start_date) || empty($end_date)) {
        $error_message = 'Please fill in all fields.';
    } else {
        if ($stmt = $conn->prepare("SELECT * FROM py_strategies WHERE id = ?")) {
            $stmt->bind_param("i", $strategy_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $strategy = $result->fetch_assoc();
            $stmt->close();
        }

        if (!$strategy) {
            $error_message = "Strategy not found.";
        } else {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            if ($end < $start) {
                $error_message = 'End date must be after start date.';
            } else {
                $days = $start->diff($end)->days;
                $historical_data = fetch_historical_data($ticker, $days);

                if (!empty($historical_data)) {
                    $backtest_result = run_backtest($strategy, $historical_data);
                } else {
                    $error_message = "Failed to retrieve historical data. Please try again later.";
                }
            }
        }
    }
}

function fetch_historical_data($ticker, $days) {
    $command = escapeshellcmd("python fetch_stock_data.py " . escapeshellarg($ticker) . " " . escapeshellarg($days));
    $output = shell_exec($command . " 2>&1");

    if (empty($output)) {
        error_log("Error executing fetch_stock_data.py: " . $output);
        return [];
    }

    $historical_data = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return [];
    }

    return $historical_data['historicalPrices'] ?? [];
}

function run_backtest($strategy, $historical_data) {
    $results = [];
    $balance = 10000;
    $position = 0;
    $total_profit = 0;
    $trades = 0;

    $entry_threshold = $strategy['param_1'] / 100;
    $exit_threshold = $strategy['param_2'] / 100;

    $last_close = null;

    foreach ($historical_data as $index => $day) {
        $price = $day['close'];
        $date = date('Y-m-d', $day['time'] / 1000);

        $day_of_week = date('w', strtotime($date));

        if ($day_of_week == 0 || $day_of_week == 6) {
            continue;
        }

        if ($last_close === null) {
            $last_close = $price;
            continue;
        }

        $entry_price = $last_close * (1 - $entry_threshold);
        $exit_price = $last_close * (1 + $exit_threshold);

        if ($price < $entry_price && $position == 0) {
            $position = 1;
            $entry_price = $price;
        } elseif ($price > $exit_price && $position == 1) {
            $position = 0;
            $profit = $price - $entry_price;
            $total_profit += $profit;
            $balance += $profit;
            $trades++;
            $results[] = [
                'date' => $date,
                'profit' => $profit,
                'balance' => $balance
            ];
        }

        $last_close = $price;
    }

    $net_profit = $total_profit;
    $average_trade = $trades > 0 ? $total_profit / $trades : 0;
    $percent_profitable = $trades > 0 ? (count(array_filter($results, fn($r) => $r['profit'] > 0)) / $trades) * 100 : 0;

    return [
        'results' => $results,
        'net_profit' => $net_profit,
        'trades' => $trades,
        'average_trade' => $average_trade,
        'percent_profitable' => $percent_profitable
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backtest Strategy - Epitome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            padding-top: 60px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .container h2 {
            text-align: center;
            color: #007bff;
        }

        form {
            margin-top: 20px;
        }

        label, select, input {
            width: 100%;
            margin-bottom: 10px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            border: 1px solid #dee2e6;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        th {
            background-color: #007bff;
            color: #fff;
        }

        ul {
            list-style-type: none;
            padding: 0;
            margin-top: 15px;
        }

        ul li {
            padding: 5px 0;
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
    </style>
</head>
<body>

    <div class="header">
        <?php include 'header.php'; ?>
    </div>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    <div class="container">
        <h2>Backtest Strategy</h2>
        <form method="post">
            <label for="strategy">Select Strategy:</label>
            <select name="strategy" id="strategy" required>
                <?php foreach ($strategies as $strategy): ?>
                    <option value="<?php echo $strategy['id']; ?>"><?php echo htmlspecialchars($strategy['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="ticker">Ticker Symbol:</label>
            <input type="text" name="ticker" id="ticker" required>

            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" required>

            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" required>

            <button type="submit">Run Backtest</button>
        </form>

        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($backtest_result): ?>
            <h3>Backtest Results</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Profit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backtest_result['results'] as $result): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['date']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($result['profit'], 2)); ?></td>
                            <td><?php echo htmlspecialchars(number_format($result['balance'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Summary</h3>
            <ul>
                <li>Net Profit: <?php echo htmlspecialchars(number_format($backtest_result['net_profit'], 2)); ?></li>
                <li>Number of Trades: <?php echo htmlspecialchars($backtest_result['trades']); ?></li>
                <li>Average Trade: <?php echo htmlspecialchars(number_format($backtest_result['average_trade'], 2)); ?></li>
                <li>Percent Profitable: <?php echo htmlspecialchars(number_format($backtest_result['percent_profitable'], 2)); ?>%</li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
