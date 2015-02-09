<?php
/**
 * @copyright 2013 Thomas Tanghus (thomas@tanghus.net)
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\AppFramework\Http;

use OCP\Security\ISecureRandom;
use OCP\IConfig;

/**
 * Class RequestTest
 *
 * @package OC\AppFramework\Http
 */
class RequestTest extends \Test\TestCase {
	/** @var string */
	protected $stream = 'fakeinput://data';
	/** @var ISecureRandom */
	protected $secureRandom;
	/** @var IConfig */
	protected $config;

	protected function setUp() {
		parent::setUp();

		require_once __DIR__ . '/requeststream.php';
		if (in_array('fakeinput', stream_get_wrappers())) {
			stream_wrapper_unregister('fakeinput');
		}
		stream_wrapper_register('fakeinput', 'RequestStream');

		$this->secureRandom = $this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
	}

	protected function tearDown() {
		stream_wrapper_unregister('fakeinput');
		parent::tearDown();
	}

	public function testRequestAccessors() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		// Countable
		$this->assertEquals(2, count($request));
		// Array access
		$this->assertEquals('Joey', $request['nickname']);
		// "Magic" accessors
		$this->assertEquals('Joey', $request->{'nickname'});
		$this->assertTrue(isset($request['nickname']));
		$this->assertTrue(isset($request->{'nickname'}));
		$this->assertEquals(false, isset($request->{'flickname'}));
		// Only testing 'get', but same approach for post, files etc.
		$this->assertEquals('Joey', $request->get['nickname']);
		// Always returns null if variable not set.
		$this->assertEquals(null, $request->{'flickname'});

	}

	// urlParams has precedence over POST which has precedence over GET
	public function testPrecedence() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'post' => array('name' => 'Jane Doe', 'nickname' => 'Janey'),
			'urlParams' => array('user' => 'jw', 'name' => 'Johnny Weissmüller'),
			'method' => 'GET'
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals(3, count($request));
		$this->assertEquals('Janey', $request->{'nickname'});
		$this->assertEquals('Johnny Weissmüller', $request->{'name'});
	}


	/**
	* @expectedException \RuntimeException
	*/
	public function testImmutableArrayAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET'
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request['nickname'] = 'Janey';
	}

	/**
	* @expectedException \RuntimeException
	*/
	public function testImmutableMagicAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET'
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request->{'nickname'} = 'Janey';
	}

	/**
	* @expectedException \LogicException
	*/
	public function testGetTheMethodRight() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request->post;
	}

	public function testTheMethodIsRight() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('GET', $request->method);
		$result = $request->get;
		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
	}

	public function testJsonPost() {
		global $data;
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = array(
			'method' => 'POST',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8')
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('POST', $request->method);
		$result = $request->post;
		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
		$this->assertEquals('Joey', $request->params['nickname']);
		$this->assertEquals('Joey', $request['nickname']);
	}

	public function testPatch() {
		global $data;
		$data = http_build_query(array('name' => 'John Q. Public', 'nickname' => 'Joey'), '', '&');

		$vars = array(
			'method' => 'PATCH',
			'server' => array('CONTENT_TYPE' => 'application/x-www-form-urlencoded'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PATCH', $request->method);
		$result = $request->patch;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
	}

	public function testJsonPatchAndPut() {
		global $data;

		// PUT content
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = array(
			'method' => 'PUT',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PUT', $request->method);
		$result = $request->put;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);

		// PATCH content
		$data = '{"name": "John Q. Public", "nickname": null}';
		$vars = array(
			'method' => 'PATCH',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PATCH', $request->method);
		$result = $request->patch;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals(null, $result['nickname']);
	}

	public function testPutStream() {
		global $data;
		$data = file_get_contents(__DIR__ . '/../../../data/testimage.png');

		$vars = array(
			'put' => $data,
			'method' => 'PUT',
			'server' => array('CONTENT_TYPE' => 'image/png'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PUT', $request->method);
		$resource = $request->put;
		$contents = stream_get_contents($resource);
		$this->assertEquals($data, $contents);

		try {
			$resource = $request->put;
		} catch(\LogicException $e) {
			return;
		}
		$this->fail('Expected LogicException.');

	}


	public function testSetUrlParameters() {
		$vars = array(
			'post' => array(),
			'method' => 'POST',
			'urlParams' => array('id' => '2'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$newParams = array('id' => '3', 'test' => 'test2');
		$request->setUrlParameters($newParams);
		$this->assertEquals('test2', $request->getParam('test'));
		$this->assertEquals('3', $request->getParam('id'));
		$this->assertEquals('3', $request->getParams()['id']);
	}

	public function testGetIdWithModUnique() {
		$vars = [
			'server' => [
				'UNIQUE_ID' => 'GeneratedUniqueIdByModUnique'
			],
		];

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('GeneratedUniqueIdByModUnique', $request->getId());
	}

	public function testGetIdWithoutModUnique() {
		$lowRandomSource = $this->getMockBuilder('\OCP\Security\ISecureRandom')
			->disableOriginalConstructor()->getMock();
		$lowRandomSource->expects($this->once())
			->method('generate')
			->with('20')
			->will($this->returnValue('GeneratedByOwnCloudItself'));

		$this->secureRandom
			->expects($this->once())
			->method('getLowStrengthGenerator')
			->will($this->returnValue($lowRandomSource));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('GeneratedByOwnCloudItself', $request->getId());
	}

	public function testGetIdWithoutModUniqueStable() {
		$request = new Request(
			[],
			\OC::$server->getSecureRandom(),
			$this->config,
			$this->stream
		);
		$firstId = $request->getId();
		$secondId = $request->getId();
		$this->assertSame($firstId, $secondId);
	}

	public function testHasModificationTimeWithValue() {
		$request = new Request(
			[
				'server' => [
					'HTTP_X_OC_MTIME' => '1234567890',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame(1234567890, $request->hasModificationTime());
	}

	public function testHasModificationTimeWithoutValue() {
		$request = new Request([], $this->secureRandom, $this->config, $this->stream);
		$this->assertSame(false, $request->hasModificationTime());
	}

	public function testGetRemoteAddressWithoutTrustedRemote() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue([]));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('10.0.0.2', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithNoTrustedHeader() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue(['10.0.0.2']));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('forwarded_for_headers')
			->will($this->returnValue([]));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('10.0.0.2', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithSingleTrustedRemote() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue(['10.0.0.2']));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('forwarded_for_headers')
			->will($this->returnValue(['HTTP_X_FORWARDED']));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('10.4.0.5', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressVerifyPriorityHeader() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue(['10.0.0.2']));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('forwarded_for_headers')
			->will($this->returnValue([
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED'
			]));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('192.168.0.233', $request->getRemoteAddress());
	}

	public function testGetServerProtocolWithOverride() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue('customProtocol'));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('overwritecondaddr')
			->will($this->returnValue(''));
		$this->config
			->expects($this->at(2))
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue('customProtocol'));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('customProtocol', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithProtoValid() {
		$this->config
				->expects($this->exactly(2))
				->method('getSystemValue')
				->with('overwriteprotocol')
				->will($this->returnValue(''));

		$requestHttps = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'HtTpS'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$requestHttp = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'HTTp'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);


		$this->assertSame('https', $requestHttps->getServerProtocol());
		$this->assertSame('http', $requestHttp->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueOn() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[
				'server' => [
					'HTTPS' => 'on'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame('https', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueOff() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[
				'server' => [
					'HTTPS' => 'off'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

	public function testGetServerProtocolDefault() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

}
