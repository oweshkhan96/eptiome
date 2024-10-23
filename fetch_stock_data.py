import sys
import yfinance as yf
import json
from datetime import datetime, timedelta

def fetch_stock_data(symbol, days, interval='1m'):
    end_date = datetime.now() + timedelta(days=1)
    start_date = end_date - timedelta(days=days)

    data = yf.download(symbol, start=start_date.strftime('%Y-%m-%d'), end=end_date.strftime('%Y-%m-%d'), interval=interval, progress=False)
    
    if data.empty:
        return {'error': 'No data available for the given symbol and date range.'}

    historical_prices = []
    for index, row in data.iterrows():
        historical_prices.append({
            'time': int(index.timestamp() * 1000),  # milliseconds
            'open': row['Open'],
            'high': row['High'],
            'low': row['Low'],
            'close': row['Close']
        })
    
    return historical_prices

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print(json.dumps({'error': 'Invalid number of arguments. Usage: fetch_stock_data.py <symbol> <days>'}))
        sys.exit(1)

    stock_symbol = sys.argv[1]
    days = int(sys.argv[2])

    try:
        if days <= 1:
            prices = fetch_stock_data(stock_symbol, days, interval='1m')
        else:
            prices = fetch_stock_data(stock_symbol, days, interval='1d')

        print(json.dumps({'historicalPrices': prices}))
    except Exception as e:
        print(json.dumps({'error': str(e)}))
