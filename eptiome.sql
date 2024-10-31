create database epitome;
use epitome;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    user_type ENUM('admin', 'normal') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stock_symbol VARCHAR(15) NOT NULL,
    added_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE strategies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    pine_script TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE py_strategies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    param_1 FLOAT NOT NULL,  -- Example parameter for the strategy
    param_2 FLOAT NOT NULL,  -- Example parameter for the strategy
    param_3 FLOAT NOT NULL,  -- Example parameter for the strategy
    param_4 VARCHAR(10),      -- New parameter, possibly for timeframe
    param_5 TEXT              -- New parameter, additional flexibility for strategy settings
);

INSERT INTO py_strategies (name, param_1, param_2, param_3, param_4, param_5)
VALUES ('renko', 10, 3, 1.0, '1m', 0.5);

INSERT INTO py_strategies (name, param_1, param_2, param_3, param_4, param_5) 
VALUES ('iron_condor', '10', '4', '14', '3.0', '0.02');

INSERT INTO py_strategies (name, param_1, param_2, param_3, param_4, param_5)
VALUES ('Iron Condor', 10, 4, 44200, '1m', 'Buy far OTM CE and PE below Rs 10; Sell 4 OTM options; Use Super Trend for signals; Monitor Open Interest; Define SL1, SL2 based on strikes; SL3 based on P/L limit; Backtest with free options data; Exclude weekends.');

