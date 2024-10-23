import pandas as pd
import numpy as np
import yfinance as yf
import matplotlib.pyplot as plt


ticker = 'TCS.NS'  
data = yf.download(ticker, start='2023-11-01', end='2023-12-31')
df = data[['Close', 'High', 'Low']].copy()


df['High-Low'] = df['High'] - df['Low']


def calculate_supertrend(df, period=7, multiplier=3):
    df['H-L'] = df['High'] - df['Low']
    df['H-PC'] = (df['High'] - df['Close'].shift(1)).abs()
    df['L-PC'] = (df['Low'] - df['Close'].shift(1)).abs()
    df['TR'] = df[['H-L', 'H-PC', 'L-PC']].max(axis=1)  
    df['ATR'] = df['TR'].rolling(window=period).mean()

    df['UpperBand'] = (df['High'] + df['Low']) / 2 + (multiplier * df['ATR'])
    df['LowerBand'] = (df['High'] + df['Low']) / 2 - (multiplier * df['ATR'])
    
    df['supertrend'] = np.nan
    
    for i in range(1, len(df)):
        if i < period:
            continue
        if df['Close'].iloc[i] <= df['UpperBand'].iloc[i-1]:
            df.loc[df.index[i], 'supertrend'] = df['UpperBand'].iloc[i]
        else:
            df.loc[df.index[i], 'supertrend'] = df['LowerBand'].iloc[i]

    df['supertrend'] = df['supertrend'].ffill()

    return df


df = calculate_supertrend(df)


df['Signal'] = 0
df['Signal'][1:] = np.where(df['Close'][1:] > df['supertrend'][1:], 1, 0)  
df['Signal'][1:] = np.where(df['Close'][1:] < df['supertrend'][1:], -1, df['Signal'][1:])  


initial_balance = 100000  
balance = initial_balance
positions = 0
entry_price = 0
trade_log = []  

for i in range(1, len(df)):
    if df['Signal'].iloc[i] == 1 and positions == 0:  
        positions += 1
        entry_price = df['Close'].iloc[i]
        trade_log.append({'Date': df.index[i], 'Type': 'Buy', 'Price': entry_price, 'Balance': balance})
        
    elif df['Signal'].iloc[i] == -1 and positions > 0:  
        balance += (df['Close'].iloc[i] - entry_price) * positions  
        trade_log.append({'Date': df.index[i], 'Type': 'Sell', 'Price': df['Close'].iloc[i], 'Balance': balance})
        positions = 0


if positions > 0:  
    balance += (df['Close'].iloc[-1] - entry_price) * positions


trade_log_df = pd.DataFrame(trade_log)


total_trades = len(trade_log_df[trade_log_df['Type'] == 'Sell'])
profit_loss = balance - initial_balance
win_rate = (total_trades and len(trade_log_df[trade_log_df['Type'] == 'Sell'][trade_log_df['Price'] > trade_log_df['Balance']])) / total_trades if total_trades else 0


print(f'Final Balance: {balance:.2f}')
print(f'Profit/Loss: {profit_loss:.2f}')
print(f'Total Trades: {total_trades}')
print(f'Win Rate: {win_rate:.2%}')


plt.figure(figsize=(14, 7))
plt.plot(df.index, df['Close'], label='Close Price', color='blue', linewidth=1.5)
plt.plot(df.index, df['supertrend'], label='Supertrend', color='orange', linewidth=1.5)
plt.scatter(trade_log_df['Date'], trade_log_df[trade_log_df['Type'] == 'Buy']['Price'], marker='^', color='green', label='Buy Signal', s=100)
plt.scatter(trade_log_df['Date'], trade_log_df[trade_log_df['Type'] == 'Sell']['Price'], marker='v', color='red', label='Sell Signal', s=100)

plt.title(f'{ticker} Price and Supertrend with Buy/Sell Signals')
plt.xlabel('Date')
plt.ylabel('Price')
plt.legend()
plt.grid()
plt.show()
