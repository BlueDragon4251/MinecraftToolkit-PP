<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Console\Commands;

use Illuminate\Console\Command;

class CheckMinecraftToolkitTranslationsCommand extends Command
{
    protected $signature = 'minecraft-toolkit:translations';

    protected $description = 'Prüft Übersetzungsschlüssel und Zeichenkodierung von Minecraft Toolkit.';

    public function handle(): int
    {
        $base = plugin_path('minecrafttoolkit', 'lang');
        $english = $this->flatten(require $base.'/en/strings.php');
        $german = $this->flatten(require $base.'/de/strings.php');
        $checks = [
            'Fehlt auf Deutsch' => array_diff_key($english, $german),
            'Fehlt auf Englisch' => array_diff_key($german, $english),
            'Kodierungsfehler' => array_filter($english + $german, fn (mixed $value): bool => is_string($value) && preg_match('/Ã.|Â.|â€/', $value) === 1),
        ];
        foreach ($checks as $label => $items) {
            $this->line($label.': '.($items === [] ? 'keine' : implode(', ', array_keys($items))));
        }

        return collect($checks)->every(fn (array $items): bool => $items === []) ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, mixed> */
    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = ltrim($prefix.'.'.$key, '.');
            $flat += is_array($value) ? $this->flatten($value, $path) : [$path => $value];
        }

        return $flat;
    }
}
