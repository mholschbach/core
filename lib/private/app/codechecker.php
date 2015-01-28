<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\App;

use OC\Hooks\BasicEmitter;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class CodeChecker extends BasicEmitter {

	const CLASS_EXTENDS_NOT_ALLOWED = 1000;
	const CLASS_IMPLEMENTS_NOT_ALLOWED = 1001;
	const STATIC_CALL_NOT_ALLOWED = 1002;
	const CLASS_CONST_FETCH_NOT_ALLOWED = 1003;
	const CLASS_NEW_FETCH_NOT_ALLOWED =  1004;

	public function __construct() {
		$this->parser = new Parser(new Lexer);
		$this->blackListedClassNames = [
			// classes replaced by the public api
			'OC_API',
			'OC_App',
			'OC_AppConfig',
			'OC_Avatar',
			'OC_BackgroundJob',
			'OC_Config',
			'OC_DB',
			'OC_Files',
			'OC_Helper',
			'OC_Hook',
			'OC_Image',
			'OC_JSON',
			'OC_L10N',
			'OC_Log',
			'OC_Mail',
			'OC_Preferences',
			'OC_Request',
			'OC_Response',
			'OC_Template',
			'OC_User',
			'OC_Util',
		];
	}

	/**
	 * @param string $appId
	 * @return array
	 */
	public function analyse($appId) {
		$appPath = \OC_App::getAppPath($appId);
		if ($appPath === false) {
			throw new \RuntimeException("No app with given id <$appId> known.");
		}

		$errors = [];

		$excludes = array_map(function($item) use ($appPath) {
			return $appPath . '/' . $item;
		}, ['vendor', '3rdparty', '.git', 'l10n']);

		$iterator = new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveCallbackFilterIterator($iterator, function($item) use ($appPath, $excludes){
			/** @var SplFileInfo $item */
			foreach($excludes as $exclude) {
				if (substr($item->getPath(), 0, strlen($exclude)) === $exclude) {
					return false;
				}
			}
			return true;
		});
		$iterator = new RecursiveIteratorIterator($iterator);
		$iterator = new RegexIterator($iterator, '/^.+\.php$/i');

		foreach ($iterator as $file) {
			/** @var SplFileInfo $file */
			$this->emit('CodeChecker', 'analyseFileBegin', [$file->getPathname()]);
			$errors = array_merge($this->analyseFile($file), $errors);
			$this->emit('CodeChecker', 'analyseFileFinished', [$errors]);
		}

		return $errors;
	}

	/**
	 * @param string $file
	 * @return array
	 */
	public function analyseFile($file) {
		$code = file_get_contents($file);
		$statements = $this->parser->parse($code);

		$visitor = new CodeCheckVisitor($this->blackListedClassNames);
		$traverser = new NodeTraverser;
		$traverser->addVisitor($visitor);

		$traverser->traverse($statements);

		return $visitor->errors;
	}
}


class CodeCheckVisitor extends NodeVisitorAbstract {

	public function __construct($blackListedClassNames) {
		$this->blackListedClassNames = array_map('strtolower', $blackListedClassNames);
	}

	public $errors = [];

	public function enterNode(Node $node) {
		if ($node instanceof Node\Stmt\Class_) {
			if (!is_null($node->extends)) {
				$this->checkBlackList($node->extends->toString(), CodeChecker::CLASS_EXTENDS_NOT_ALLOWED, $node);
			}
			foreach ($node->implements as $implements) {
				$this->checkBlackList($implements->toString(), CodeChecker::CLASS_IMPLEMENTS_NOT_ALLOWED, $node);
			}
		}
		if ($node instanceof Node\Expr\StaticCall) {
			if (!is_null($node->class)) {
				if ($node->class instanceof Name) {
					$this->checkBlackList($node->class->toString(), CodeChecker::STATIC_CALL_NOT_ALLOWED, $node);
				}
				if ($node->class instanceof Node\Expr\Variable) {
					/**
					 * TODO: find a way to detect something like this:
					 *       $c = "OC_API";
					 *       $n = $i::call();
					 */
				}
			}
		}
		if ($node instanceof Node\Expr\ClassConstFetch) {
			if (!is_null($node->class)) {
				if ($node->class instanceof Name) {
					$this->checkBlackList($node->class->toString(), CodeChecker::CLASS_CONST_FETCH_NOT_ALLOWED, $node);
				}
				if ($node->class instanceof Node\Expr\Variable) {
					/**
					 * TODO: find a way to detect something like this:
					 *       $c = "OC_API";
					 *       $n = $i::ADMIN_AUTH;
					 */
				}
			}
		}
		if ($node instanceof Node\Expr\New_) {
			if (!is_null($node->class)) {
				if ($node->class instanceof Name) {
					$this->checkBlackList($node->class->toString(), CodeChecker::CLASS_NEW_FETCH_NOT_ALLOWED, $node);
				}
				if ($node->class instanceof Node\Expr\Variable) {
					/**
					 * TODO: find a way to detect something like this:
					 *       $c = "OC_API";
					 *       $n = new $i;
					 */
				}
			}
		}
	}

	private function checkBlackList($name, $errorCode, Node $node) {
		if (in_array(strtolower($name), $this->blackListedClassNames)) {
			$this->errors[]= [
				'disallowedToken' => $name,
				'errorCode' => $errorCode,
				'line' => $node->getLine(),
				'reason' => $this->buildReason($name, $errorCode)
			];
		}
	}

	private function buildReason($name, $errorCode) {
		static $errorMessages= [
			CodeChecker::CLASS_EXTENDS_NOT_ALLOWED => "used as base class",
			CodeChecker::CLASS_IMPLEMENTS_NOT_ALLOWED => "used as interface",
			CodeChecker::STATIC_CALL_NOT_ALLOWED => "static method call on private class",
			CodeChecker::CLASS_CONST_FETCH_NOT_ALLOWED => "used to fetch a const from",
			CodeChecker::CLASS_NEW_FETCH_NOT_ALLOWED => "is instanciated",
		];

		if (isset($errorMessages[$errorCode])) {
			return $errorMessages[$errorCode];
		}

		return "$name usage not allowed - error: $errorCode";
	}
}
