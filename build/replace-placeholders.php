<?php

declare(strict_types=1);

$options = getopt('', [
    'version:',
    'date::',
    'root::',
]);

$version = trim((string) ($options['version'] ?? ''));

if ($version === '') {
    fwrite(STDERR, "Missing required --version option.\n");
    exit(1);
}

$date = trim((string) ($options['date'] ?? date('d.m.Y')));
$root = isset($options['root'])
    ? rtrim(str_replace('\\', '/', (string) $options['root']), '/')
    : \dirname(__DIR__);

$files = [
    'full_category_path.xml',
    'script.php',
    'services/provider.php',
    'src/Extension/FullCategoryPath.php',
    'src/Fields/PlugininfoField.php',
    'language/en-GB/plg_jshoppingrouter_full_category_path.ini',
    'language/en-GB/plg_jshoppingrouter_full_category_path.sys.ini',
    'language/ru-RU/plg_jshoppingrouter_full_category_path.ini',
    'language/ru-RU/plg_jshoppingrouter_full_category_path.sys.ini',
];

foreach ($files as $file) {
    $path = $root . '/' . $file;

    if (!is_file($path)) {
        fwrite(STDERR, 'Missing expected file: ' . $file . "\n");
        exit(1);
    }

    $contents = (string) file_get_contents($path);
    $contents = str_replace(
        ['__DEPLOY_VERSION__', '__DEPLOY_DATE__'],
        [$version, $date],
        $contents
    );
    $contents = preg_replace('/<version>[^<]*<\/version>/', '<version>' . $version . '</version>', $contents) ?? $contents;
    $contents = preg_replace('/<creationDate>[^<]*<\/creationDate>/', '<creationDate>' . $date . '</creationDate>', $contents) ?? $contents;
    $contents = preg_replace('/(^\s*\*\s+@version\s+).+$/m', '${1}' . $version, $contents) ?? $contents;

    file_put_contents($path, $contents);
}

echo 'Placeholders replaced for version ' . $version . "\n";
