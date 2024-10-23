<?php
require 'db_config.php';

$symbol = isset($_GET['symbol']) ? $_GET['symbol'] : '';
$strategy = isset($_GET['strategy']) ? $_GET['strategy'] : '';

$strategyResult = [];

if ($symbol && $strategy) {
    $renkoBoxSize = 1;
    $atrLength = 14;
    $superTrendATR = 10;
    $superTrendMultiplier = 3.0;
    $lotSize = 1;
    $startHour = 9;
    $stopHour = 15;
    $maxTradesPerSession = 3;

    $currentPrice = 150;
    $previousPrice = 148;

    $atr = 2;
    $volatilityFilter = $atr > 1;

    $superTrend = ($currentPrice > $previousPrice) ? 1 : -1;
    $direction = $superTrend;

    $shortMA = 152;
    $longMA = 150;
    $isTrendingUp = $shortMA > $longMA;
    $isTrendingDown = $shortMA < $longMA;

    $buyCondition = ($direction == 1) && ($currentPrice > $previousPrice) && $isTrendingUp && $volatilityFilter;
    $sellCondition = ($direction == -1) && ($currentPrice < $previousPrice) && $isTrendingDown && $volatilityFilter;

    static $tradeCount = 0;

    if ($buyCondition && $tradeCount < $maxTradesPerSession) {
        $strategyResult = [
            'signal' => 'Buy',
            'price' => $currentPrice,
            'message' => 'Buy signal generated based on Renko and SuperTrend strategy.'
        ];
        $tradeCount++;
    } elseif ($sellCondition && $tradeCount < $maxTradesPerSession) {
        $strategyResult = [
            'signal' => 'Sell',
            'price' => $currentPrice,
            'message' => 'Sell signal generated based on Renko and SuperTrend strategy.'
        ];
        $tradeCount++;
    } else {
        $strategyResult = [
            'signal' => 'No Action',
            'price' => $currentPrice,
            'message' => 'No suitable trading condition met.'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($strategyResult);
?>
