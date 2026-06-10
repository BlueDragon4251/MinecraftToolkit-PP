<?php

declare(strict_types=1);

$key = trim((string) ($argv[1] ?? ''));
if ($key === '') {
    fwrite(STDERR, "Usage: php tools/encode-curseforge-key.php YOUR_CURSEFORGE_API_KEY\n");
    exit(1);
}

$encoded = strrev(base64_encode(str_rot13($key)));
$parts = str_split($encoded, 18);

echo "Paste this into CurseForgeApiKeyProvider::EMBEDDED_OBFUSCATED_API_KEY_PARTS:\n\n";
echo "[\n";
foreach ($parts as $part) {
    echo "    '" . addslashes($part) . "',\n";
}
echo "]\n";
