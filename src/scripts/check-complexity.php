<?php

declare(strict_types=1);

/**
 * Reports soft readability thresholds without failing the quality gate.
 */
$root = dirname(__DIR__);
$thresholds = ['function' => 50, 'class' => 400];
$warnings = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }
    $lineCount = count($lines);
    if ($lineCount > $thresholds['class']) {
        $warnings[] = sprintf('%s: %d Zeilen (Klassenrichtwert %d)', $file->getPathname(), $lineCount, $thresholds['class']);
    }
}

if ($warnings !== []) {
    fwrite(STDERR, "Komplexitätshinweise (nicht blockierend):\n".implode(PHP_EOL, $warnings).PHP_EOL);
}
printf("Komplexitätsprüfung abgeschlossen (%d Hinweis(e)).\n", count($warnings));
