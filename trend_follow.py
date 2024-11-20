import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import yfinance as yf
import mysql.connector
from mysql.connector import Error

def get_database_connection():
    """Establishes a connection to the MySQL database."""
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='epitome'
        )
        if conn.is_connected():
            return conn
    except Error as e:
        print(f"Error connecting to database: {e}")
        return None

def fetch_strategy_parameters(strategy_name):
    """Fetches strategy parameters from the database for a given strategy."""
    conn = get_database_connection()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        query = "SELECT param_1, param_2, param_3, param_4, param_5 FROM py_strategies WHERE name = %s"
        cursor.execute(query, (strategy_name,))
        params = cursor.fetchone()
    except Error as e:
        print(f"Error fetching parameters: {e}")
        params = None
    finally:
        cursor.close()
        conn.close()
    return params

def calculate_moving_averages(df, short_window=20, long_window=50):
    """Calculates short and long moving averages."""
    df['SMA_short'] = df['Close'].rolling(window=short_window).mean()
    df['SMA_long'] = df['Close'].rolling(window=long_window).mean()
    return df

def apply_trailing_stop(current_price, stop_loss, profit, trail_by):
    """Adjusts the stop loss based on the trailing stop strategy."""
    if profit >= trail_by:
        stop_loss = max(stop_loss, current_price - trail_by)
    return stop_loss

def execute_options_trade(signal, option_type, theta_buy, theta_sell):
    """Executes an options trade based on the given parameters."""
    print(f"Executing {signal} {option_type} options with theta buy: {theta_buy} and sell: {theta_sell}")

def backtest_strategy(ticker, start_date, end_date):
    """Backtests the strategy on historical data for a specified ticker and date range."""
    
    df = yf.download(ticker, start=start_date, end=end_date)

    if df.empty:
        print("No data fetched for the given date range. Please check the ticker symbol and date range.")
        return

    
    df = df[df.index.dayofweek < 5]  

    
    strategy_name = 'trend_following'
    params = fetch_strategy_parameters(strategy_name)
    if not params:
        print("No strategy parameters found.")
        return

    
    short_window = int(params['param_1'])
    long_window = int(params['param_2'])
    trail_by = float(params['param_3'])
    buy_theta = float(params['param_4'])
    sell_theta = float(params['param_5'])

    
    df = calculate_moving_averages(df, short_window, long_window)
    df['Signal'] = 0
    df.iloc[short_window:, df.columns.get_loc('Signal')] = np.where(df['SMA_short'].iloc[short_window:] > df['SMA_long'].iloc[short_window:], 1, -1)


    
    initial_balance = 100000
    balance = initial_balance
    trade_log = []
    shares_bought = 0
    trailing_stop_loss = None

    
    for i in range(long_window, len(df)):
        current_price = df['Close'].iloc[i]

        
        if df['Signal'].iloc[i] == 1 and shares_bought == 0:
            trade_price = current_price
            shares_bought = balance // trade_price
            balance -= shares_bought * trade_price
            trailing_stop_loss = trade_price - (trade_price * 0.01)
            trade_log.append({'Date': df.index[i], 'Type': 'Buy', 'Price': trade_price, 'Balance': balance})
            execute_options_trade('Buy', 'Put', buy_theta, sell_theta)

        
        elif shares_bought > 0 and current_price <= trailing_stop_loss:
            balance += shares_bought * current_price
            trade_log.append({'Date': df.index[i], 'Type': 'Sell (TSL)', 'Price': current_price, 'Balance': balance})
            shares_bought = 0

        
        elif df['Signal'].iloc[i] == -1 and shares_bought > 0:
            balance += shares_bought * current_price
            trade_log.append({'Date': df.index[i], 'Type': 'Sell', 'Price': current_price, 'Balance': balance})
            shares_bought = 0
            execute_options_trade('Sell', 'Call', buy_theta, sell_theta)

        
        if shares_bought > 0:
            profit = current_price - trade_price
            trailing_stop_loss = apply_trailing_stop(current_price, trailing_stop_loss, profit, trail_by)

    
    trade_log_df = pd.DataFrame(trade_log)
    total_trades = len(trade_log_df) // 2

    
    if total_trades > 0:
        profit_loss = balance - initial_balance
        buy_prices = trade_log_df[trade_log_df['Type'] == 'Buy']['Price'].values
        sell_prices = trade_log_df[trade_log_df['Type'].str.startswith('Sell')]['Price'].values[:len(buy_prices)]
        win_rate = (np.sum(sell_prices > buy_prices) / total_trades) * 100
    else:
        profit_loss = 0
        win_rate = 0

    
    print(f"Final Balance: {balance:.2f}")
    print(f"Profit/Loss: {profit_loss:.2f}")
    print(f"Total Trades: {total_trades}")
    print(f"Win Rate: {win_rate:.2f}%")

    
    plt.figure(figsize=(14, 7))
    plt.plot(df['Close'], label='Close Price', alpha=0.5)
    plt.plot(df['SMA_short'], label=f'{short_window}-Day SMA', linestyle='--', color='orange')
    plt.plot(df['SMA_long'], label=f'{long_window}-Day SMA', linestyle='--', color='blue')

    
    if not trade_log_df.empty:
        plt.scatter(trade_log_df['Date'][trade_log_df['Type'] == 'Buy'], trade_log_df['Price'][trade_log_df['Type'] == 'Buy'], marker='^', color='green', label='Buy Signal', s=100)
        plt.scatter(trade_log_df['Date'][trade_log_df['Type'].str.startswith('Sell')], trade_log_df['Price'][trade_log_df['Type'].str.startswith('Sell')], marker='v', color='red', label='Sell Signal', s=100)

    plt.title(f'{ticker} Trend Following Strategy Backtest')
    plt.xlabel('Date')
    plt.ylabel('Price')
    plt.legend()
    plt.grid()
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.show()

if __name__ == "__main__":
    backtest_strategy('^NSEI', '2020-01-26', '2024-10-26')
