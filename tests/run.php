<?php

declare(strict_types=1);

if (!function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        return preg_split('/' . str_replace('/', '\/', $pattern) . '/u', $string, $limit);
    }
}

$root = dirname(__DIR__, 2);
$loader = require $root . '/pelican/vendor/autoload.php';
$loader->addPsr4('BlueWolf\\MinecraftToolkit\\', dirname(__DIR__) . '/src/');

use BlueWolf\MinecraftToolkit\Services\MinecraftPropertiesService;
use BlueWolf\MinecraftToolkit\Services\MinecraftCrossplayService;
use BlueWolf\MinecraftToolkit\Services\MinecraftPackageInstaller;
use BlueWolf\MinecraftToolkit\Services\MinecraftSoftwareService;
use BlueWolf\MinecraftToolkit\Services\ModrinthService;
use BlueWolf\MinecraftToolkit\Services\MinecraftUpdateService;
use BlueWolf\MinecraftToolkit\Services\MinecraftCompatibilityService;
use BlueWolf\MinecraftToolkit\Services\GeyserDownloadService;
use BlueWolf\MinecraftToolkit\Services\CurseForgeService;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitPackage;
use BlueWolf\MinecraftToolkit\Models\MinecraftToolkitSetup;

$tests = [];

$tests['java properties'] = function (): void {
    $service = new MinecraftPropertiesService();
    $properties = $service->generateJava([
        'motd' => 'Grüße: Test=Ja',
        'level_name' => 'world',
        'max_players' => 20,
        'online_mode' => true,
    ], 25565);

    assertContains('server-port=25565', $properties);
    assertContains('server-ip=', $properties);
    assertContains('motd=Grüße\\: Test\\=Ja', $properties);
    assertContains('online-mode=true', $properties);
};

$tests['targeted properties patch'] = function (): void {
    $service = new MinecraftPropertiesService();
    $original = "motd=Alt\nmax-players=20\ncustom-setting=keep\n";
    $patched = $service->patch($original, [
        'motd' => 'Neu: schön',
        'max-players' => 42,
    ]);

    assertContains('motd=Neu\\: schön', $patched);
    assertContains('max-players=42', $patched);
    assertContains('custom-setting=keep', $patched);
};

$tests['latest stable Paper build'] = function (): void {
    $service = new MinecraftSoftwareService();
    $build = $service->selectPaperBuild([
        ['id' => 132, 'channel' => 'STABLE'],
        ['id' => 131, 'channel' => 'STABLE'],
        ['id' => 62, 'channel' => 'BETA'],
    ]);

    if (($build['id'] ?? null) !== 132) {
        throw new RuntimeException('The newest stable Paper build was not selected.');
    }
};

$tests['Forge Minecraft version mapping'] = function (): void {
    $service = new MinecraftSoftwareService();
    $versions = $service->forgeMinecraftVersions([
        '1.21.11-61.1.8',
        '1.21.11-61.1.7',
        '1.20.1-47.4.10',
        'invalid',
    ]);

    assertSame(['1.21.11', '1.20.1'], $versions);
};

$tests['NeoForge Minecraft version mapping'] = function (): void {
    $service = new MinecraftSoftwareService();
    $versions = $service->neoForgeMinecraftVersions([
        '21.0.167',
        '21.1.219',
        '20.4.251',
        '26.1.2.75',
    ]);

    assertSame(['1.21', '1.21.1', '1.20.4', '26.1.2'], $versions);
    assertSame('21.1.', $service->neoForgePrefix('1.21.1'));
    assertSame('26.1.2.', $service->neoForgePrefix('26.1.2'));
};

$tests['Modrinth plugin facets'] = function (): void {
    $setup = new MinecraftToolkitSetup();
    $setup->forceFill([
        'software' => 'paper',
        'minecraft_version' => '1.21.4',
    ]);
    $facets = (new ModrinthService())->searchFacets($setup);

    assertSame(['categories:paper', 'categories:spigot', 'categories:bukkit'], $facets[0]);
    assertSame(['versions:1.21.4'], $facets[1]);
    assertSame(['project_type:plugin'], $facets[2]);
};

$tests['Modrinth mod facets'] = function (): void {
    foreach (['fabric', 'forge', 'neoforge'] as $loader) {
        $setup = new MinecraftToolkitSetup();
        $setup->forceFill([
            'software' => $loader,
            'minecraft_version' => '1.21.1',
        ]);
        $facets = (new ModrinthService())->searchFacets($setup);

        assertSame(["categories:$loader"], $facets[0]);
        assertSame(['versions:1.21.1'], $facets[1]);
        assertSame(['project_type:mod'], $facets[2]);
    }
};

$tests['Modrinth search normalization'] = function (): void {
    $results = (new ModrinthService())->normalizeSearchResults([[
        'project_id' => 'Vebnzrzj',
        'slug' => 'luckperms',
        'title' => 'LuckPerms',
        'description' => 'Permissions',
        'downloads' => 123,
        'server_side' => 'required',
        'categories' => ['paper', 'management'],
        'versions' => ['1.21.4'],
    ]]);

    assertSame('Vebnzrzj', $results[0]['project_id']);
    assertSame(123, $results[0]['downloads']);
    assertSame(['paper', 'management'], $results[0]['categories']);
};

$tests['package filename validation'] = function (): void {
    $reflection = new ReflectionClass(MinecraftPackageInstaller::class);
    $installer = $reflection->newInstanceWithoutConstructor();

    assertSame('LuckPerms-Bukkit-5.5.53.jar', $installer->safeFileName('LuckPerms-Bukkit-5.5.53.jar'));

    try {
        $installer->safeFileName('../malicious.jar');
    } catch (Throwable) {
        return;
    }

    throw new RuntimeException('Unsafe package filename was accepted.');
};

$tests['targeted Geyser YAML patch'] = function (): void {
    $reflection = new ReflectionClass(MinecraftCrossplayService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $original = <<<'YAML'
bedrock:
  address: 127.0.0.1
  port: 19132
  clone-remote-port: true
  motd1: Geyser
remote:
  address: auto
  auth-type: online
custom-setting: keep
YAML;

    $patched = $service->patchConfig($original, 19133);

    assertContains("  address: 0.0.0.0", $patched);
    assertContains("  port: 19133", $patched);
    assertContains("  clone-remote-port: false", $patched);
    assertContains("  auth-type: floodgate", $patched);
    assertContains("  motd1: Geyser", $patched);
    assertContains("custom-setting: keep", $patched);
};

$tests['Modrinth update candidate normalization'] = function (): void {
    $installerReflection = new ReflectionClass(MinecraftPackageInstaller::class);
    $installer = $installerReflection->newInstanceWithoutConstructor();
    $serviceReflection = new ReflectionClass(MinecraftUpdateService::class);
    $service = $serviceReflection->newInstanceWithoutConstructor();
    $installerProperty = $serviceReflection->getProperty('installer');
    $installerProperty->setValue($service, $installer);

    $candidate = $service->normalizeModrinthCandidate([
        'version' => [
            'id' => 'version-2',
            'version_number' => '2.0.0',
            'selected_file' => [
                'filename' => 'Example-2.0.0.jar',
                'url' => 'https://cdn.modrinth.com/data/example/versions/version-2/Example-2.0.0.jar',
                'hashes' => ['sha512' => str_repeat('a', 128)],
            ],
        ],
        'dependencies' => [['project_id' => 'dependency']],
    ]);

    assertSame('version-2', $candidate['version_id']);
    assertSame('Example-2.0.0.jar', $candidate['file_name']);
    assertSame(str_repeat('a', 128), $candidate['hashes']['sha512']);
};

$tests['Geyser update candidate normalization'] = function (): void {
    $installerReflection = new ReflectionClass(MinecraftPackageInstaller::class);
    $installer = $installerReflection->newInstanceWithoutConstructor();
    $serviceReflection = new ReflectionClass(MinecraftUpdateService::class);
    $service = $serviceReflection->newInstanceWithoutConstructor();
    $installerProperty = $serviceReflection->getProperty('installer');
    $installerProperty->setValue($service, $installer);

    $candidate = $service->normalizeGeyserCandidate([
        'version' => '2.10.0',
        'build' => '1162',
        'file_name' => 'Geyser-Spigot.jar',
        'url' => 'https://download.geysermc.org/example',
        'sha256' => str_repeat('b', 64),
    ]);

    assertSame('1162', $candidate['version_id']);
    assertSame('2.10.0+1162', $candidate['version_number']);
    assertSame(str_repeat('b', 64), $candidate['hashes']['sha256']);
};

$tests['package compatibility classification'] = function (): void {
    $modrinth = new class extends ModrinthService
    {
        public function updateCandidate(string $projectId, MinecraftToolkitSetup $setup): array
        {
            if ($projectId === 'missing') {
                throw new BlueWolf\MinecraftToolkit\Exceptions\MinecraftToolkitException(
                    'Keine kompatible Paketversion wurde gefunden.'
                );
            }

            return [
                'project' => [],
                'version' => [
                    'id' => $projectId === 'same' ? 'installed-version' : 'new-version',
                    'version_number' => '2.0.0',
                    'selected_file' => [],
                ],
                'dependencies' => [],
            ];
        }
    };
    $geyser = new class extends GeyserDownloadService
    {
        public function __construct() {}

        public function latestSpigot(string $project): array
        {
            return [
                'project' => $project,
                'version' => '2.10.0',
                'build' => '1162',
                'file_name' => 'Geyser-Spigot.jar',
                'url' => 'https://download.geysermc.org/example',
                'sha256' => str_repeat('b', 64),
            ];
        }
    };
    $curseForge = new class extends CurseForgeService
    {
        public function updateCandidate(string $projectId, MinecraftToolkitSetup $setup): array
        {
            return [
                'project' => [],
                'version' => [
                    'id' => $projectId === '238222' ? 'new-file' : 'same-file',
                    'version_number' => 'Curse 2.0',
                    'selected_file' => [],
                ],
                'dependencies' => [],
            ];
        }
    };
    $service = new MinecraftCompatibilityService(
        $modrinth,
        $curseForge,
        $geyser,
        new MinecraftSoftwareService()
    );
    $target = new MinecraftToolkitSetup();
    $target->forceFill(['software' => 'paper', 'minecraft_version' => '1.21.4']);

    $compatible = new MinecraftToolkitPackage();
    $compatible->forceFill([
        'id' => 1,
        'project_name' => 'Same',
        'source' => 'modrinth',
        'source_project_id' => 'same',
        'source_version_id' => 'installed-version',
        'package_type' => 'plugin',
    ]);
    assertSame('compatible', $service->checkPackage($compatible, $target)['status']);

    $update = new MinecraftToolkitPackage();
    $update->forceFill([
        'id' => 2,
        'project_name' => 'Update',
        'source' => 'modrinth',
        'source_project_id' => 'update',
        'source_version_id' => 'installed-version',
        'package_type' => 'plugin',
    ]);
    assertSame('update_required', $service->checkPackage($update, $target)['status']);

    $missing = new MinecraftToolkitPackage();
    $missing->forceFill([
        'id' => 3,
        'project_name' => 'Missing',
        'source' => 'modrinth',
        'source_project_id' => 'missing',
        'source_version_id' => 'installed-version',
        'package_type' => 'plugin',
    ]);
    assertSame('incompatible', $service->checkPackage($missing, $target)['status']);

    $system = new MinecraftToolkitPackage();
    $system->forceFill([
        'id' => 4,
        'project_name' => 'Geyser',
        'source' => 'geysermc',
        'source_project_id' => 'geyser',
        'source_version_id' => '1161',
        'package_type' => 'crossplay',
        'is_system_package' => true,
    ]);
    assertSame('system_update', $service->checkPackage($system, $target)['status']);

    $manual = new MinecraftToolkitPackage();
    $manual->forceFill([
        'id' => 5,
        'project_name' => 'Manual',
        'source' => 'manual',
        'source_project_id' => 'manual',
        'package_type' => 'plugin',
    ]);
    assertSame('unknown', $service->checkPackage($manual, $target)['status']);

    $curse = new MinecraftToolkitPackage();
    $curse->forceFill([
        'id' => 6,
        'project_name' => 'Curse Project',
        'source' => 'curseforge',
        'source_project_id' => '238222',
        'source_version_id' => 'old-file',
        'package_type' => 'mod',
    ]);
    assertSame('update_required', $service->checkPackage($curse, $target)['status']);
};

$tests['CurseForge loader and search mapping'] = function (): void {
    $service = new CurseForgeService();
    assertSame(1, $service->loaderType('forge'));
    assertSame(4, $service->loaderType('fabric'));
    assertSame(6, $service->loaderType('neoforge'));
    assertSame(null, $service->loaderType('paper'));

    $paper = new MinecraftToolkitSetup();
    $paper->forceFill(['software' => 'paper', 'minecraft_version' => '1.21.4']);
    assertSame([
        'gameId' => 432,
        'classId' => 5,
        'gameVersion' => '1.21.4',
    ], $service->searchParameters($paper));

    $fabric = new MinecraftToolkitSetup();
    $fabric->forceFill(['software' => 'fabric', 'minecraft_version' => '1.21.4']);
    assertSame([
        'gameId' => 432,
        'classId' => 6,
        'gameVersion' => '1.21.4',
        'modLoaderType' => 4,
    ], $service->searchParameters($fabric));
};

$tests['CurseForge normalization'] = function (): void {
    $service = new CurseForgeService();
    $results = $service->normalizeSearchResults([[
        'id' => 238222,
        'slug' => 'jei',
        'name' => 'Just Enough Items',
        'summary' => 'Item viewer',
        'downloadCount' => 1234,
        'logo' => ['thumbnailUrl' => 'https://media.forgecdn.net/icon.png'],
        'authors' => [['name' => 'mezz']],
        'categories' => [['name' => 'Map and Information']],
        'latestFilesIndexes' => [['gameVersion' => '1.21.1']],
    ]]);

    assertSame('238222', $results[0]['project_id']);
    assertSame('Just Enough Items', $results[0]['title']);
    assertSame(['1.21.1'], $results[0]['versions']);
    assertSame([
        'sha1' => str_repeat('a', 40),
        'md5' => str_repeat('b', 32),
    ], $service->normalizeHashes([
        ['algo' => 1, 'value' => str_repeat('A', 40)],
        ['algo' => 2, 'value' => str_repeat('B', 32)],
    ]));
};

$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] $name\n";
    } catch (Throwable $exception) {
        $failed++;
        echo "[FAIL] $name: {$exception->getMessage()}\n";
    }
}

echo sprintf("\n%d passed, %d failed.\n", count($tests) - $failed, $failed);
exit($failed === 0 ? 0 : 1);

function assertContains(string $needle, string $haystack): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException("Expected output to contain: $needle");
    }
}

function assertSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException('Values are not identical.');
    }
}
