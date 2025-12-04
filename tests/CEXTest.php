<?php
/**
 * CEX API Client Unit Tests
 *
 * Basic unit tests for the CEX.io Spot Trading API PHP client
 *
 * Run tests with: ./vendor/bin/phpunit tests/CEXTest.php
 */

require_once __DIR__ . '/../cex.class.php';

class CEXTest extends PHPUnit\Framework\TestCase {

	private $cex;
	private $cexWithAuth;

	protected function setUp(): void {
		// Create instance without authentication for public method tests
		$this->cex = new CEX();

		// Create instance with mock credentials for private method tests
		$this->cexWithAuth = new CEX('test_api_key', 'test_api_secret');
	}

	/**
	 * Test constructor with no arguments
	 */
	public function testConstructorWithoutCredentials() {
		$cex = new CEX();
		$this->assertInstanceOf(CEX::class, $cex);
	}

	/**
	 * Test constructor with credentials
	 */
	public function testConstructorWithCredentials() {
		$cex = new CEX('api_key', 'api_secret', 'cacert.pem', true);
		$this->assertInstanceOf(CEX::class, $cex);
	}

	/**
	 * Test symbol conversion from old format (BTC/USD) to new format (BTC-USD)
	 */
	public function testConvertSymbolToNewFormat() {
		$reflection = new ReflectionClass($this->cex);
		$method = $reflection->getMethod('_convertSymbol');
		$method->setAccessible(true);

		$this->assertEquals('BTC-USD', $method->invoke($this->cex, 'BTC/USD'));
		$this->assertEquals('ETH-EUR', $method->invoke($this->cex, 'ETH/EUR'));
		$this->assertEquals('XRP-BTC', $method->invoke($this->cex, 'XRP/BTC'));
		// Already in new format should remain unchanged
		$this->assertEquals('BTC-USD', $method->invoke($this->cex, 'BTC-USD'));
	}

	/**
	 * Test symbol conversion from new format (BTC-USD) to old format (BTC/USD)
	 */
	public function testConvertSymbolToOldFormat() {
		$reflection = new ReflectionClass($this->cex);
		$method = $reflection->getMethod('_convertSymbolToOld');
		$method->setAccessible(true);

		$this->assertEquals('BTC/USD', $method->invoke($this->cex, 'BTC-USD'));
		$this->assertEquals('ETH/EUR', $method->invoke($this->cex, 'ETH-EUR'));
		$this->assertEquals('XRP/BTC', $method->invoke($this->cex, 'XRP-BTC'));
		// Already in old format should remain unchanged
		$this->assertEquals('BTC/USD', $method->invoke($this->cex, 'BTC/USD'));
	}

	/**
	 * Test signature generation
	 */
	public function testSignatureGeneration() {
		$cex = new CEX('test_key', 'test_secret');
		$reflection = new ReflectionClass($cex);
		$method = $reflection->getMethod('_signature');
		$method->setAccessible(true);

		$path = '/api/spot/rest/get_my_wallet_balance';
		$timestamp = 1700000000;
		$body = '{}';

		$signature = $method->invoke($cex, $path, $timestamp, $body);

		// Verify signature is a valid hex string (64 chars for SHA256)
		$this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);

		// Verify signature is deterministic
		$signature2 = $method->invoke($cex, $path, $timestamp, $body);
		$this->assertEquals($signature, $signature2);

		// Verify different inputs produce different signatures
		$signature3 = $method->invoke($cex, $path, $timestamp + 1, $body);
		$this->assertNotEquals($signature, $signature3);
	}

	/**
	 * Test that private methods require authentication
	 */
	public function testPrivateMethodsRequireAuth() {
		$cex = new CEX(); // No credentials

		$result = $cex->balance();
		$this->assertArrayHasKey('error', $result);
		$this->assertStringContainsString('API key and secret are required', $result['error']);
	}

	/**
	 * Test place_order validates order type
	 */
	public function testPlaceOrderValidatesType() {
		$result = $this->cexWithAuth->place_order('BTC/USD', 'invalid', 0.001, 50000);

		$this->assertArrayHasKey('error', $result);
		$this->assertStringContainsString('Invalid order type', $result['error']);
		$this->assertStringContainsString('buy or sell', $result['error']);
	}

	/**
	 * Test place_market_order validates order type
	 */
	public function testPlaceMarketOrderValidatesType() {
		$result = $this->cexWithAuth->place_market_order('BTC/USD', 'invalid', 0.001);

		$this->assertArrayHasKey('error', $result);
		$this->assertStringContainsString('Invalid order type', $result['error']);
	}

	/**
	 * Test API URL constants
	 */
	public function testApiUrlConstants() {
		$this->assertEquals(
			'https://trade.cex.io/api/spot/rest-public',
			CEX::API_URL_PUBLIC
		);
		$this->assertEquals(
			'https://trade.cex.io/api/spot/rest',
			CEX::API_URL_PRIVATE
		);
	}
}
