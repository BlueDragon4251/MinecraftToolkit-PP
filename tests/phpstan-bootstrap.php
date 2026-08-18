<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$panelPath = getenv('PELICAN_PANEL_PATH') ?: $root.'/pelican';
$loader = require $panelPath.'/vendor/autoload.php';
$loader->addPsr4('BlueWolf\\MinecraftToolkit\\', dirname(__DIR__).'/src/');
