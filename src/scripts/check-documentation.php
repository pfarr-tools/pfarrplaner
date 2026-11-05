<?php

declare(strict_types=1);

/**
 * Checks PHP declarations for descriptive documentation and supports a legacy baseline.
 */
$root = dirname(__DIR__);
$baselinePath = $root.'/quality/phpdoc-baseline.txt';
$changedOnly = in_array('--changed-only', $argv, true);
$generateBaseline = in_array('--generate-baseline', $argv, true);

/** @var list<string> $paths */
$paths = [];
foreach (['app', 'routes', 'database'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $paths[] = $file->getPathname();
        }
    }
}

sort($paths);
$baseline = is_file($baselinePath) ? file($baselinePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$baseline = array_fill_keys($baseline, true);
$violations = [];
$modifiers = [
    T_ABSTRACT,
    T_FINAL,
    T_PRIVATE,
    T_PROTECTED,
    T_PUBLIC,
    T_READONLY,
    T_STATIC,
    T_VAR,
];

foreach ($paths as $path) {
    $contents = file_get_contents($path);
    if ($contents === false) {
        continue;
    }

    $tokens = token_get_all($contents);
    $lastDocblock = null;
    $line = 1;
    foreach ($tokens as $index => $token) {
        if (is_array($token)) {
            [$id, $text, $tokenLine] = $token;
            $line = $tokenLine;
            if ($id === T_DOC_COMMENT) {
                $lastDocblock = $tokenLine;

                continue;
            }
            if ($id === T_WHITESPACE || $id === T_ATTRIBUTE || $id === T_COMMENT || in_array($id, $modifiers, true)) {
                continue;
            }
            $declaration = in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION], true);
            if ($declaration) {
                $next = null;
                for ($nextIndex = $index + 1; $nextIndex < count($tokens); $nextIndex++) {
                    $candidate = $tokens[$nextIndex];
                    if (is_array($candidate) && in_array($candidate[0], [T_WHITESPACE, T_COMMENT], true)) {
                        continue;
                    }
                    $next = $candidate;
                    break;
                }
                $previous = null;
                for ($previousIndex = $index - 1; $previousIndex >= 0; $previousIndex--) {
                    $candidate = $tokens[$previousIndex];
                    if (is_array($candidate) && in_array($candidate[0], [T_WHITESPACE, T_COMMENT], true)) {
                        continue;
                    }
                    $previous = $candidate;
                    break;
                }
                $isAnonymous = $id === T_CLASS && is_array($previous) && in_array($previous[0], [T_NEW, T_DOUBLE_COLON], true);
                $isClosure = $id === T_FUNCTION && $next === '(';
                if (! $isAnonymous && ! $isClosure && $lastDocblock === null) {
                    $relative = ltrim(substr($path, strlen($root)), '/');
                    $violations[] = $relative.':'.$tokenLine.':'.token_name($id);
                }
            }
            $lastDocblock = null;
        } elseif (trim($token) !== '') {
            $lastDocblock = null;
        }
    }
}

if ($generateBaseline) {
    sort($violations);
    if (! is_dir(dirname($baselinePath))) {
        mkdir(dirname($baselinePath), 0775, true);
    }
    file_put_contents($baselinePath, $violations === [] ? '' : implode(PHP_EOL, $violations).PHP_EOL);
    printf("PHPDoc-Baseline erzeugt: %d Einträge\n", count($violations));
    exit(0);
}

$reported = [];
foreach ($violations as $violation) {
    if (! $changedOnly && isset($baseline[$violation])) {
        continue;
    }
    $reported[] = $violation;
}

if ($reported !== []) {
    fwrite(STDERR, "Fehlende PHPDoc-Blöcke:\n".implode(PHP_EOL, $reported).PHP_EOL);
    exit(1);
}

printf("PHPDoc-Prüfung bestanden (%d deklarierte Altlasten baselined).\n", count($violations));
