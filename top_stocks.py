import sys
import yfinance as yf
import json
from datetime import datetime, timedelta

def fetch_stock_data(symbol, days, interval='1d'):
    """
    Fetch historical stock data for a given symbol over a specified number of days.
    """
    end_date = datetime.now() + timedelta(days=1)
    start_date = end_date - timedelta(days=days)

    # Fetch the stock data
    data = yf.download(symbol, start=start_date.strftime('%Y-%m-%d'), end=end_date.strftime('%Y-%m-%d'), interval=interval, progress=False)

    if data.empty:
        return {'symbol': symbol, 'error': 'No data available for the given symbol and date range.'}

    historical_prices = []
    for index, row in data.iterrows():
        historical_prices.append({
            'time': int(index.timestamp() * 1000),  # milliseconds
            'open': float(row['Open']),
            'high': float(row['High']),
            'low': float(row['Low']),
            'close': float(row['Close'])
        })

    return {
        'symbol': symbol,
        'historicalPrices': historical_prices
    }

def calculate_price_change(stock_data):
    """
    Calculate the percentage price change from the first open price to the last close price.
    """
    if 'historicalPrices' not in stock_data or len(stock_data['historicalPrices']) < 2:
        return None

    first_day = stock_data['historicalPrices'][0]
    last_day = stock_data['historicalPrices'][-1]

    open_price = first_day['open']
    close_price = last_day['close']

    if open_price > 0:
        price_change_percent = ((close_price - open_price) / open_price) * 100
        return price_change_percent
    return None

def get_top_gainers_and_losers(symbols, days):
    """
    Fetch stock data for multiple symbols and calculate the top gainers and losers.
    """
    top_gainers = []
    top_losers = []

    for symbol in symbols:
        stock_data = fetch_stock_data(symbol, days)
        
        if 'error' in stock_data:
            print(f"Skipping {symbol}: {stock_data['error']}")
            continue  # Skip symbols with no data
        
        price_change_percent = calculate_price_change(stock_data)

        if price_change_percent is not None:
            if price_change_percent > 0:
                top_gainers.append({
                    'symbol': symbol,
                    'priceChangePercent': price_change_percent
                })
            else:
                top_losers.append({
                    'symbol': symbol,
                    'priceChangePercent': price_change_percent
                })

    return top_gainers, top_losers

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print(json.dumps({'error': 'Invalid number of arguments. Usage: top_stocks.py <symbols> <days>'}))
        sys.exit(1)

    stock_symbols = sys.argv[1].split(",")
    days = int(sys.argv[2])

    try:
        top_gainers, top_losers = get_top_gainers_and_losers(stock_symbols, days)

        result = {
            'topGainers': top_gainers,
            'topLosers': top_losers
        }

        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'error': str(e)}))
        sys.exit(1)  # Exit with an error code
