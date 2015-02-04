<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

use \OCP\ICacheFactory;

class Factory implements ICacheFactory {
	/**
	 * @var string $globalPrefix
	 */
	private $globalPrefix;

	/**
	 * @param string $globalPrefix
	 */
	public function __construct($globalPrefix) {
		$this->globalPrefix = $globalPrefix;
	}

	/**
	 * get a cache instance, or Null backend if no backend available
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	function create($prefix = '') {
		$prefix = $this->globalPrefix . '/' . $prefix;
		$logger = \OC::$server->getLogger();
		if (XCache::isAvailable()) {
			$logger->debug('creating xcache instance', array('app'=>'memcache'));
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			$logger->debug('creating APCu instance', array('app'=>'memcache'));
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			$logger->debug('creating APC instance', array('app'=>'memcache'));
			return new APC($prefix);
		} elseif (Redis::isAvailable()) {
			$logger->debug('creating redis instance', array('app'=>'memcache'));
			return new Redis($prefix);
		} elseif (Memcached::isAvailable()) {
			$logger->debug('creating memcached instance', array('app'=>'memcache'));
			return new Memcached($prefix);
		} else {
			$logger->debug('no cache available instance', array('app'=>'memcache'));
			return new Null($prefix);
		}
	}

	/**
	 * check if there is a memcache backend available
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable() || Redis::isAvailable() || Memcached::isAvailable();
	}

	/**
	 * get a in-server cache instance, will return null if no backend is available
	 *
	 * @param string $prefix
	 * @return null|Cache
	 */
	public function createLowLatency($prefix = '') {
		$prefix = $this->globalPrefix . '/' . $prefix;
		$logger = \OC::$server->getLogger();
		if (XCache::isAvailable()) {
			$logger->debug('creating xcache instance for low latency', array('app'=>'memcache'));
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			$logger->debug('creating APCu instance for low latency', array('app'=>'memcache'));
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			$logger->debug('creating APC instance for low latency', array('app'=>'memcache'));
			return new APC($prefix);
		} else {
			$logger->debug('no low latency cache available', array('app'=>'memcache'));
			return null;
		}
	}

	/**
	 * check if there is a in-server backend available
	 *
	 * @return bool
	 */
	public function isAvailableLowLatency() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable();
	}


}
