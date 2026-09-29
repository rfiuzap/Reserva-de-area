<?php
declare(strict_types=1);

$appVersion = 'Versão: 1.06 (2026) by RF';

$assetVersion = static function (string $path): int {
	return (int) (@filemtime(__DIR__ . '/../' . $path) ?: 0);
};
