<?php
/**
 * CEX API Mock Tests
 *
 * Tests using mock responses to verify API client behavior without network calls.
 *
 * Run tests with: ./vendor/bin/phpunit tests/CEXMockTest.php
 */

require_once __DIR__ . '/../cex.class.php';

/**
 * Testable CEX class that exposes internal method calls
 * Uses reflection to intercept and mock API calls
 */
class CEXTestable extends CEX {

	public $mockResponses = array();
	public $lastAction = null;
	public $lastParams = null;
	public $mockEnabled = true;

	public function __construct($api_key = false, $api_secret = false) {
		parent::__construct($api_key, $api_secret, false, false);
	}

	public function setMockResponse($action, $response) {
		$this->mockResponses[$action] = $response;
	}

	public function getLastAction() {
		return $this->lastAction;
	}

	public function getLastParams() {
		return $this->lastParams;
	}

	/**
	 * Override server_time to use mock
	 */
	public function server_time() {
		$this->lastAction = 'get_server_time';
		$this->lastParams = array();
		if ($this->mockEnabled && isset($this->mockResponses['get_server_time'])) {
			return $this->mockResponses['get_server_time'];
		}
		return parent::server_time();
	}

	/**
	 * Override pairs_info to use mock
	 */
	public function pairs_info() {
		$this->lastAction = 'get_pairs_info';
		$this->lastParams = array();
		if ($this->mockEnabled && isset($this->mockResponses['get_pairs_info'])) {
			return $this->mockResponses['get_pairs_info'];
		}
		return parent::pairs_info();
	}

	/**
	 * Override currencies_info to use mock
	 */
	public function currencies_info() {
		$this->lastAction = 'get_currencies_info';
		$this->lastParams = array();
		if ($this->mockEnabled && isset($this->mockResponses['get_currencies_info'])) {
			return $this->mockResponses['get_currencies_info'];
		}
		return parent::currencies_info();
	}

	/**
	 * Override ticker to use mock
	 */
	public function ticker($symbol_pair) {
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'get_ticker';
		$this->lastParams = array('pairs' => array($pair));
		if ($this->mockEnabled && isset($this->mockResponses['get_ticker'])) {
			$result = $this->mockResponses['get_ticker'];
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
		return parent::ticker($symbol_pair);
	}

	/**
	 * Override tickers to use mock
	 */
	public function tickers($symbol_pairs) {
		$pairs = array();
		foreach ($symbol_pairs as $pair) {
			$pairs[] = str_replace('/', '-', $pair);
		}
		$this->lastAction = 'get_ticker';
		$this->lastParams = array('pairs' => $pairs);
		if ($this->mockEnabled && isset($this->mockResponses['get_ticker'])) {
			return $this->mockResponses['get_ticker'];
		}
		return parent::tickers($symbol_pairs);
	}

	/**
	 * Override order_book to use mock
	 */
	public function order_book($symbol_pair, $depth = -1) {
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'get_order_book';
		$this->lastParams = array('pair' => $pair);
		if ($depth !== false && $depth > 0) {
			$this->lastParams['depth'] = intval($depth);
		}
		if ($this->mockEnabled && isset($this->mockResponses['get_order_book'])) {
			$result = $this->mockResponses['get_order_book'];
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
		return parent::order_book($symbol_pair, $depth);
	}

	/**
	 * Override trade_history to use mock
	 */
	public function trade_history($symbol_pair, $since = 0) {
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'get_trade_history';
		$this->lastParams = array('pair' => $pair);
		if ($since > 0) {
			$this->lastParams['since'] = intval($since);
		}
		if ($this->mockEnabled && isset($this->mockResponses['get_trade_history'])) {
			return $this->mockResponses['get_trade_history'];
		}
		return parent::trade_history($symbol_pair, $since);
	}

	/**
	 * Override candles to use mock
	 */
	public function candles($symbol_pair, $resolution = '1h', $from = null, $to = null) {
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'get_candles';
		$this->lastParams = array('pair' => $pair, 'resolution' => $resolution);
		if ($from !== null) {
			$this->lastParams['from'] = intval($from);
		}
		if ($to !== null) {
			$this->lastParams['to'] = intval($to);
		}
		if ($this->mockEnabled && isset($this->mockResponses['get_candles'])) {
			return $this->mockResponses['get_candles'];
		}
		return parent::candles($symbol_pair, $resolution, $from, $to);
	}

	/**
	 * Override balance to use mock
	 */
	public function balance() {
		$this->lastAction = 'get_my_wallet_balance';
		$this->lastParams = array();
		if ($this->mockEnabled && isset($this->mockResponses['get_my_wallet_balance'])) {
			return $this->mockResponses['get_my_wallet_balance'];
		}
		return parent::balance();
	}

	/**
	 * Override my_fee to use mock
	 */
	public function my_fee($symbol_pair) {
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'get_my_current_fee';
		$this->lastParams = array('pair' => $pair);
		if ($this->mockEnabled && isset($this->mockResponses['get_my_current_fee'])) {
			return $this->mockResponses['get_my_current_fee'];
		}
		return parent::my_fee($symbol_pair);
	}

	/**
	 * Override my_orders to use mock
	 */
	public function my_orders($symbol_pair = null, $status = 'open', $limit = 100) {
		$this->lastAction = 'get_my_orders';
		$this->lastParams = array();
		if ($symbol_pair !== null) {
			$this->lastParams['pair'] = str_replace('/', '-', $symbol_pair);
		}
		if ($status !== null) {
			$this->lastParams['status'] = $status;
		}
		if ($limit > 0) {
			$this->lastParams['limit'] = intval($limit);
		}
		if ($this->mockEnabled && isset($this->mockResponses['get_my_orders'])) {
			return $this->mockResponses['get_my_orders'];
		}
		return parent::my_orders($symbol_pair, $status, $limit);
	}

	/**
	 * Override place_order to use mock
	 */
	public function place_order($symbol_pair, $type, $amount, $price, $order_type = 'limit') {
		$valid_types = array('buy', 'sell');
		if (!in_array($type, $valid_types)) {
			return array('error' => 'Invalid order type: ' . $type . '. Must be buy or sell.');
		}
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'do_my_new_order';
		$this->lastParams = array(
			'pair' => $pair,
			'side' => $type,
			'type' => $order_type,
			'amount' => strval(floatval($amount)),
			'price' => strval(floatval($price))
		);
		if ($this->mockEnabled && isset($this->mockResponses['do_my_new_order'])) {
			return $this->mockResponses['do_my_new_order'];
		}
		return parent::place_order($symbol_pair, $type, $amount, $price, $order_type);
	}

	/**
	 * Override place_market_order to use mock
	 */
	public function place_market_order($symbol_pair, $type, $amount) {
		$valid_types = array('buy', 'sell');
		if (!in_array($type, $valid_types)) {
			return array('error' => 'Invalid order type: ' . $type . '. Must be buy or sell.');
		}
		$pair = str_replace('/', '-', $symbol_pair);
		$this->lastAction = 'do_my_new_order';
		$this->lastParams = array(
			'pair' => $pair,
			'side' => $type,
			'type' => 'market',
			'amount' => strval(floatval($amount))
		);
		if ($this->mockEnabled && isset($this->mockResponses['do_my_new_order'])) {
			return $this->mockResponses['do_my_new_order'];
		}
		return parent::place_market_order($symbol_pair, $type, $amount);
	}

	/**
	 * Override cancel_order to use mock
	 */
	public function cancel_order($order_id) {
		$this->lastAction = 'do_cancel_my_order';
		$this->lastParams = array('orderId' => strval($order_id));
		if ($this->mockEnabled && isset($this->mockResponses['do_cancel_my_order'])) {
			$result = $this->mockResponses['do_cancel_my_order'];
			if (isset($result['error'])) {
				return false;
			}
			return true;
		}
		return parent::cancel_order($order_id);
	}

	/**
	 * Override cancel_all_orders to use mock
	 */
	public function cancel_all_orders($symbol_pair = null) {
		$this->lastAction = 'do_cancel_all_orders';
		$this->lastParams = array();
		if ($symbol_pair !== null) {
			$this->lastParams['pair'] = str_replace('/', '-', $symbol_pair);
		}
		if ($this->mockEnabled && isset($this->mockResponses['do_cancel_all_orders'])) {
			return $this->mockResponses['do_cancel_all_orders'];
		}
		return parent::cancel_all_orders($symbol_pair);
	}

	/**
	 * Override transaction_history to use mock
	 */
	public function transaction_history($currency = null, $limit = 100) {
		$this->lastAction = 'get_my_transaction_history';
		$this->lastParams = array('limit' => intval($limit));
		if ($currency !== null) {
			$this->lastParams['currency'] = strtoupper($currency);
		}
		if ($this->mockEnabled && isset($this->mockResponses['get_my_transaction_history'])) {
			return $this->mockResponses['get_my_transaction_history'];
		}
		return parent::transaction_history($currency, $limit);
	}

	/**
	 * Override funding_history to use mock
	 */
	public function funding_history($currency = null, $limit = 100) {
		$this->lastAction = 'get_my_funding_history';
		$this->lastParams = array('limit' => intval($limit));
		if ($currency !== null) {
			$this->lastParams['currency'] = strtoupper($currency);
		}
		if ($this->mockEnabled && isset($this->mockResponses['get_my_funding_history'])) {
			return $this->mockResponses['get_my_funding_history'];
		}
		return parent::funding_history($currency, $limit);
	}

	/**
	 * Override deposit_address to use mock
	 */
	public function deposit_address($currency) {
		$this->lastAction = 'get_deposit_address';
		$this->lastParams = array('currency' => strtoupper($currency));
		if ($this->mockEnabled && isset($this->mockResponses['get_deposit_address'])) {
			return $this->mockResponses['get_deposit_address'];
		}
		return parent::deposit_address($currency);
	}

	/**
	 * Override internal_transfer to use mock
	 */
	public function internal_transfer($currency, $amount, $to_account) {
		$this->lastAction = 'do_my_internal_transfer';
		$this->lastParams = array(
			'currency' => strtoupper($currency),
			'amount' => strval(floatval($amount)),
			'toAccount' => $to_account
		);
		if ($this->mockEnabled && isset($this->mockResponses['do_my_internal_transfer'])) {
			return $this->mockResponses['do_my_internal_transfer'];
		}
		return parent::internal_transfer($currency, $amount, $to_account);
	}
}

/**
 * Tests using mock responses
 */
class CEXMockTest extends PHPUnit\Framework\TestCase {

	private $cex;

	protected function setUp(): void {
		$this->cex = new CEXTestable('test_key', 'test_secret');
	}

	/**
	 * Test server_time method
	 */
	public function testServerTime() {
		$mockResponse = array('timestamp' => 1700000000);
		$this->cex->setMockResponse('get_server_time', $mockResponse);

		$result = $this->cex->server_time();

		$this->assertEquals('get_server_time', $this->cex->getLastAction());
		$this->assertEquals(1700000000, $result['timestamp']);
	}

	/**
	 * Test pairs_info method
	 */
	public function testPairsInfo() {
		$mockResponse = array(
			'BTC-USD' => array('base' => 'BTC', 'quote' => 'USD'),
			'ETH-USD' => array('base' => 'ETH', 'quote' => 'USD')
		);
		$this->cex->setMockResponse('get_pairs_info', $mockResponse);

		$result = $this->cex->pairs_info();

		$this->assertEquals('get_pairs_info', $this->cex->getLastAction());
		$this->assertArrayHasKey('BTC-USD', $result);
		$this->assertArrayHasKey('ETH-USD', $result);
	}

	/**
	 * Test symbols method converts format correctly
	 */
	public function testSymbolsConvertsFormat() {
		$mockResponse = array(
			'BTC-USD' => array('base' => 'BTC', 'quote' => 'USD'),
			'ETH-EUR' => array('base' => 'ETH', 'quote' => 'EUR')
		);
		$this->cex->setMockResponse('get_pairs_info', $mockResponse);

		$result = $this->cex->symbols();

		$this->assertContains('BTC/USD', $result);
		$this->assertContains('ETH/EUR', $result);
		$this->assertNotContains('BTC-USD', $result);
	}

	/**
	 * Test symbols caching
	 */
	public function testSymbolsCaching() {
		$mockResponse = array(
			'BTC-USD' => array('base' => 'BTC', 'quote' => 'USD')
		);
		$this->cex->setMockResponse('get_pairs_info', $mockResponse);

		// First call
		$result1 = $this->cex->symbols();

		// Change mock response
		$this->cex->setMockResponse('get_pairs_info', array(
			'ETH-USD' => array('base' => 'ETH', 'quote' => 'USD')
		));

		// Second call should return cached result
		$result2 = $this->cex->symbols();

		$this->assertEquals($result1, $result2);
		$this->assertContains('BTC/USD', $result2);
	}

	/**
	 * Test ticker method transforms response
	 */
	public function testTickerTransformsResponse() {
		$mockResponse = array(
			'BTC-USD' => array(
				'lastTradePx' => '50000.00',
				'high24' => '51000.00',
				'low24' => '49000.00',
				'vol24' => '1000.5',
				'bestBidPx' => '49999.00',
				'bestAskPx' => '50001.00'
			)
		);
		$this->cex->setMockResponse('get_ticker', $mockResponse);

		$result = $this->cex->ticker('BTC/USD');

		$this->assertEquals('get_ticker', $this->cex->getLastAction());
		$this->assertEquals('50000.00', $result['last']);
		$this->assertEquals('51000.00', $result['high']);
		$this->assertEquals('49000.00', $result['low']);
		$this->assertEquals('1000.5', $result['volume']);
		$this->assertEquals('49999.00', $result['bid']);
		$this->assertEquals('50001.00', $result['ask']);
		$this->assertEquals('BTC/USD', $result['pair']);
		$this->assertArrayHasKey('raw', $result);
	}

	/**
	 * Test tickers method with multiple pairs
	 */
	public function testTickersMultiplePairs() {
		$mockResponse = array(
			'BTC-USD' => array('lastTradePx' => '50000.00'),
			'ETH-USD' => array('lastTradePx' => '3000.00')
		);
		$this->cex->setMockResponse('get_ticker', $mockResponse);

		$result = $this->cex->tickers(array('BTC/USD', 'ETH/USD'));

		$params = $this->cex->getLastParams();
		$this->assertContains('BTC-USD', $params['pairs']);
		$this->assertContains('ETH-USD', $params['pairs']);
	}

	/**
	 * Test last_price method
	 */
	public function testLastPrice() {
		$mockResponse = array(
			'BTC-USD' => array('lastTradePx' => '50000.00')
		);
		$this->cex->setMockResponse('get_ticker', $mockResponse);

		$result = $this->cex->last_price('BTC/USD');

		$this->assertEquals('50000.00', $result['lprice']);
		$this->assertEquals('BTC/USD', $result['pair']);
	}

	/**
	 * Test order_book method
	 */
	public function testOrderBook() {
		$mockResponse = array(
			'timestamp' => 1700000000,
			'bids' => array(array('49999.00', '1.5'), array('49998.00', '2.0')),
			'asks' => array(array('50001.00', '1.0'), array('50002.00', '1.5'))
		);
		$this->cex->setMockResponse('get_order_book', $mockResponse);

		$result = $this->cex->order_book('BTC/USD', 10);

		$this->assertEquals('get_order_book', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
		$this->assertEquals(10, $params['depth']);

		$this->assertArrayHasKey('bids', $result);
		$this->assertArrayHasKey('asks', $result);
		$this->assertEquals('BTC/USD', $result['pair']);
	}

	/**
	 * Test order_book without depth parameter
	 */
	public function testOrderBookWithoutDepth() {
		$mockResponse = array(
			'bids' => array(),
			'asks' => array()
		);
		$this->cex->setMockResponse('get_order_book', $mockResponse);

		$this->cex->order_book('BTC/USD');

		$params = $this->cex->getLastParams();
		$this->assertArrayNotHasKey('depth', $params);
	}

	/**
	 * Test trade_history method
	 */
	public function testTradeHistory() {
		$mockResponse = array(
			array('tid' => 1, 'amount' => '0.5', 'price' => '50000'),
			array('tid' => 2, 'amount' => '1.0', 'price' => '50001')
		);
		$this->cex->setMockResponse('get_trade_history', $mockResponse);

		$result = $this->cex->trade_history('BTC/USD', 100);

		$this->assertEquals('get_trade_history', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
		$this->assertEquals(100, $params['since']);
	}

	/**
	 * Test candles method
	 */
	public function testCandles() {
		$mockResponse = array(
			array(1700000000, '50000', '51000', '49000', '50500', '100')
		);
		$this->cex->setMockResponse('get_candles', $mockResponse);

		$from = 1699900000;
		$to = 1700000000;
		$result = $this->cex->candles('BTC/USD', '1h', $from, $to);

		$this->assertEquals('get_candles', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
		$this->assertEquals('1h', $params['resolution']);
		$this->assertEquals($from, $params['from']);
		$this->assertEquals($to, $params['to']);
	}

	/**
	 * Test balance method
	 */
	public function testBalance() {
		$mockResponse = array(
			'BTC' => array('available' => '1.5', 'orders' => '0.5'),
			'USD' => array('available' => '10000', 'orders' => '0')
		);
		$this->cex->setMockResponse('get_my_wallet_balance', $mockResponse);

		$result = $this->cex->balance();

		$this->assertEquals('get_my_wallet_balance', $this->cex->getLastAction());
		$this->assertArrayHasKey('BTC', $result);
		$this->assertArrayHasKey('USD', $result);
	}

	/**
	 * Test my_fee method
	 */
	public function testMyFee() {
		$mockResponse = array('makerFee' => '0.001', 'takerFee' => '0.002');
		$this->cex->setMockResponse('get_my_current_fee', $mockResponse);

		$result = $this->cex->my_fee('BTC/USD');

		$this->assertEquals('get_my_current_fee', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
	}

	/**
	 * Test my_orders method
	 */
	public function testMyOrders() {
		$mockResponse = array(
			array('orderId' => '123', 'status' => 'open')
		);
		$this->cex->setMockResponse('get_my_orders', $mockResponse);

		$result = $this->cex->my_orders('BTC/USD', 'open', 50);

		$this->assertEquals('get_my_orders', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
		$this->assertEquals('open', $params['status']);
		$this->assertEquals(50, $params['limit']);
	}

	/**
	 * Test open_orders method (backward compatible)
	 */
	public function testOpenOrders() {
		$mockResponse = array();
		$this->cex->setMockResponse('get_my_orders', $mockResponse);

		$this->cex->open_orders('BTC/USD');

		$params = $this->cex->getLastParams();
		$this->assertEquals('open', $params['status']);
	}

	/**
	 * Test place_order method
	 */
	public function testPlaceOrder() {
		$mockResponse = array('orderId' => '12345', 'status' => 'pending');
		$this->cex->setMockResponse('do_my_new_order', $mockResponse);

		$result = $this->cex->place_order('BTC/USD', 'buy', 0.001, 50000);

		$this->assertEquals('do_my_new_order', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
		$this->assertEquals('buy', $params['side']);
		$this->assertEquals('limit', $params['type']);
		$this->assertEquals('0.001', $params['amount']);
		$this->assertEquals('50000', $params['price']);
	}

	/**
	 * Test place_market_order method
	 */
	public function testPlaceMarketOrder() {
		$mockResponse = array('orderId' => '12346', 'status' => 'filled');
		$this->cex->setMockResponse('do_my_new_order', $mockResponse);

		$result = $this->cex->place_market_order('BTC/USD', 'sell', 0.5);

		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
		$this->assertEquals('sell', $params['side']);
		$this->assertEquals('market', $params['type']);
		$this->assertEquals('0.5', $params['amount']);
		$this->assertArrayNotHasKey('price', $params);
	}

	/**
	 * Test cancel_order method success
	 */
	public function testCancelOrderSuccess() {
		$mockResponse = array('status' => 'canceled');
		$this->cex->setMockResponse('do_cancel_my_order', $mockResponse);

		$result = $this->cex->cancel_order('12345');

		$this->assertTrue($result);
		$params = $this->cex->getLastParams();
		$this->assertEquals('12345', $params['orderId']);
	}

	/**
	 * Test cancel_order method failure
	 */
	public function testCancelOrderFailure() {
		$mockResponse = array('error' => 'Order not found');
		$this->cex->setMockResponse('do_cancel_my_order', $mockResponse);

		$result = $this->cex->cancel_order('99999');

		$this->assertFalse($result);
	}

	/**
	 * Test cancel_all_orders method
	 */
	public function testCancelAllOrders() {
		$mockResponse = array('canceled' => 5);
		$this->cex->setMockResponse('do_cancel_all_orders', $mockResponse);

		$result = $this->cex->cancel_all_orders('BTC/USD');

		$this->assertEquals('do_cancel_all_orders', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC-USD', $params['pair']);
	}

	/**
	 * Test cancel_all_orders without pair
	 */
	public function testCancelAllOrdersNoPair() {
		$mockResponse = array('canceled' => 10);
		$this->cex->setMockResponse('do_cancel_all_orders', $mockResponse);

		$this->cex->cancel_all_orders();

		$params = $this->cex->getLastParams();
		$this->assertArrayNotHasKey('pair', $params);
	}

	/**
	 * Test transaction_history method
	 */
	public function testTransactionHistory() {
		$mockResponse = array(
			array('id' => 1, 'type' => 'trade', 'amount' => '100')
		);
		$this->cex->setMockResponse('get_my_transaction_history', $mockResponse);

		$result = $this->cex->transaction_history('BTC', 50);

		$this->assertEquals('get_my_transaction_history', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC', $params['currency']);
		$this->assertEquals(50, $params['limit']);
	}

	/**
	 * Test funding_history method
	 */
	public function testFundingHistory() {
		$mockResponse = array(
			array('id' => 1, 'type' => 'deposit', 'amount' => '1.0')
		);
		$this->cex->setMockResponse('get_my_funding_history', $mockResponse);

		$result = $this->cex->funding_history('ETH', 25);

		$this->assertEquals('get_my_funding_history', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('ETH', $params['currency']);
		$this->assertEquals(25, $params['limit']);
	}

	/**
	 * Test deposit_address method
	 */
	public function testDepositAddress() {
		$mockResponse = array('address' => '1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2');
		$this->cex->setMockResponse('get_deposit_address', $mockResponse);

		$result = $this->cex->deposit_address('btc');

		$this->assertEquals('get_deposit_address', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC', $params['currency']); // Should be uppercase
	}

	/**
	 * Test internal_transfer method
	 */
	public function testInternalTransfer() {
		$mockResponse = array('status' => 'success');
		$this->cex->setMockResponse('do_my_internal_transfer', $mockResponse);

		$result = $this->cex->internal_transfer('btc', 0.5, 'account123');

		$this->assertEquals('do_my_internal_transfer', $this->cex->getLastAction());
		$params = $this->cex->getLastParams();
		$this->assertEquals('BTC', $params['currency']);
		$this->assertEquals('0.5', $params['amount']);
		$this->assertEquals('account123', $params['toAccount']);
	}

	/**
	 * Test currencies_info method
	 */
	public function testCurrenciesInfo() {
		$mockResponse = array(
			'BTC' => array('name' => 'Bitcoin'),
			'ETH' => array('name' => 'Ethereum')
		);
		$this->cex->setMockResponse('get_currencies_info', $mockResponse);

		$result = $this->cex->currencies_info();

		$this->assertEquals('get_currencies_info', $this->cex->getLastAction());
		$this->assertArrayHasKey('BTC', $result);
		$this->assertArrayHasKey('ETH', $result);
	}
}
