<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

/** @global CMain $APPLICATION */
/** @global array $paramsPage */

$asset = Asset::getInstance();

$asset->addCss(SITE_TEMPLATE_PATH . '/css/index.css');
$asset->addJs(SITE_TEMPLATE_PATH . '/js/main.js');
CJSCore::Init(['fx']);

define('CUR_PAGE', $APPLICATION->GetCurPage(true));
define('CUR_CANONICAL_URL', 'https://ligron.ru/' . ltrim(str_replace('index.php', '', CUR_PAGE), '/'));

$isIndexPage = false;
$isDevPage = false;

$matches = [];
if (preg_match("#^/e-order/.*/([^/]+)/?$#", CUR_PAGE, $matches)) {
    $isIndexPage = true;
}
if (preg_match("#^/e-order/dev.*/([^/]+)/?$#", CUR_PAGE, $matches)) {
    $isDevPage = true;
}

$title = $APPLICATION->GetTitle(false, false);
?>

<!DOCTYPE html>
<html xml:lang="<?= LANGUAGE_ID ?>" lang="<?= LANGUAGE_ID ?>">
    <head>
        <title><?= $APPLICATION->ShowTitle(); ?></title>


        <?php
        $asset->addString('<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">');
        $asset->addString('<meta property="og:title" content="' . $APPLICATION->GetTitle(true, true) . '" />');
        $asset->addString('<meta property="og:url" content="' . CUR_CANONICAL_URL . '" />');
        $asset->addString('<meta property="og:description" content="' . $APPLICATION->GetProperty("description") . '" />');
        $asset->addString('<meta property="og:type" content="website" />');
        $asset->addString('<meta property="og:image" content="' . SITE_TEMPLATE_PATH . '/img/preview.webp" />');

        $asset->addString('<meta name="author" content="ligron.ru" />');
        $asset->addString('<meta name="generator" content="ligron.ru" />');

        $asset->addString('<link rel="canonical" href="' . CUR_CANONICAL_URL . '" />');
        $asset->addString('<link rel="icon" type="image/x-icon" href="/favicon.ico" />');

        ?>

        <?php


        $APPLICATION->ShowHead();
        $pwaPath = '/local/components/vistegra/e.order.page/templates/.default';
        ?>

        <!-- Подключение манифеста -->
        <link rel="manifest" href="<?= $pwaPath ?>/manifest.webmanifest">

        <!-- Иконка для Apple устройств (для установки на iOS) -->
        <link rel="apple-touch-icon" href="<?= $pwaPath ?>/pwa-icons/pwa-192x192.png">

        <!-- Иконки для вкладки браузера и PWA -->
        <link rel="icon" type="image/x-icon" href="<?= $pwaPath ?>/pwa-icons/favicon.ico">
        <link rel="icon" type="image/png" sizes="192x192" href="<?= $pwaPath ?>/pwa-icons/pwa-192x192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="<?= $pwaPath ?>/pwa-icons/pwa-512x512.png">

        <!-- Цвет темы (окрашивает статус-бар на мобильных устройствах) -->
        <meta name="theme-color" content="#ffffff">

        <!-- Yandex.Metrika counter -->
        <!-- /Yandex.Metrika counter -->

    </head>

<body>
    <div id="panel"><? /*$APPLICATION->ShowPanel();*/ ?></div>

    <!-- region base-container-->
    <div class="base-container">
        <!--    <header class="header content-container">
                <a href="/" class="logo">
                    <img src="https://via.placeholder.com/180x50.png?text=LIGRON" alt="Ligron Logo">
                </a>
            </header>-->
        <!-- region workarea-->
        <div class="workarea">

<?php echo $title; ?>