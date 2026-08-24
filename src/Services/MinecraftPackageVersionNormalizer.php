<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Services;

final class MinecraftPackageVersionNormalizer
{
    public static function equivalent(string $first, string $second): bool
    {
        $first = trim($first);
        $second = trim($second);

        return $first === $second || self::base($first) === self::base($second);
    }

    public static function base(string $version): string
    {
        $version = strtolower(trim($version));
        $version = preg_replace('/^(?:bukkit|spigot|paper|purpur|folia|fabric|forge|neoforge)-/i', '', $version) ?? $version;
        $version = preg_replace('/^v/i', '', $version) ?? $version;
        $version = preg_replace('/\+.*$/', '', $version) ?? $version;
        $version = preg_replace('/-snapshot.*$/i', '', $version) ?? $version;
        $version = preg_replace('/-(?:bukkit|spigot|paper|purpur|folia|fabric|forge|neoforge)$/i', '', $version) ?? $version;

        return trim($version);
    }
}
