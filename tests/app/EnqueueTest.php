<?php

declare(strict_types=1);

namespace Syntatis\WPHelpers\Tests;

use _WP_Dependency;
use Syntatis\WPHelpers\Contracts\InlineScript;
use Syntatis\WPHelpers\Enqueue\Enqueue;
use WP_Scripts;
use WP_Styles;

use function dirname;

class EnqueueTest extends WPTestCase
{
	private string $fixturePath;

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->fixturePath = dirname(__DIR__) . '/phpunit/fixtures';
	}

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles'] = null;

		parent::tear_down();
	}

	public function testAddScript(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addScript('foo');
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['foo']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['foo']);
		$this->assertSame('foo', $wpScripts->registered['foo']->handle);
		$this->assertSame('https://example.com/assets/foo.js', $wpScripts->registered['foo']->src);
	}

	public function testAddScriptWithPrefix(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addScript('foo');
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['prefix-foo']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['prefix-foo']);
		$this->assertSame('prefix-foo', $wpScripts->registered['prefix-foo']->handle);
		$this->assertSame('https://example.com/assets/foo.js', $wpScripts->registered['prefix-foo']->src);
	}

	public function testAddScriptWithVersion(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addScript('foo', ['version' => '1.0.0-rc.1']);
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['prefix-foo']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['prefix-foo']);
		$this->assertSame('prefix-foo', $wpScripts->registered['prefix-foo']->handle);
		$this->assertSame('https://example.com/assets/foo.js', $wpScripts->registered['prefix-foo']->src);

		$this->assertSame('1.0.0-rc.1', $wpScripts->registered['prefix-foo']->ver);
	}

	public function testAddScriptWithDependencies(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addScript('foo', ['dependencies' => ['jquery']]);
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['prefix-foo']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['prefix-foo']);
		$this->assertSame('prefix-foo', $wpScripts->registered['prefix-foo']->handle);
		$this->assertSame('https://example.com/assets/foo.js', $wpScripts->registered['prefix-foo']->src);

		$this->assertSame(['jquery'], $wpScripts->registered['prefix-foo']->deps);
	}

	public function testAddScriptWithManifest(): void
	{
		$enqueue = new Enqueue($this->fixturePath . '/assets', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addScript('admin');
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['prefix-admin']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['prefix-admin']);
		$this->assertSame('prefix-admin', $wpScripts->registered['prefix-admin']->handle);
		$this->assertSame('https://example.com/assets/admin.js', $wpScripts->registered['prefix-admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpScripts->registered['prefix-admin']->ver);
		$this->assertSame(
			[
				'react',
				'react-dom',
				'wp-api-fetch',
				'wp-dom-ready',
				'wp-i18n',
			],
			$wpScripts->registered['prefix-admin']->deps,
		);
	}

	public function testAddScriptWithManifestAndOptions(): void
	{
		$enqueue = new Enqueue($this->fixturePath . '/assets', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addScript('admin', ['version' => '1.0.0-rc.2', 'dependencies' => ['jquery']]);
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['prefix-admin']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['prefix-admin']);
		$this->assertSame('prefix-admin', $wpScripts->registered['prefix-admin']->handle);
		$this->assertSame('https://example.com/assets/admin.js', $wpScripts->registered['prefix-admin']->src);
		$this->assertSame('1.0.0-rc.2', $wpScripts->registered['prefix-admin']->ver);
		$this->assertSame(
			[
				'react',
				'react-dom',
				'wp-api-fetch',
				'wp-dom-ready',
				'wp-i18n',
				'jquery',
			],
			$wpScripts->registered['prefix-admin']->deps,
		);
	}

	public function testAddScriptWithInlineScript(): void
	{
		$this->markTestIncomplete('Requires custom assertion.');

		$enqueue = new Enqueue($this->fixturePath . '/assets', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addScript('admin', ['version' => '1.0.0-rc.2', 'dependencies' => ['jquery']])
			->withInlineScripts(
				new class implements InlineScript {
					public function getInlineScriptPosition(): string
					{
						return 'before';
					}

					public function getInlineScriptContent(): string
					{
						return 'console.log("Hello, World!");';
					}
				},
			);
		$enqueue->scripts();

		$this->assertStringContainsString(
			<<<'HTML'
			<script type="text/javascript" id="prefix-admin-js-before">
			/* <![CDATA[ */
			console.log("Hello, World!");
			/* ]]> */
			</script>
			<script type="text/javascript" src="https://example.com/assets/admin.js?ver=1.0.0-rc.2" id="prefix-admin-js"></script>
			HTML,
			get_echo('wp_print_scripts'),
		);
	}

	public function testAddScriptWithTranslations(): void
	{
		$this->markTestIncomplete('Requires custom assertion.');

		$enqueue = new Enqueue($this->fixturePath . '/assets', 'https://example.com/assets/');
		$enqueue->setTranslations('text-domain', $this->fixturePath . '/languages');
		$enqueue->addScript('admin', ['localized' => true]);
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertSame('text-domain', $wpScripts->registered['admin']->textdomain);
		$this->assertStringEndsWith('/tests/phpunit/fixtures/languages/', $wpScripts->registered['admin']->translations_path);
		$this->assertStringContainsString(
			<<<'SCRIPT'
			<script type="text/javascript" id="admin-js-translations">
			/* <![CDATA[ */
			( function( domain, translations ) {
			SCRIPT,
			get_echo('wp_print_scripts'),
		);
	}

	public function testAddScriptWithNameContainingSuffix(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addScript('bar.js');
		$enqueue->scripts();

		/** @var WP_Scripts $wpScripts */
		$wpScripts = $GLOBALS['wp_scripts'];

		$this->assertTrue(isset($wpScripts->registered['bar']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpScripts->registered['bar']);
		$this->assertSame('bar', $wpScripts->registered['bar']->handle);
		$this->assertSame('https://example.com/assets/bar.js', $wpScripts->registered['bar']->src);
	}

	public function testAddStyle(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addStyle('foo');
		$enqueue->styles();

		/** @var WP_Styles $wpStyles */
		$wpStyles = $GLOBALS['wp_styles'];

		$this->assertTrue(isset($wpStyles->registered['foo']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpStyles->registered['foo']);
		$this->assertSame('foo', $wpStyles->registered['foo']->handle);
		$this->assertSame('https://example.com/assets/foo.css', $wpStyles->registered['foo']->src);
	}

	public function testAddStyleWithOptions(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->addStyle('foo', ['dependencies' => ['bootstrap'], 'version' => '2.0.0']);
		$enqueue->styles();

		/** @var WP_Styles $wpStyles */
		$wpStyles = $GLOBALS['wp_styles'];

		$this->assertTrue(isset($wpStyles->registered['foo']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpStyles->registered['foo']);
		$this->assertSame('foo', $wpStyles->registered['foo']->handle);
		$this->assertSame('https://example.com/assets/foo.css', $wpStyles->registered['foo']->src);
		$this->assertSame('2.0.0', $wpStyles->registered['foo']->ver);
		$this->assertSame(['bootstrap'], $wpStyles->registered['foo']->deps);
	}

	public function testAddStyleWithPrefix(): void
	{
		$enqueue = new Enqueue('/public/assets/', 'https://example.com/assets/');
		$enqueue->setPrefix('prefix');
		$enqueue->addStyle('foo');
		$enqueue->styles();

		/** @var WP_Styles $wpStyles */
		$wpStyles = $GLOBALS['wp_styles'];

		$this->assertArrayHasKey('prefix-foo', $wpStyles->registered);
		$this->assertInstanceOf(_WP_Dependency::class, $wpStyles->registered['prefix-foo']);
		$this->assertSame('prefix-foo', $wpStyles->registered['prefix-foo']->handle);
		$this->assertSame('https://example.com/assets/foo.css', $wpStyles->registered['prefix-foo']->src);
	}

	public function testAddStyleWithManifest(): void
	{
		$enqueue = new Enqueue($this->fixturePath . '/assets', 'https://example.com/assets/');
		$enqueue->addStyle('admin');
		$enqueue->styles();

		/** @var WP_Styles $wpStyles */
		$wpStyles = $GLOBALS['wp_styles'];

		$this->assertTrue(isset($wpStyles->registered['admin']));
		$this->assertInstanceOf(_WP_Dependency::class, $wpStyles->registered['admin']);
		$this->assertSame('admin', $wpStyles->registered['admin']->handle);
		$this->assertSame('https://example.com/assets/admin.css', $wpStyles->registered['admin']->src);
		$this->assertSame('7cb1493e4611c2ec1223', $wpStyles->registered['admin']->ver);
		$this->assertSame([], $wpStyles->registered['admin']->deps);
	}

	public function testAddStyleWithManifestAndOptions(): void
	{
		$enqueue = new Enqueue($this->fixturePath . '/assets', 'https://example.com/assets/');
		$enqueue->addStyle('admin', ['version' => '1.0.0-rc.2', 'dependencies' => ['bootstrap']]);
		$enqueue->styles();

		/** @var WP_Styles $wpStyles */
		$wpStyles = $GLOBALS['wp_styles'];

		$this->assertArrayHasKey('admin', $wpStyles->registered);
		$this->assertInstanceOf(_WP_Dependency::class, $wpStyles->registered['admin']);
		$this->assertSame('admin', $wpStyles->registered['admin']->handle);
		$this->assertSame('https://example.com/assets/admin.css', $wpStyles->registered['admin']->src);
		$this->assertSame('1.0.0-rc.2', $wpStyles->registered['admin']->ver);
		$this->assertSame(['bootstrap'], $wpStyles->registered['admin']->deps);
	}
}
