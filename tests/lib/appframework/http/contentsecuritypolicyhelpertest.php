<?php
/**
 * Copyright (c) 2015 Lukas Reschke lukas@owncloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OC\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicyHelper;

/**
 * Class ContentSecurityPolicyHelperTest
 *
 * @package OC\AppFramework\Http
 */
class ContentSecurityPolicyHelperTest extends \Test\TestCase {

	/** @var ContentSecurityPolicyHelper */
	private $contentSecurityPolicyHelper;

	public function setUp() {
		parent::setUp();
		$this->contentSecurityPolicyHelper = new ContentSecurityPolicyHelper();
	}

	public function testGetPolicyDefault() {
		$defaultPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";
		$this->assertSame($defaultPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyScriptDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' www.owncloud.com 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedScriptDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyScriptDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' www.owncloud.com www.owncloud.org 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedScriptDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedScriptDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyScriptAllowInline() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-inline' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->inlineScriptState(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyScriptAllowInlineWithDomain() {
		$expectedPolicy = "default-src 'none';script-src 'self' www.owncloud.com 'unsafe-inline' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedScriptDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->inlineScriptState(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyScriptDisallowInlineAndEval() {
		$expectedPolicy = "default-src 'none';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->inlineScriptState(false);
		$this->contentSecurityPolicyHelper->evalScriptState(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyStyleDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' www.owncloud.com 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedStyleDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyStyleDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' www.owncloud.com www.owncloud.org 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedStyleDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedStyleDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyStyleAllowInline() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->inlineStyleState(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyStyleAllowInlineWithDomain() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' www.owncloud.com 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedStyleDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyStyleDisallowInline() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->inlineStyleState(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyImageDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' www.owncloud.com;font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedImageDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyImageDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' www.owncloud.com www.owncloud.org;font-src 'self';connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedImageDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedImageDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyFontDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self' www.owncloud.com;connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedFontDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyFontDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self' www.owncloud.com www.owncloud.org;connect-src 'self';media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedFontDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedFontDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyConnectDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self' www.owncloud.com;media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedConnectDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyConnectDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self' www.owncloud.com www.owncloud.org;media-src 'self'";

		$this->contentSecurityPolicyHelper->addAllowedConnectDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedConnectDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyMediaDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self' www.owncloud.com";

		$this->contentSecurityPolicyHelper->addAllowedMediaDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyMediaDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self' www.owncloud.com www.owncloud.org";

		$this->contentSecurityPolicyHelper->addAllowedMediaDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedMediaDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyObjectDomainValid() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self';object-src www.owncloud.com";

		$this->contentSecurityPolicyHelper->addAllowedObjectDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyObjectDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self';object-src www.owncloud.com www.owncloud.org";

		$this->contentSecurityPolicyHelper->addAllowedObjectDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedObjectDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}


	public function testGetAllowedFrameDomain() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self';frame-src www.owncloud.com";

		$this->contentSecurityPolicyHelper->addAllowedFrameDomain('www.owncloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testGetPolicyFrameDomainValidMultiple() {
		$expectedPolicy = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self';frame-src www.owncloud.com www.owncloud.org";

		$this->contentSecurityPolicyHelper->addAllowedFrameDomain('www.owncloud.com');
		$this->contentSecurityPolicyHelper->addAllowedFrameDomain('www.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}

	public function testConfigureStacked() {
		$expectedPolicy = "default-src 'none';script-src 'self' script.owncloud.org;style-src 'self' style.owncloud.org;img-src 'self' img.owncloud.org;font-src 'self' font.owncloud.org;connect-src 'self' connect.owncloud.org;media-src 'self' media.owncloud.org;object-src objects.owncloud.org;frame-src frame.owncloud.org";

		$this->contentSecurityPolicyHelper->inlineStyleState(false)
			->evalScriptState(false)
			->addAllowedScriptDomain('script.owncloud.org')
			->inlineStyleState(false)
			->addAllowedStyleDomain('style.owncloud.org')
			->addAllowedFontDomain('font.owncloud.org')
			->addAllowedImageDomain('img.owncloud.org')
			->addAllowedConnectDomain('connect.owncloud.org')
			->addAllowedMediaDomain('media.owncloud.org')
			->addAllowedObjectDomain('objects.owncloud.org')
			->addAllowedFrameDomain('frame.owncloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicyHelper->getPolicy());
	}
}
