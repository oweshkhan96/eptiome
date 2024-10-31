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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strategy_id = intval($_POST['strategy']);
    $ticker = htmlspecialchars($_POST['ticker']);
    $start_date = htmlspecialchars($_POST['start_date']);
    $end_date = htmlspecialchars($_POST['end_date']);

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

    
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $days = $interval->days;

    
    $historical_data = fetch_historical_data($ticker, $days);

    if (!empty($historical_data)) {
        $backtest_result = run_backtest($strategy, $historical_data);
    } else {
        echo "Failed to retrieve historical data. Please try again later.";
    }
}

function fetch_historical_data($ticker, $days) {
    $command = escapeshellcmd("python fetch_stock_data.py $ticker $days");
    $output = shell_exec($command . " 2>&1"); 

    
    echo "<pre>Python Output:\n$output</pre>";

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

    foreach ($historical_data as $day) {
        $price = $day['close'];
        if ($price < $strategy['param_1'] && $position == 0) {
            $position = 1;
            $entry_price = $price;
        } elseif ($price > $strategy['param_2'] && $position == 1) {
            $position = 0;
            $profit = $price - $entry_price;
            $balance += $profit;
            $results[] = ['date' => $day['time'], 'profit' => $profit, 'balance' => $balance];
        }
    }

    return $results;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backtest Strategy</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        form { background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        label, select, input { display: block; margin-bottom: 10px; }
        button { padding: 10px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
    </style>
</head>
<body>
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
                <?php foreach ($backtest_result as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['date']); ?></td>
                        <td><?php echo htmlspecialchars($result['profit']); ?></td>
                        <td><?php echo htmlspecialchars($result['balance']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
