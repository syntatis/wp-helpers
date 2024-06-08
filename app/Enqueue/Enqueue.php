<?php

declare(strict_types=1);

namespace Syntatis\WPHelpers\Enqueue;

use function array_merge;
use function basename;
use function is_file;
use function Syntatis\Utils\is_blank;

/**
 * A helper class providing an OOP interface to enqueue scripts and styles in WordPress.
 */
class Enqueue
{
	/** @var array<Script> */
	private array $scripts = [];

	/** @var array<string,array{url:string,handle:string,dependencies:string[],version:string|null,media:string}> */
	private array $styles = [];

	private string $dirPath;

	private string $dirUrl;

	private string $domainName;

	private string $languagePath;

	private ?string $prefix = null;

	/**
	 * @param string $dirPath The path to the directory containing the scripts and styles files.
	 * @param string $dirUrl  The public URL to the directory containing the scripts and styles files.
	 *                        This URL will be used to enqueue the scripts and styles. Typically, it
	 *                        may be retrieved with the `plugin_dir_url` function or the
	 *                        `get_template_directory_uri` function.
	 */
	public function __construct(string $dirPath, string $dirUrl)
	{
		$this->dirPath = trailingslashit($dirPath);
		$this->dirUrl = trailingslashit($dirUrl);
	}

	/**
	 * Add the prefix to uniquely identify the scripts and styles.
	 *
	 * By default, the class uses the basename of the script or style file as the handle.
	 * This can cause problems when multiple scripts or styles have the same basename,
	 * especially those from other plugins, themes, or WordPress core. You can use
	 * this method to add a prefix to the handle and make it unique.
	 *
	 * @param string $prefix The prefix to add to the handle.
	 *                       It is recommended to use kebab case for the prefix e.g. 'my-plugin'.
	 */
	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	/**
	 * Set the domain name and language path for script translations.
	 *
	 * This will be used to localize the scripts that have been added through the `addScript` method,
	 * with the `localized` option set to `true`.
	 *
	 * @param string $domainName   The text domain to use for the translations.
	 * @param string $languagePath The path to the language files.
	 */
	public function setTranslations(string $domainName, string $languagePath): void
	{
		$this->domainName = $domainName;
		$this->languagePath = trailingslashit($languagePath);
	}

	/**
	 * Add a script file to enqueue.
	 *
	 * @param string                                                               $fileName The name of the script file, without the .js extension.
	 * @param array{localized:bool,dependencies:array<string>,version:string|null} $options
	 */
	public function addScript(string $fileName, array $options = []): Script
	{
		$basename = basename($fileName, '.js');
		$manifest = $this->getManifest($basename);

		$script = new Script(array_merge(
			$manifest,
			[
				'dependencies' => array_merge($manifest['dependencies'] ?? [], $options['dependencies'] ?? []),
				'localized' => $options['localized'] ?? false,
				'url' => $manifest['url'] . '.js',
				'version' => $options['version'] ?? $manifest['version'] ?? null,
			],
		));

		$this->scripts[$basename] = $script;

		return $script;
	}

	/**
	 * Add a stylesheet to enqueue.
	 *
	 * @param string                                                                            $fileName The name of the stylesheet file, without the .css extension.
	 * @param array{localized:bool,dependencies:array<string>,version:string|null,media:string} $options
	 */
	public function addStyle(string $fileName, array $options = []): void
	{
		$basename = basename($fileName, '.css');
		$manifest = $this->getManifest($basename);

		$this->styles[$basename] = array_merge(
			$manifest,
			[
				'handle' => $manifest['handle'],
				'url' => $manifest['url'] . ( is_rtl() ? '-rtl' : '' ) . '.css',
				'dependencies' => $options['dependencies'] ?? [],
				'version' => $options['version'] ?? $manifest['version'] ?? null,
				'media' => $options['media'] ?? 'all',
			],
		);
	}

	/**
	 * Enqueue all the scripts that have been added through the `addScript` method.
	 */
	public function scripts(): void
	{
		foreach ($this->scripts as $script) {
			$script = $script->get();

			wp_enqueue_script(
				$script['handle'],
				$script['url'],
				$script['dependencies'],
				$script['version'],
				true, // in footer.
			);

			foreach ($script['inline'] as $inline) {
				wp_add_inline_script(
					$script['handle'],
					$inline['data'],
					$inline['position'],
				);
			}

			if (! $script['localized'] || is_blank($this->domainName) || is_blank($this->languagePath)) {
				continue;
			}

			wp_set_script_translations(
				$script['handle'],
				$this->domainName,
				$this->languagePath,
			);
		}
	}

	/**
	 * Enqueue all the styles that have been added through the `addStyle` method.
	 */
	public function styles(): void
	{
		foreach ($this->styles as $style) {
			wp_enqueue_style(
				$style['handle'],
				$style['url'],
				$style['dependencies'],
				$style['version'],
				$style['media'],
			);
		}
	}

	/** @return array{dependencies:array<string>,version:string|null,handle:string,url:string} */
	private function getManifest(string $fileName): array
	{
		$asset = [];
		$assetFile = $this->dirPath . $fileName . '.asset.php';

		if (is_file($assetFile)) {
			$asset = include $assetFile;
		}

		$dependencies = $asset['dependencies'] ?? [];
		$version = $asset['version'] ?? null;

		return [
			'dependencies' => $dependencies,
			'handle' => ! is_blank($this->prefix) ? $this->prefix . '-' . $fileName : $fileName,
			'url' => $this->dirUrl . $fileName,
			'version' => $version,
		];
	}
}
