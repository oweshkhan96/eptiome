import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import yfinance as yf
import mysql.connector


def get_database_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='epitome'
    )


def fetch_strategy_parameters(strategy_name):
    conn = get_database_connection()
    cursor = conn.cursor(dictionary=True)
    query = "SELECT param_1, param_2, param_3, param_4, param_5 FROM py_strategies WHERE name = %s"
    cursor.execute(query, (strategy_name,))
    params = cursor.fetchone()
    cursor.close()
    conn.close()
    return params


def calculate_supertrend(df, period=7, multiplier=3):
    df['H-L'] = df['High'] - df['Low']
    df['H-PC'] = abs(df['High'] - df['Close'].shift(1))
    df['L-PC'] = abs(df['Low'] - df['Close'].shift(1))
    df['TR'] = df[['H-L', 'H-PC', 'L-PC']].max(axis=1)
    df['ATR'] = df['TR'].rolling(window=period).mean()
    df['UpperBand'] = (df['High'] + df['Low']) / 2 + (multiplier * df['ATR'])
    df['LowerBand'] = (df['High'] + df['Low']) / 2 - (multiplier * df['ATR'])
    df['supertrend'] = np.nan

    for i in range(1, len(df)):
        if df['Close'].iloc[i] <= df['UpperBand'].iloc[i - 1]:
            df.loc[i, 'supertrend'] = df['UpperBand'].iloc[i]
        else:
            df.loc[i, 'supertrend'] = df['LowerBand'].iloc[i]

    return df


def calculate_renko(df, brick_size):
    df['Renko'] = 0  
    
    return df


def apply_trailing_stop(current_price, stop_loss, profit, trail_by):
    if profit >= trail_by:
        stop_loss = max(stop_loss, current_price - trail_by)
    return stop_loss


def execute_options_trade(signal, option_type, theta_buy, theta_sell):
    
    print(f"Executing {signal} {option_type} options with theta {theta_buy} and {theta_sell}")


def backtest_strategy(ticker, start_date, end_date):
    df = yf.download(ticker, start=start_date, end=end_date)

    if df.empty:
        print("No data fetched for the given date range. Please check the ticker symbol and date range.")
        return

    df = df[df.index.dayofweek < 5]  

    
    strategy_name = 'renko'
    params = fetch_strategy_parameters(strategy_name)

    if not params:
        print("No strategy found with the specified name.")
        return

    
    period = int(params['param_1'])
    multiplier = float(params['param_2'])
    renko_brick_size = float(params['param_3'])
    buy_theta = float(params['param_5'])

    
    df = calculate_supertrend(df, period, multiplier)
    df = calculate_renko(df, renko_brick_size)

    
    df['Signal'] = 0
    buy_signals = (df['Close'] > df['supertrend']) & (df['Renko'] == 1)  
    sell_signals = (df['Close'] < df['supertrend']) & (df['Renko'] == -1)
    df.loc[buy_signals, 'Signal'] = 1
    df.loc[sell_signals, 'Signal'] = -1

    
    initial_balance = 100000
    balance = initial_balance
    trade_log = []
    shares_bought = 0
    trailing_stop_loss = None

    
    for i in range(1, len(df)):
        
        if df['Signal'].iloc[i] == 1 and balance > 0:
            trade_price = df['Close'].iloc[i]
            shares_bought = balance // trade_price
            balance -= shares_bought * trade_price
            trailing_stop_loss = trade_price - (trade_price * 0.01)  
            trade_log.append({'Date': df.index[i], 'Type': 'Buy', 'Price': trade_price, 'Balance': balance})
            execute_options_trade('Buy', 'Put', buy_theta, 50)

        
        elif shares_bought > 0 and df['Close'].iloc[i] <= trailing_stop_loss:
            trade_price = df['Close'].iloc[i]
            balance += shares_bought * trade_price
            trade_log.append({'Date': df.index[i], 'Type': 'Sell (TSL)', 'Price': trade_price, 'Balance': balance})
            shares_bought = 0

        
        elif df['Signal'].iloc[i] == -1 and shares_bought > 0:
            trade_price = df['Close'].iloc[i]
            balance += shares_bought * trade_price
            trade_log.append({'Date': df.index[i], 'Type': 'Sell', 'Price': trade_price, 'Balance': balance})
            shares_bought = 0
            execute_options_trade('Sell', 'Call', 20, 50)

        
        if shares_bought > 0:
            profit = df['Close'].iloc[i] - trade_price
            trailing_stop_loss = apply_trailing_stop(df['Close'].iloc[i], trailing_stop_loss, profit, 5)

    
    trade_log_df = pd.DataFrame(trade_log)
    total_trades = len(trade_log_df)

    if total_trades > 0:
        profit_loss = balance - initial_balance
        win_trades = trade_log_df[trade_log_df['Type'] == 'Sell']
        win_rate = (len(win_trades[win_trades['Price'] > trade_log_df[trade_log_df['Type'] == 'Buy']['Price'].values])) / total_trades * 100
    else:
        profit_loss = 0
        win_rate = 0

    print(f"Final Balance: {balance:.2f}")
    print(f"Profit/Loss: {profit_loss:.2f}")
    print(f"Total Trades: {total_trades}")
    print(f"Win Rate: {win_rate:.2f}%")

    
    plt.figure(figsize=(14, 7))
    plt.plot(df['Close'], label='Close Price', alpha=0.5)
    plt.plot(df.dropna(subset=['supertrend'])['supertrend'], label='Supertrend', linestyle='--', color='orange')

    if not trade_log_df.empty:
        plt.scatter(trade_log_df['Date'][trade_log_df['Type'] == 'Buy'], trade_log_df['Price'][trade_log_df['Type'] == 'Buy'], marker='^', color='green', label='Buy Signal', s=100)
        plt.scatter(trade_log_df['Date'][trade_log_df['Type'] == 'Sell'], trade_log_df['Price'][trade_log_df['Type'] == 'Sell'], marker='v', color='red', label='Sell Signal', s=100)

    plt.title(f'{ticker} Trading Strategy Backtest')
    plt.xlabel('Date')
    plt.ylabel('Price')
    plt.legend()
    plt.grid()
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.show()

if __name__ == "__main__":
    backtest_strategy('RELIANCE.NS', '2024-1-26', '2024-10-26')
