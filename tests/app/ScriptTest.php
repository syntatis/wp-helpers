<?php

declare(strict_types=1);

namespace Syntatis\WPHelpers\Tests;

use Syntatis\WPHelpers\Contracts\InlineScript;
use Syntatis\WPHelpers\Enqueue\Script;

class ScriptTest extends WPTestCase
{
	public function testGet(): void
	{
		$script = new Script([
			'url' => 'https://example.com/assets/script.js',
			'handle' => 'script',
			'dependencies' => ['jquery'],
			'version' => '1.0.0',
			'localized' => true,
		]);

		$this->assertSame([
			'url' => 'https://example.com/assets/script.js',
			'handle' => 'script',
			'dependencies' => ['jquery'],
			'version' => '1.0.0',
			'localized' => true,
			'inline' => [],
		], $script->get());
	}

	public function testGetWithInlineScripts(): void
	{
		$inlineScript = new class implements InlineScript {
			public function getPosition(): string
			{
				return 'before';
			}

			public function __toString(): string
			{
				return 'console.log("Hello, World!");';
			}
		};

		$script = new Script([
			'url' => 'https://example.com/assets/script.js',
			'handle' => 'script',
			'dependencies' => ['jquery'],
			'version' => '1.0.0',
			'localized' => true,
		]);
		$script->withInlineScripts($inlineScript);

		$this->assertSame([
			'url' => 'https://example.com/assets/script.js',
			'handle' => 'script',
			'dependencies' => ['jquery'],
			'version' => '1.0.0',
			'localized' => true,
			'inline' => [
				[
					'position' => 'before',
					'data' => 'console.log("Hello, World!");',
				],
			],
		], $script->get());
	}
}
