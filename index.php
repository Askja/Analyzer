<?php

require 'vendor/autoload.php';

use engine\Application;
use engine\Render;
use models\AssetManager;

Application::init();
$assetManager = new AssetManager(Application::getAssetsCss(), Application::getAssetsJS());
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Анализатор"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= Application::getAppName() ?></title>

    <link rel="shortcut icon" href="web/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <?= $assetManager->getAssetsCss() ?>
</head>
<body>
<?= Render::out('layouts/reports/main.php', []) ?>

<?= $assetManager->getAssetsJS() ?>
</body>
</html>