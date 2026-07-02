<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$locales = [
    'en' => require $base . '/lang/en/strings.php',
    'de' => require $base . '/lang/de/strings.php',
];

/** @return list<string> */
function flattenKeys(array $values, string $prefix = ''): array
{
    $keys = [];
    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            array_push($keys, ...flattenKeys($value, $path));
            continue;
        }

        $keys[] = $path;
    }

    return $keys;
}

/** @return list<string> */
function mojibakeValues(array $values, string $prefix = ''): array
{
    $bad = [];
    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            array_push($bad, ...mojibakeValues($value, $path));
            continue;
        }

        if (is_string($value) && preg_match('/(Ã|Â|�)/u', $value) === 1) {
            $bad[] = $path;
        }
    }

    return $bad;
}

$reference = array_values(array_unique(flattenKeys($locales['en'])));
sort($reference);
$failed = false;

foreach ($locales as $locale => $strings) {
    $keys = array_values(array_unique(flattenKeys($strings)));
    sort($keys);

    $missing = array_values(array_diff($reference, $keys));
    $extra = array_values(array_diff($keys, $reference));
    $mojibake = mojibakeValues($strings);

    if ($missing !== [] || $extra !== [] || $mojibake !== []) {
        $failed = true;
        echo "Locale {$locale} failed translation QA.\n";
        if ($missing !== []) {
            echo 'Missing keys: ' . implode(', ', $missing) . "\n";
        }
        if ($extra !== []) {
            echo 'Extra keys: ' . implode(', ', $extra) . "\n";
        }
        if ($mojibake !== []) {
            echo 'Possible mojibake: ' . implode(', ', $mojibake) . "\n";
        }
    }
}

if ($failed) {
    exit(1);
}

echo "Language keys are aligned.\n";
