import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import yfinance as yf

ticker = 'RELIANCE.NS'
df = yf.download(ticker, start='2024-03-10', end='2024-10-10')

if df.empty:
    print("No data fetched for the given date range. Please check the ticker symbol and date range.")
else:
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
            if df['Close'].iloc[i] <= df['UpperBand'].iloc[i-1]:
                df.loc[i, 'supertrend'] = df['UpperBand'].iloc[i]
            else:
                df.loc[i, 'supertrend'] = df['LowerBand'].iloc[i]
        return df

    df = calculate_supertrend(df)
    plot_df = df.dropna(subset=['supertrend'])

    df['Signal'] = 0
    buy_signals = df['Close'] > df['supertrend']
    sell_signals = df['Close'] < df['supertrend']
    df.loc[buy_signals, 'Signal'] = 1
    df.loc[sell_signals, 'Signal'] = -1

    initial_balance = 100000
    balance = initial_balance
    trade_log = []
    shares_bought = 0

    for i in range(1, len(df)):
        if df['Signal'].iloc[i] == 1 and balance > 0:
            trade_price = df['Close'].iloc[i]
            shares_bought = balance // trade_price
            balance -= shares_bought * trade_price
            trade_log.append({'Date': df.index[i], 'Type': 'Buy', 'Price': trade_price, 'Balance': balance})
        elif df['Signal'].iloc[i] == -1 and shares_bought > 0:
            trade_price = df['Close'].iloc[i]
            balance += shares_bought * trade_price
            trade_log.append({'Date': df.index[i], 'Type': 'Sell', 'Price': trade_price, 'Balance': balance})
            shares_bought = 0

    trade_log_df = pd.DataFrame(trade_log)

    total_trades = len(trade_log_df)
    if total_trades > 0:
        profit_loss = balance - initial_balance
        win_trades = trade_log_df[trade_log_df['Type'] == 'Sell']
        win_rate = (len(win_trades[win_trades['Price'] > initial_balance / total_trades])) / total_trades * 100
    else:
        profit_loss = 0
        win_rate = 0

    print(f"Final Balance: {balance:.2f}")
    print(f"Profit/Loss: {profit_loss:.2f}")
    print(f"Total Trades: {total_trades}")
    print(f"Win Rate: {win_rate:.2f}%")

    plt.figure(figsize=(14, 7))
    plt.plot(df['Close'], label='Close Price', alpha=0.5)
    plt.plot(plot_df['supertrend'], label='Supertrend', linestyle='--', color='orange')

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
