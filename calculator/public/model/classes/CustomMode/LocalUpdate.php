<?php

namespace CustomMode;

trait LocalUpdate
{
  static function update() {
    $config = [
      'method' => 'get',
      'contentType' => 'application/json; charset=utf-8',
      'json' => false,
    ];

    // Инициализация
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'initCalc', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'calcInit.json', $result);
    else $result['error'] = 'Error load initCalc';

    // Загрузка Сообщений
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadMessages', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'messages.json', $result);
    else $result['error'] = 'Error load loadMessages';

    // Загрузка услуг
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadService', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'service.json', $result);
    else $result['error'] = 'Error load service';

    // Загрузка плинтусов
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadSkirting', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'skirting.json', $result);
    else $result['error'] = 'Error load skirting';

    // Загрузка декоров заглушек для плинтусов
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadSkirtingPlug', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'skirtingPlug.json', $result);
    else $result['error'] = 'Error load skirting plug';

    // Загрузка профилей кромок
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadEdgeProfile', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'edgeProfile.json', $result);
    else $result['error'] = 'Error load edge profiles';

    // Загрузка моек
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadSinks', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'sinkData.json', $result);
    else $result['error'] = 'Error load sink';

    // Загрузка смесителей
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadFaucet', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'faucetData.json', $result);
    else $result['error'] = 'Error load faucet';

    // Загрузка комплектующих
    $result = httpRequest(MAIN_SERVER_URL, $config, ['mode' => 'loadComponents', 'testDb' => DEV_DATA_BASE]);
    if (is_string($result)) file_put_contents(STORE_DATA . 'componentsData.json', $result);
    else $result['error'] = 'Error load components';

    return $result;
  }
}
