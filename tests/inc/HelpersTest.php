<?php

declare(strict_types=1);

namespace Syntatis\WPHelpers\Tests;

use function Syntatis\WPHelpers\is_plugin_updated;

class HelpersTest extends WPTestCase
{
	public function testIsPluginUpdated(): void
	{
		$pluginBasename = 'plugin-folder/plugin-file.php';
		$hookExtra = [
			'action' => 'update',
			'type' => 'plugin',
			'plugins' => [
				'plugin-folder/plugin-file.php',
				'other-plugin/plugin-file.php',
			],
		];

		$this->assertTrue(is_plugin_updated($pluginBasename, $hookExtra));

		$hookExtra['plugins'] = ['other-plugin/plugin-file.php'];

		$this->assertFalse(is_plugin_updated($pluginBasename, $hookExtra));
	}
}
