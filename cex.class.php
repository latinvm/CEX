<?php

/**
 * CEX API class
 *
 * This class allows a user to call the CEX.io Spot Trading API
 * API Documentation: https://trade.cex.io/docs/
 *
 * PHP version 7.0+
 *
 * LICENSE: This source file is subject to The MIT License (MIT)
 *
 * @category   Bitcoin
 * @package    CEX
 * @author     Roy Boverhof <roy@server.biz>
 * @donations  16vq2qDf3VxDH56rc1p47W8tAwhwQX1z7H
 * @twitter    https://twitter.com/Boverhof
 * @copyright  2014-2024 Roy Boverhof
 * @license    http://opensource.org/licenses/MIT
 * @version    2.0
 * @link       https://github.com/ServerDotBiz/CEX
 *
 */

class CEX {
	private $_api_key;
	private $_api_secret;
	private $_api_url_public;
	private $_api_url_private;
	private $_api_cert;
	private $_symbols_cache;
	private $_debug;

	// API base URLs
	const API_URL_PUBLIC = 'https://trade.cex.io/api/spot/rest-public';
	const API_URL_PRIVATE = 'https://trade.cex.io/api/spot/rest';

	/**
	 * Create a new CEX object
	 * @param string $api_key Your API key (optional for public methods)
	 * @param string $api_secret Your API secret (optional for public methods)
	 * @param string $api_cert Path to CA certificate bundle (optional)
	 * @param bool $debug Enable debug mode
	 */
	public function __construct($api_key = false, $api_secret = false, $api_cert = false, $debug = false) {
		$this->_api_key = $api_key;
		$this->_api_secret = $api_secret;
		$this->_api_url_public = self::API_URL_PUBLIC;
		$this->_api_url_private = self::API_URL_PRIVATE;
		$this->_api_cert = $api_cert;
		$this->_symbols_cache = null;
		$this->_debug = $debug;
	}

	/**
	 * Initialize cURL
	 * @param string $url
	 * @return array cURL handle
	 */
	private function _initCurl($url) {
		$ch = null;

		if (($ch = @curl_init($url)) == false) {
			header("HTTP/1.1 500", true, 500);
			die("Cannot initialize cUrl session, please check if cURL is enabled / installed properly.");
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 1);

		if ($this->_api_cert){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, $this->_api_cert);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		if ($this->_debug){
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$verbose = fopen('curl.log', 'a+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose);
		}

		return($ch);
	}

	/**
	 * Generate signature for private API calls
	 * New API requires: HMAC-SHA256(path + timestamp + body)
	 * @param string $path The API endpoint path
	 * @param int $timestamp Unix timestamp in seconds
	 * @param string $body JSON encoded request body
	 * @return string $signature
	 */
	private function _signature($path, $timestamp, $body) {
		$message = $path . $timestamp . $body;
		$signature = hash_hmac('sha256', $message, $this->_api_secret);
		return $signature;
	}

	/**
	 * Convert symbol pair format from old (BTC/USD) to new (BTC-USD)
	 * @param string $symbol_pair
	 * @return string
	 */
	private function _convertSymbol($symbol_pair) {
		return str_replace('/', '-', $symbol_pair);
	}

	/**
	 * Convert symbol pair format from new (BTC-USD) to old (BTC/USD)
	 * @param string $symbol_pair
	 * @return string
	 */
	private function _convertSymbolToOld($symbol_pair) {
		return str_replace('-', '/', $symbol_pair);
	}

	/**
	 * Call CEX API (Public endpoint)
	 * All requests use POST method with JSON body
	 * @param string $action The API action to call
	 * @param array $params Request parameters
	 * @return array $response_array
	 */
	private function _callPublic($action, $params = array()) {
		$url = $this->_api_url_public . '/' . $action;
		$body = json_encode($params);

		$ch = $this->_initCurl($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json'
		));

		return $this->_executeRequest($ch);
	}

	/**
	 * Call CEX API (Private endpoint)
	 * Requires authentication via headers
	 * @param string $action The API action to call
	 * @param array $params Request parameters
	 * @return array $response_array
	 */
	private function _callPrivate($action, $params = array()) {
		if (!$this->_api_key || !$this->_api_secret) {
			return array('error' => 'API key and secret are required for private methods');
		}

		$path = '/api/spot/rest/' . $action;
		$url = $this->_api_url_private . '/' . $action;
		$body = json_encode($params);
		$timestamp = time();
		$signature = $this->_signature($path, $timestamp, $body);

		$ch = $this->_initCurl($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json',
			'X-AGGR-KEY: ' . $this->_api_key,
			'X-AGGR-TIMESTAMP: ' . $timestamp,
			'X-AGGR-SIGNATURE: ' . $signature
		));

		return $this->_executeRequest($ch);
	}

	/**
	 * Execute cURL request and handle response
	 * @param resource $ch cURL handle
	 * @return array $response_array
	 */
	private function _executeRequest($ch) {
		$response_data = curl_exec($ch);

		if (curl_errno($ch)) {
			$response_array = array(
				'error' => 'cURL error: ' . curl_error($ch)
			);
		} else {
			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$decoded = json_decode($response_data, true);

			if ($response_code == 200) {
				// New API returns {"ok": "ok", "data": {...}} format
				if (isset($decoded['ok']) && $decoded['ok'] === 'ok') {
					$response_array = isset($decoded['data']) ? $decoded['data'] : $decoded;
				} else if (isset($decoded['error'])) {
					$response_array = array(
						'error' => $decoded['error'],
						'statusCode' => isset($decoded['statusCode']) ? $decoded['statusCode'] : $response_code
					);
				} else {
					$response_array = $decoded;
				}
			} else {
				$error_msg = isset($decoded['error']) ? $decoded['error'] : $response_data;
				$response_array = array(
					'error' => 'HTTP response: ' . $response_code . ' ' . $error_msg
				);
			}
		}

		curl_close($ch);
		return $response_array;
	}

	/**
	 * Public functions, can be called without API credentials
	 */

	/**
	 * Get Server Time
	 * Returns the server timestamp
	 * @return array $response_array
	 */
	public function server_time() {
		return $this->_callPublic('get_server_time');
	}

	/**
	 * Get Pairs Info (symbols)
	 * Returns information about all available trading pairs
	 * @return array $response_array
	 */
	public function pairs_info() {
		return $this->_callPublic('get_pairs_info');
	}

	/**
	 * symbols (backward compatible alias for pairs_info)
	 * Returns an array of available symbol pairs
	 * @return array $symbols_pair
	 */
	public function symbols() {
		if ($this->_symbols_cache === null) {
			$result = $this->pairs_info();
			if (isset($result['error'])) {
				return $result;
			}
			$this->_symbols_cache = array();
			if (is_array($result)) {
				foreach ($result as $pair => $info) {
					// Convert from API format (BTC-USD) to old format (BTC/USD)
					$this->_symbols_cache[] = $this->_convertSymbolToOld($pair);
				}
			}
		}
		return $this->_symbols_cache;
	}

	/**
	 * Get Currencies Info
	 * Returns information about all available currencies
	 * @return array $response_array
	 */
	public function currencies_info() {
		return $this->_callPublic('get_currencies_info');
	}

	/**
	 * Ticker
	 * Get the symbol ticker
	 * @param string $symbol_pair Trading pair (e.g., 'BTC/USD' or 'BTC-USD')
	 * @return array $response_array
	 *
	 * Returns ticker data including:
	 *   lastTradePx - last trade price
	 *   high24 - 24h high
	 *   low24 - 24h low
	 *   vol24 - 24h volume
	 *   bestBidPx - best bid price
	 *   bestAskPx - best ask price
	 */
	public function ticker($symbol_pair) {
		$pair = $this->_convertSymbol($symbol_pair);
		$result = $this->_callPublic('get_ticker', array('pairs' => array($pair)));

		// Transform response to be more similar to old format for easier migration
		if (isset($result[$pair])) {
			$data = $result[$pair];
			return array(
				'last' => isset($data['lastTradePx']) ? $data['lastTradePx'] : null,
				'high' => isset($data['high24']) ? $data['high24'] : null,
				'low' => isset($data['low24']) ? $data['low24'] : null,
				'volume' => isset($data['vol24']) ? $data['vol24'] : null,
				'bid' => isset($data['bestBidPx']) ? $data['bestBidPx'] : null,
				'ask' => isset($data['bestAskPx']) ? $data['bestAskPx'] : null,
				'pair' => $symbol_pair,
				'raw' => $data
			);
		}

		return $result;
	}

	/**
	 * Get Multiple Tickers
	 * Get ticker data for multiple pairs at once
	 * @param array $symbol_pairs Array of trading pairs
	 * @return array $response_array
	 */
	public function tickers($symbol_pairs) {
		$pairs = array();
		foreach ($symbol_pairs as $pair) {
			$pairs[] = $this->_convertSymbol($pair);
		}
		return $this->_callPublic('get_ticker', array('pairs' => $pairs));
	}

	/**
	 * Last Price
	 * Returns the last trade price for a trading pair
	 * @param string $symbol_pair Trading pair
	 * @return array $response_array
	 */
	public function last_price($symbol_pair) {
		$ticker = $this->ticker($symbol_pair);
		if (isset($ticker['error'])) {
			return $ticker;
		}
		return array(
			'lprice' => isset($ticker['last']) ? $ticker['last'] : null,
			'pair' => $symbol_pair
		);
	}

	/**
	 * Order Book
	 * Returns the order book with bids and asks
	 * @param string $symbol_pair Trading pair
	 * @param int $depth Limit the number of bid/ask records (optional, -1 for full depth)
	 * @return array $response_array
	 */
	public function order_book($symbol_pair, $depth = -1) {
		$pair = $this->_convertSymbol($symbol_pair);
		$params = array('pair' => $pair);
		if ($depth !== false && $depth > 0) {
			$params['depth'] = intval($depth);
		}

		$result = $this->_callPublic('get_order_book', $params);

		// Transform to be more similar to old format
		if (isset($result['bids']) || isset($result['asks'])) {
			return array(
				'timestamp' => isset($result['timestamp']) ? $result['timestamp'] : time(),
				'bids' => isset($result['bids']) ? $result['bids'] : array(),
				'asks' => isset($result['asks']) ? $result['asks'] : array(),
				'pair' => $symbol_pair
			);
		}

		return $result;
	}

	/**
	 * Trade History
	 * Returns recent trades for a trading pair
	 * @param string $symbol_pair Trading pair
	 * @param int $since Return trades since this trade ID (optional)
	 * @return array $response_array
	 */
	public function trade_history($symbol_pair, $since = 0) {
		$pair = $this->_convertSymbol($symbol_pair);
		$params = array('pair' => $pair);
		if ($since > 0) {
			$params['since'] = intval($since);
		}
		return $this->_callPublic('get_trade_history', $params);
	}

	/**
	 * Get Candles (OHLCV data)
	 * Returns candlestick/kline data for charting
	 * @param string $symbol_pair Trading pair
	 * @param string $resolution Time resolution (1m, 5m, 15m, 30m, 1h, 4h, 1d, 1w)
	 * @param int $from Start timestamp (Unix timestamp)
	 * @param int $to End timestamp (Unix timestamp)
	 * @return array $response_array
	 */
	public function candles($symbol_pair, $resolution = '1h', $from = null, $to = null) {
		$pair = $this->_convertSymbol($symbol_pair);
		$params = array(
			'pair' => $pair,
			'resolution' => $resolution
		);
		if ($from !== null) {
			$params['from'] = intval($from);
		}
		if ($to !== null) {
			$params['to'] = intval($to);
		}
		return $this->_callPublic('get_candles', $params);
	}

	/**
	 * Price Stats (backward compatible - uses candles)
	 * Returns price statistics for charting
	 * @param string $symbol_pair Trading pair
	 * @param int $lastHours Number of hours to look back
	 * @param int $maxRespArrSize Maximum response array size
	 * @return array $response_array
	 * @deprecated Use candles() method instead
	 */
	public function price_stats($symbol_pair, $lastHours, $maxRespArrSize) {
		$from = time() - ($lastHours * 3600);
		$to = time();
		return $this->candles($symbol_pair, '1h', $from, $to);
	}

	/**
	 * Convert (deprecated)
	 * This method is no longer available in the new API
	 * Use ticker to get exchange rate and calculate manually
	 * @param string $symbol_pair
	 * @param float $value
	 * @return array
	 * @deprecated No longer available in new API
	 */
	public function convert($symbol_pair, $value) {
		$ticker = $this->ticker($symbol_pair);
		if (isset($ticker['error'])) {
			return $ticker;
		}
		if (isset($ticker['last'])) {
			return array(
				'amnt' => floatval($value) * floatval($ticker['last']),
				'rate' => $ticker['last']
			);
		}
		return array('error' => 'Could not get exchange rate');
	}

	/**
	 * Private functions, needs valid API credentials
	 */

	/**
	 * Balance (Wallet Balance)
	 * Returns account balances for all currencies
	 * @return array $response_array
	 */
	public function balance() {
		return $this->_callPrivate('get_my_wallet_balance');
	}

	/**
	 * Get My Current Fee
	 * Returns the current trading fee for a pair
	 * @param string $symbol_pair Trading pair
	 * @return array $response_array
	 */
	public function my_fee($symbol_pair) {
		$pair = $this->_convertSymbol($symbol_pair);
		return $this->_callPrivate('get_my_current_fee', array('pair' => $pair));
	}

	/**
	 * Get My Orders
	 * Returns orders based on filter criteria
	 * @param string $symbol_pair Trading pair (optional)
	 * @param string $status Order status filter: 'open', 'closed', 'canceled', 'all' (default: 'open')
	 * @param int $limit Maximum number of orders to return
	 * @return array $response_array
	 */
	public function my_orders($symbol_pair = null, $status = 'open', $limit = 100) {
		$params = array();
		if ($symbol_pair !== null) {
			$params['pair'] = $this->_convertSymbol($symbol_pair);
		}
		if ($status !== null) {
			$params['status'] = $status;
		}
		if ($limit > 0) {
			$params['limit'] = intval($limit);
		}
		return $this->_callPrivate('get_my_orders', $params);
	}

	/**
	 * Open orders (backward compatible)
	 * Returns list of open orders for a trading pair
	 * @param string $symbol_pair Trading pair
	 * @return array $response_array
	 */
	public function open_orders($symbol_pair) {
		return $this->my_orders($symbol_pair, 'open');
	}

	/**
	 * Place Order
	 * Creates a new order
	 * @param string $symbol_pair Trading pair
	 * @param string $type Order type: 'buy' or 'sell'
	 * @param float $amount Order amount
	 * @param float $price Order price (for limit orders)
	 * @param string $order_type Order type: 'limit', 'market', 'stop-limit' (default: 'limit')
	 * @return array $response_array
	 */
	public function place_order($symbol_pair, $type, $amount, $price, $order_type = 'limit') {
		$valid_types = array('buy', 'sell');
		if (!in_array($type, $valid_types)) {
			return array('error' => 'Invalid order type: ' . $type . '. Must be buy or sell.');
		}

		$pair = $this->_convertSymbol($symbol_pair);
		$params = array(
			'pair' => $pair,
			'side' => $type,
			'type' => $order_type,
			'amount' => strval(floatval($amount)),
			'price' => strval(floatval($price))
		);

		return $this->_callPrivate('do_my_new_order', $params);
	}

	/**
	 * Place Market Order
	 * Creates a market order that executes immediately at best available price
	 * @param string $symbol_pair Trading pair
	 * @param string $type Order type: 'buy' or 'sell'
	 * @param float $amount Order amount
	 * @return array $response_array
	 */
	public function place_market_order($symbol_pair, $type, $amount) {
		$valid_types = array('buy', 'sell');
		if (!in_array($type, $valid_types)) {
			return array('error' => 'Invalid order type: ' . $type . '. Must be buy or sell.');
		}

		$pair = $this->_convertSymbol($symbol_pair);
		$params = array(
			'pair' => $pair,
			'side' => $type,
			'type' => 'market',
			'amount' => strval(floatval($amount))
		);

		return $this->_callPrivate('do_my_new_order', $params);
	}

	/**
	 * Cancel Order
	 * Cancels an existing order
	 * @param string $order_id Order ID to cancel
	 * @return array|bool $response_array or true on success
	 */
	public function cancel_order($order_id) {
		$result = $this->_callPrivate('do_cancel_my_order', array('orderId' => strval($order_id)));

		if (isset($result['error'])) {
			return false;
		}
		return true;
	}

	/**
	 * Cancel All Orders
	 * Cancels all open orders, optionally for a specific pair
	 * @param string $symbol_pair Trading pair (optional)
	 * @return array $response_array
	 */
	public function cancel_all_orders($symbol_pair = null) {
		$params = array();
		if ($symbol_pair !== null) {
			$params['pair'] = $this->_convertSymbol($symbol_pair);
		}
		return $this->_callPrivate('do_cancel_all_orders', $params);
	}

	/**
	 * Get Transaction History
	 * Returns ledger/transaction history
	 * @param string $currency Currency to filter by (optional)
	 * @param int $limit Maximum number of records
	 * @return array $response_array
	 */
	public function transaction_history($currency = null, $limit = 100) {
		$params = array('limit' => intval($limit));
		if ($currency !== null) {
			$params['currency'] = strtoupper($currency);
		}
		return $this->_callPrivate('get_my_transaction_history', $params);
	}

	/**
	 * Get Funding History
	 * Returns deposit/withdrawal history
	 * @param string $currency Currency to filter by (optional)
	 * @param int $limit Maximum number of records
	 * @return array $response_array
	 */
	public function funding_history($currency = null, $limit = 100) {
		$params = array('limit' => intval($limit));
		if ($currency !== null) {
			$params['currency'] = strtoupper($currency);
		}
		return $this->_callPrivate('get_my_funding_history', $params);
	}

	/**
	 * Get Deposit Address
	 * Returns deposit address for a currency
	 * @param string $currency Currency code (e.g., 'BTC', 'ETH')
	 * @return array $response_array
	 */
	public function deposit_address($currency) {
		return $this->_callPrivate('get_deposit_address', array(
			'currency' => strtoupper($currency)
		));
	}

	/**
	 * Internal Transfer
	 * Transfer funds between CEX.io accounts
	 * @param string $currency Currency code
	 * @param float $amount Amount to transfer
	 * @param string $to_account Destination account ID
	 * @return array $response_array
	 */
	public function internal_transfer($currency, $amount, $to_account) {
		return $this->_callPrivate('do_my_internal_transfer', array(
			'currency' => strtoupper($currency),
			'amount' => strval(floatval($amount)),
			'toAccount' => $to_account
		));
	}

	/**
	 * Hash Rate (deprecated)
	 * GHash.io mining pool is no longer available
	 * @return array $response_array
	 * @deprecated GHash.io has been discontinued
	 */
	public function hashrate() {
		return array('error' => 'GHash.io mining pool has been discontinued. This method is no longer available.');
	}

	/**
	 * Workers Hash Rate (deprecated)
	 * GHash.io mining pool is no longer available
	 * @return array $response_array
	 * @deprecated GHash.io has been discontinued
	 */
	public function workers() {
		return array('error' => 'GHash.io mining pool has been discontinued. This method is no longer available.');
	}
}