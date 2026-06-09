<?php

declare(strict_types=1);

$boolean = static fn (mixed $value, bool $default): bool => filter_var(
    $value,
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
) ?? $default;

return [
    'enabled' => $boolean(env('MINECRAFT_TOOLKIT_ENABLED', true), true),
    'admins_only' => $boolean(env('MINECRAFT_TOOLKIT_ADMINS_ONLY', false), false),
    'backup_before_overwrite' => $boolean(env('MINECRAFT_TOOLKIT_BACKUP_BEFORE_OVERWRITE', true), true),
    'http_timeout' => max(5, (int) env('MINECRAFT_TOOLKIT_HTTP_TIMEOUT', 20)),
    'download_timeout' => max(30, (int) env('MINECRAFT_TOOLKIT_DOWNLOAD_TIMEOUT', 300)),
    'max_icon_bytes' => max(65536, (int) env('MINECRAFT_TOOLKIT_MAX_ICON_BYTES', 2097152)),
    'fallback_versions' => [
        '1.21.11',
        '1.21.10',
        '1.21.8',
        '1.21.7',
        '1.21.6',
        '1.21.5',
        '1.21.4',
        '1.20.6',
        '1.20.4',
    ],
];
