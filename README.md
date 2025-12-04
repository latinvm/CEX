# CEX.io PHP API

A PHP class for the CEX.io Spot Trading API: https://trade.cex.io/docs/

## Version 2.0

This version has been updated to work with the new CEX.io Spot Trading API. The old API endpoints (`https://cex.io/api`) have been replaced with the new Spot Trading API (`https://trade.cex.io/api/spot/`).

## Requirements

- PHP 7.0 or higher
- cURL extension enabled
- OpenSSL extension (for HTTPS)

## Installation

1. Download the API source files
2. Generate your API key and API secret on https://trade.cex.io/
3. Include the CEX class in your project

## Testing

### Setup

1. Copy the environment template and add your API credentials:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your API keys from https://trade.cex.io/

3. Run tests:
   ```bash
   source .env && ./vendor/bin/phpunit tests/CEXTest.php
   ```

The `.env` file is ignored by git to keep your credentials safe.

## Quick Start

```php
include_once("cex.class.php");

// API credentials (optional for public methods)
$api_key    = 'your_api_key';
$api_secret = 'your_api_secret';
$api_cert   = 'cacert.pem';  // Optional: path to CA certificate bundle

// Create CEX object
$CEX = new CEX($api_key, $api_secret, $api_cert);

// Public method example
$ticker = $CEX->ticker('BTC/USD');
var_dump($ticker);

// Private method example (requires API credentials)
$balance = $CEX->balance();
var_dump($balance);
```

## Public Methods

These methods don't require API credentials.

### Server Time
```php
$server_time = $CEX->server_time();
```

### Pairs Info
Returns information about all available trading pairs.
```php
$pairs = $CEX->pairs_info();
```

### Symbols
Returns an array of available symbol pairs (backward compatible).
```php
$symbols = $CEX->symbols();
// Returns: ['BTC/USD', 'ETH/USD', ...]
```

### Currencies Info
Returns information about all available currencies.
```php
$currencies = $CEX->currencies_info();
```

### Ticker
Get market data for a trading pair.
```php
$ticker = $CEX->ticker('BTC/USD');
// Returns: ['last', 'high', 'low', 'volume', 'bid', 'ask', 'pair', 'raw']
```

### Multiple Tickers
Get ticker data for multiple pairs at once.
```php
$tickers = $CEX->tickers(['BTC/USD', 'ETH/USD']);
```

### Last Price
Get the last trade price for a trading pair.
```php
$last_price = $CEX->last_price('BTC/USD');
// Returns: ['lprice', 'pair']
```

### Order Book
Get the order book with bids and asks.
```php
// Full order book
$order_book = $CEX->order_book('BTC/USD');

// Limited depth
$order_book = $CEX->order_book('BTC/USD', 10);
```

### Trade History
Get recent trades for a trading pair.
```php
$trades = $CEX->trade_history('BTC/USD');

// With since parameter
$trades = $CEX->trade_history('BTC/USD', $since_trade_id);
```

### Candles (OHLCV)
Get candlestick/kline data for charting.
```php
$from = time() - (24 * 3600);
$to = time();
$candles = $CEX->candles('BTC/USD', '1h', $from, $to);
// Resolutions: 1m, 5m, 15m, 30m, 1h, 4h, 1d, 1w
```

## Private Methods

These methods require valid API credentials.

### Balance
Get account balances for all currencies.
```php
$balance = $CEX->balance();
```

### My Fee
Get the current trading fee for a pair.
```php
$fee = $CEX->my_fee('BTC/USD');
```

### My Orders
Get orders based on filter criteria.
```php
// All open orders for a pair
$orders = $CEX->my_orders('BTC/USD', 'open', 100);

// Status options: 'open', 'closed', 'canceled', 'all'
```

### Open Orders (backward compatible)
Get list of open orders for a trading pair.
```php
$open_orders = $CEX->open_orders('BTC/USD');
```

### Place Order
Create a new limit order.
```php
$order = $CEX->place_order('BTC/USD', 'buy', 0.001, 50000);
// Parameters: pair, type (buy/sell), amount, price
```

### Place Market Order
Create a market order.
```php
$order = $CEX->place_market_order('BTC/USD', 'buy', 0.001);
// Parameters: pair, type (buy/sell), amount
```

### Cancel Order
Cancel an existing order.
```php
$result = $CEX->cancel_order($order_id);
// Returns: true on success, false on failure
```

### Cancel All Orders
Cancel all open orders.
```php
// Cancel all orders for a specific pair
$result = $CEX->cancel_all_orders('BTC/USD');

// Cancel all orders for all pairs
$result = $CEX->cancel_all_orders();
```

### Transaction History
Get ledger/transaction history.
```php
$history = $CEX->transaction_history('BTC', 100);
// Parameters: currency (optional), limit
```

### Funding History
Get deposit/withdrawal history.
```php
$history = $CEX->funding_history('BTC', 100);
// Parameters: currency (optional), limit
```

### Deposit Address
Get deposit address for a currency.
```php
$address = $CEX->deposit_address('BTC');
```

### Internal Transfer
Transfer funds between CEX.io accounts.
```php
$result = $CEX->internal_transfer('BTC', 0.01, $to_account_id);
```

## Symbol Format

The library accepts both old-style (`BTC/USD`) and new-style (`BTC-USD`) symbol formats. They are automatically converted as needed.

## Error Handling

All methods return an array. Check for errors by looking for the `error` key:

```php
$result = $CEX->ticker('BTC/USD');
if (isset($result['error'])) {
    echo "Error: " . $result['error'];
} else {
    echo "Last price: " . $result['last'];
}
```

## Migration from v1.x

### Constructor Changes

Old:
```php
$CEX = new CEX($username, $key, $secret, 'https://cex.io/api', 'cacert.pem');
```

New:
```php
$CEX = new CEX($key, $secret, 'cacert.pem');
// Note: username is no longer required
```

### Method Changes

| Old Method | New Method | Notes |
|------------|------------|-------|
| `symbols()` | `symbols()` | Now fetches dynamically from API |
| `ticker($pair)` | `ticker($pair)` | Response format changed |
| `last_price($pair)` | `last_price($pair)` | Response format changed |
| `order_book($pair, $depth)` | `order_book($pair, $depth)` | Response format similar |
| `trade_history($pair, $since)` | `trade_history($pair, $since)` | Response format changed |
| `price_stats(...)` | `candles(...)` | Use candles() instead |
| `convert($pair, $value)` | Removed | Use ticker() and calculate manually |
| `balance()` | `balance()` | Response format changed |
| `open_orders($pair)` | `open_orders($pair)` | Works the same |
| `place_order(...)` | `place_order(...)` | Parameter names changed |
| `cancel_order($id)` | `cancel_order($id)` | Works the same |
| `hashrate()` | Removed | GHash.io discontinued |
| `workers()` | Removed | GHash.io discontinued |

### New Methods in v2.0

- `server_time()` - Get server timestamp
- `pairs_info()` - Get detailed pair information
- `currencies_info()` - Get currency information
- `tickers($pairs)` - Get multiple tickers at once
- `candles($pair, $resolution, $from, $to)` - Get OHLCV data
- `my_fee($pair)` - Get trading fee
- `my_orders($pair, $status, $limit)` - Get orders with filters
- `place_market_order($pair, $type, $amount)` - Place market order
- `cancel_all_orders($pair)` - Cancel all orders
- `transaction_history($currency, $limit)` - Get transaction history
- `funding_history($currency, $limit)` - Get funding history
- `deposit_address($currency)` - Get deposit address
- `internal_transfer($currency, $amount, $to)` - Internal transfer

## API Documentation

For complete API documentation, visit: https://trade.cex.io/docs/

## License

MIT License - see LICENSE file for details.

## Author

Roy Boverhof - https://bsky.app/profile/boverhof.bsky.social

## Contributing

Contributions are welcome! Please submit pull requests to the GitHub repository.
