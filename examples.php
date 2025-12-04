<?php
/**
 * CEX.io Spot Trading API Examples
 *
 * This file demonstrates the usage of the CEX PHP API class
 * with the new Spot Trading API endpoints.
 */

// Include CEX API class
include_once("cex.class.php");

// API credentials (set to false for public methods only)
$api_key    = false;  // Your API key from CEX.io
$api_secret = false;  // Your API secret from CEX.io
$api_cert   = 'cacert.pem';  // Path to CA certificate bundle

// Create CEX Object
$CEX = new CEX($api_key, $api_secret, $api_cert);

echo "=== CEX.io Spot Trading API Examples ===\n\n";

/*
 * Public functions - can be used without providing API credentials
 */

echo "--- Public Methods ---\n\n";

/**
 * Server Time
 * Returns the server timestamp
 */
echo "Server Time:\n";
$server_time = $CEX->server_time();
var_dump($server_time);
echo "\n";

/**
 * Pairs Info
 * Returns information about all available trading pairs
 */
echo "Pairs Info (first 3):\n";
$pairs_info = $CEX->pairs_info();
if (!isset($pairs_info['error'])) {
    $count = 0;
    foreach ($pairs_info as $pair => $info) {
        echo "  $pair\n";
        if (++$count >= 3) break;
    }
}
echo "\n";

/**
 * Symbols (backward compatible)
 * Returns an array of available symbol pairs
 */
echo "Symbols (first 5):\n";
$symbols = $CEX->symbols();
if (is_array($symbols) && !isset($symbols['error'])) {
    $display = array_slice($symbols, 0, 5);
    var_dump($display);
}
echo "\n";

/**
 * Currencies Info
 * Returns information about all available currencies
 */
echo "Currencies Info:\n";
$currencies = $CEX->currencies_info();
if (!isset($currencies['error'])) {
    echo "  Available currencies: " . count($currencies) . "\n";
}
echo "\n";

/**
 * Ticker
 * Get the symbol ticker
 * @param string $symbol_pair - Trading pair (supports both BTC/USD and BTC-USD formats)
 *
 * Returns:
 *   last - last trade price
 *   high - 24h high
 *   low - 24h low
 *   volume - 24h volume
 *   bid - highest buy order
 *   ask - lowest sell order
 */
echo "Ticker (BTC/USD):\n";
$ticker = $CEX->ticker('BTC/USD');
var_dump($ticker);
echo "\n";

/**
 * Multiple Tickers
 * Get ticker data for multiple pairs at once
 */
echo "Multiple Tickers (BTC/USD, ETH/USD):\n";
$tickers = $CEX->tickers(array('BTC/USD', 'ETH/USD'));
var_dump($tickers);
echo "\n";

/**
 * Last Price
 * Returns the last trade price for a trading pair
 */
echo "Last Price (BTC/USD):\n";
$last_price = $CEX->last_price('BTC/USD');
var_dump($last_price);
echo "\n";

/**
 * Order Book
 * Returns the order book with bids and asks
 * @param string $symbol_pair - Trading pair
 * @param int $depth - Limit the number of records (optional, -1 for full)
 */
echo "Order Book (BTC/USD, depth=5):\n";
$order_book = $CEX->order_book('BTC/USD', 5);
if (!isset($order_book['error'])) {
    echo "  Bids: " . count($order_book['bids']) . " levels\n";
    echo "  Asks: " . count($order_book['asks']) . " levels\n";
    if (!empty($order_book['bids'])) {
        echo "  Best bid: " . $order_book['bids'][0][0] . "\n";
    }
    if (!empty($order_book['asks'])) {
        echo "  Best ask: " . $order_book['asks'][0][0] . "\n";
    }
}
echo "\n";

/**
 * Trade History
 * Returns recent trades for a trading pair
 * @param string $symbol_pair - Trading pair
 * @param int $since - Return trades since this trade ID (optional)
 */
echo "Trade History (BTC/USD):\n";
$trade_history = $CEX->trade_history('BTC/USD');
if (is_array($trade_history) && !isset($trade_history['error'])) {
    echo "  Recent trades: " . count($trade_history) . "\n";
}
echo "\n";

/**
 * Candles (OHLCV data)
 * Returns candlestick/kline data for charting
 * @param string $symbol_pair - Trading pair
 * @param string $resolution - Time resolution (1m, 5m, 15m, 30m, 1h, 4h, 1d, 1w)
 * @param int $from - Start timestamp (optional)
 * @param int $to - End timestamp (optional)
 */
echo "Candles (BTC/USD, 1h, last 24h):\n";
$from = time() - (24 * 3600);
$to = time();
$candles = $CEX->candles('BTC/USD', '1h', $from, $to);
if (is_array($candles) && !isset($candles['error'])) {
    echo "  Candles returned: " . count($candles) . "\n";
}
echo "\n";

/**
 * Convert (deprecated - now calculates using ticker)
 * Converts amount using current exchange rate
 */
echo "Convert 1 BTC to USD:\n";
$convert = $CEX->convert('BTC/USD', 1.0);
var_dump($convert);
echo "\n";

/*
 * Private functions - require valid API credentials
 * Uncomment and set your API credentials to test these
 */

echo "--- Private Methods (require API credentials) ---\n\n";

if ($api_key && $api_secret) {

    /**
     * Balance
     * Returns account balances for all currencies
     */
    echo "Balance:\n";
    $balance = $CEX->balance();
    var_dump($balance);
    echo "\n";

    /**
     * My Fee
     * Returns the current trading fee for a pair
     */
    echo "My Fee (BTC/USD):\n";
    $fee = $CEX->my_fee('BTC/USD');
    var_dump($fee);
    echo "\n";

    /**
     * My Orders
     * Returns orders based on filter criteria
     * @param string $symbol_pair - Trading pair (optional)
     * @param string $status - 'open', 'closed', 'canceled', 'all'
     * @param int $limit - Maximum number of orders
     */
    echo "My Orders (BTC/USD, open):\n";
    $my_orders = $CEX->my_orders('BTC/USD', 'open', 10);
    var_dump($my_orders);
    echo "\n";

    /**
     * Open Orders (backward compatible)
     * Returns list of open orders for a trading pair
     */
    echo "Open Orders (BTC/USD):\n";
    $open_orders = $CEX->open_orders('BTC/USD');
    var_dump($open_orders);
    echo "\n";

    /**
     * Place Order
     * Creates a new limit order
     * @param string $symbol_pair - Trading pair
     * @param string $type - 'buy' or 'sell'
     * @param float $amount - Order amount
     * @param float $price - Order price
     */
    // Uncomment to test order placement:
    // echo "Place Order:\n";
    // $place_order = $CEX->place_order('BTC/USD', 'buy', 0.001, 10000);
    // var_dump($place_order);
    // echo "\n";

    /**
     * Place Market Order
     * Creates a market order
     * @param string $symbol_pair - Trading pair
     * @param string $type - 'buy' or 'sell'
     * @param float $amount - Order amount
     */
    // Uncomment to test market order:
    // echo "Place Market Order:\n";
    // $market_order = $CEX->place_market_order('BTC/USD', 'buy', 0.001);
    // var_dump($market_order);
    // echo "\n";

    /**
     * Cancel Order
     * Cancels an existing order
     * @param string $order_id - Order ID
     */
    // Uncomment to test order cancellation:
    // echo "Cancel Order:\n";
    // $cancel = $CEX->cancel_order('your-order-id-here');
    // var_dump($cancel);
    // echo "\n";

    /**
     * Cancel All Orders
     * Cancels all open orders, optionally for a specific pair
     */
    // Uncomment to test:
    // echo "Cancel All Orders (BTC/USD):\n";
    // $cancel_all = $CEX->cancel_all_orders('BTC/USD');
    // var_dump($cancel_all);
    // echo "\n";

    /**
     * Transaction History
     * Returns ledger/transaction history
     */
    echo "Transaction History:\n";
    $tx_history = $CEX->transaction_history(null, 5);
    var_dump($tx_history);
    echo "\n";

    /**
     * Funding History
     * Returns deposit/withdrawal history
     */
    echo "Funding History:\n";
    $funding = $CEX->funding_history(null, 5);
    var_dump($funding);
    echo "\n";

    /**
     * Deposit Address
     * Returns deposit address for a currency
     */
    echo "Deposit Address (BTC):\n";
    $address = $CEX->deposit_address('BTC');
    var_dump($address);
    echo "\n";

} else {
    echo "API credentials not set. Set \$api_key and \$api_secret to test private methods.\n\n";
}

echo "=== Deprecated Methods ===\n\n";

/**
 * Hashrate (deprecated)
 * GHash.io mining pool is no longer available
 */
echo "Hashrate (deprecated):\n";
$hashrate = $CEX->hashrate();
var_dump($hashrate);
echo "\n";

/**
 * Workers (deprecated)
 * GHash.io mining pool is no longer available
 */
echo "Workers (deprecated):\n";
$workers = $CEX->workers();
var_dump($workers);
echo "\n";

echo "=== Examples Complete ===\n";
