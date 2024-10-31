import pandas as pd
import numpy as np
import yfinance as yf
import matplotlib.pyplot as plt


buy_price_limit = 10  
quantity = 4          
supertrend_atr = 14   
supertrend_multiplier = 3.0  
sl_percentage = 0.02  


def calculate_supertrend(df, period, multiplier):
    hl = (df['High'] + df['Low']) / 2
    atr = df['Close'].rolling(window=period).apply(lambda x: np.max(x) - np.min(x), raw=True)

    upper_band = hl + (multiplier * atr)
    lower_band = hl - (multiplier * atr)

    supertrend = np.where(df['Close'] <= upper_band, upper_band, lower_band)
    return supertrend


ticker = "RELIANCE.NS"
start_date = "2024-01-01"
end_date = "2024-10-25"
df = yf.download(ticker, start=start_date, end=end_date)


df['SuperTrend'] = calculate_supertrend(df, supertrend_atr, supertrend_multiplier)


call_oi = 1000
put_oi = 800
trades = []
balance = 100000  


for i in range(1, len(df)):
    close = df['Close'].iloc[i]
    supertrend = df['SuperTrend'].iloc[i]
    position_size = 0

    
    long_condition = close < supertrend and call_oi > put_oi
    short_condition = close > supertrend and call_oi < put_oi

    
    far_otm_call_strike = close + 200
    far_otm_put_strike = close - 200

    
    if close < buy_price_limit:
        
        trades.append({'Date': df.index[i], 'Type': 'Buy Call/Put', 'Price': close, 'Quantity': quantity})
        position_size += quantity

    
    if long_condition and position_size > 0:
        
        trades.append({'Date': df.index[i], 'Type': 'Sell Put', 'Strike': far_otm_put_strike, 'Quantity': quantity})
        position_size -= quantity

    if short_condition and position_size < 0:
        
        trades.append({'Date': df.index[i], 'Type': 'Sell Call', 'Strike': far_otm_call_strike, 'Quantity': quantity})
        position_size += quantity

    
    if position_size > 0:  
        stop_loss_price = close * (1 - sl_percentage)
        if close <= stop_loss_price:
            trades.append({'Date': df.index[i], 'Type': 'Exit Buy', 'Price': close, 'Quantity': quantity})
            position_size = 0

    elif position_size < 0:  
        stop_loss_price = close * (1 + sl_percentage)
        if close >= stop_loss_price:
            trades.append({'Date': df.index[i], 'Type': 'Exit Sell', 'Price': close, 'Quantity': quantity})
            position_size = 0


trades_df = pd.DataFrame(trades)


print(trades_df)


plt.figure(figsize=(14, 7))
plt.plot(df['Close'], label='Close Price', alpha=0.5)
plt.plot(df['SuperTrend'], label='SuperTrend', color='blue', alpha=0.75)
plt.title(f'{ticker} Iron Condor Strategy Backtest')
plt.xlabel('Date')
plt.ylabel('Price')
plt.legend()
plt.grid()
plt.xticks(rotation=45)
plt.tight_layout()
plt.show()
