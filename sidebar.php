<div class="sidebar">
    <a href="dashboard.php">Dashboard</a>
    <a href="dashboard2.php">Trading Viewer</a>
    <a href="watchlist.php">Watchlist</a>
    <a href="user_control.php">User Control</a>
    <a href="settings.php">Settings</a>
    <a href="bot_management.php">Bot Management</a>
    <a href="Overview.php">Overview</a>
    <a href="strategy.php">Strategy</a>
    <a href="backtest_strategy.php">BackTest Strategy</a>
</div>

<style>
    .sidebar {
        height: 100vh;
        width: 250px;
        position: fixed;
        top: 65px;
        left: 0;
        background-color: #343a40;
        padding-top: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        z-index: 999;
    }

    .sidebar a {
        padding: 15px 30px;
        text-decoration: none;
        font-size: 18px;
        color: white;
        display: block;
        transition: background-color 0.3s ease;
    }

    .sidebar a:hover {
        background-color: #495057;
    }
</style>
