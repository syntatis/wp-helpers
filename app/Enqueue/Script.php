<?php

declare(strict_types=1);

namespace Syntatis\WPHelpers\Enqueue;

use Syntatis\WPHelpers\Contracts\InlineScript;

use function array_merge;

class Script
{
	/** @var array<InlineScript> */
	private array $inlineScripts = [];

	/** @var array{handle:string,url:string,localized:bool,dependencies:array<string>,version:string|null} */
	private array $manifest;

	/** @param array{handle:string,url:string,localized:bool,dependencies:array<string>,version:string|null} $manifest */
	public function __construct(array $manifest)
	{
		$this->manifest = $manifest;
	}

	public function withInlineScripts(InlineScript ...$inlineScripts): void
	{
		$this->inlineScripts = $inlineScripts;
	}

	/** @return array{handle:string,url:string,localized:bool,dependencies:array<string>,version:string|null,inline:array<array{data:string,position:string}>} */
	public function get(): array
	{
		$inline = [];

		foreach ($this->inlineScripts as $inlineScript) {
			$inline[] = [
				'position' => $inlineScript->getInlineScriptPosition(),
				'data' => $inlineScript->getInlineScriptContent(),
			];
		}

		return array_merge($this->manifest, ['inline' => $inline]);
	}
}
