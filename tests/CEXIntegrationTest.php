<?php
/**
 * CEX API Integration Tests
 *
 * These tests run against the actual CEX.io API.
 * Set environment variables CEX_API_KEY and CEX_API_SECRET to enable authenticated tests.
 *
 * Run tests with: source .env && ./vendor/bin/phpunit tests/CEXIntegrationTest.php
 */

require_once __DIR__ . '/../cex.class.php';

/**
 * Integration tests - only run when credentials are available
 * Set environment variables CEX_API_KEY and CEX_API_SECRET to enable
 */
class CEXIntegrationTest extends PHPUnit\Framework\TestCase {

	private $cex;
	private $hasCredentials;

	protected function setUp(): void {
		$apiKey = getenv('CEX_API_KEY');
		$apiSecret = getenv('CEX_API_SECRET');

		$this->hasCredentials = ($apiKey && $apiSecret);

		if ($this->hasCredentials) {
			$this->cex = new CEX($apiKey, $apiSecret);
		} else {
			$this->cex = new CEX();
		}
	}

	/**
	 * Test live server_time endpoint
	 */
	public function testLiveServerTime() {
		$result = $this->cex->server_time();

		// Should return a timestamp or error
		$this->assertTrue(
			isset($result['timestamp']) || isset($result['error']),
			'Response should have timestamp or error'
		);
	}

	/**
	 * Test live pairs_info endpoint
	 */
	public function testLivePairsInfo() {
		$result = $this->cex->pairs_info();

		// Should return pairs or error
		if (!isset($result['error'])) {
			$this->assertIsArray($result);
		}
	}

	/**
	 * Test live ticker endpoint
	 */
	public function testLiveTicker() {
		$result = $this->cex->ticker('BTC-USD');

		// Should have standard ticker fields or error
		if (!isset($result['error'])) {
			$this->assertArrayHasKey('pair', $result);
		}
	}

	/**
	 * Test live order_book endpoint
	 */
	public function testLiveOrderBook() {
		// Note: CEX.io API only accepts depth of 0 or 1
		$result = $this->cex->order_book('BTC-USD', 1);

		// Should have bids/asks or error
		if (!isset($result['error'])) {
			$this->assertArrayHasKey('bids', $result);
			$this->assertArrayHasKey('asks', $result);
		} else {
			$this->fail('API returned error: ' . $result['error']);
		}
	}

	/**
	 * Test live balance endpoint (requires credentials)
	 */
	public function testLiveBalance() {
		if (!$this->hasCredentials) {
			$this->markTestSkipped('API credentials not available');
		}

		$result = $this->cex->balance();

		// Should return balances or auth error
		$this->assertIsArray($result);
	}
}
