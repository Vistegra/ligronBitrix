<?php

namespace RemoteDb;

use RedBeanPHP\RedException;

class Dealers
{
  private App $app;

  public function __construct(App $app) {
    $this->app = $app;
  }

  /**
   * Поиск дилеров по Инн из таблицы дилеров в БД калькулятора
   * Если ИНН равен 'all' загрузить все
   * @param string $tin
   * @return void
   * @throws RedException
   */
  public function getByTin(string $tin): array
  {
    $result = [];
    // Загрузка всех дилеров
    if ($tin === $this->app::QUERY_ALL_DEALERS) {
      $remoteDealers = $this->app->db->getDealersByTinList([]);
    } else {
      $remoteDealers = $this->app->db->getDealersByTinRecursive($tin);
    }
    // Загрузка всех дилеров из локальной БД
    $dealers = $this->app->main->db->selectDb('default')->loadDealers(true);

    // Установка ид
    foreach ($remoteDealers as $remoteDealer) {
      $localDealer = array_find($dealers, function ($localDealer) use ($remoteDealer) {
        $settings = $localDealer['settings'];
        return isset($settings['tin']) && $settings['tin'] === $remoteDealer['dealerTin'];
      });

      $result[] = [
        'id'   => $localDealer ? $localDealer['id'] : 'remote' . $remoteDealer['id'],
        'tin'  => $remoteDealer['dealerTin'],
        'name' => $remoteDealer['dealerName'],
      ];
    }

    return $result;
  }

  /**
   * @param array $dealer
   * @return array
   * @throws RedException
   */
  public function create(array $dealer): array
  {
    $db = $this->app->main->db;
    $db->selectDb('default');

    $dealerName = trim($dealer['name']);
    /*if (strlen($dealerName) < 2) {
      $result['error'] = 'Name must be 2 or more chars!';
    }*/

    $urlPrefix = strtolower(preg_replace('/[^a-zA-Z]/i', '', translit($dealerName)));
    $dbPrefix = substr($urlPrefix, 0, 3) . '_';

    $haveDealers = $db->selectQuery('dealers', 'cms_param');
    if (count($haveDealers)) {
      do {
        $findDealer = array_filter($haveDealers, function ($param) use ($dbPrefix) {
          $param = json_decode($param, true);
          return ($param['prefix'] ?? false) === $dbPrefix;
        });

        if (count($findDealer)) {
          $dbPrefix = substr($dbPrefix, 0, 3) . substr(uniqid(), -3, 3) . '_';
        }
      } while (count($findDealer));

    }

    $id = $db->getLastID('dealers', ['name' => 'tmp']);
    $param = [
      'name'      => $dealerName,
      'cms_param' => json_encode(['prefix' => $dbPrefix]),
      'activity'  => 1,
      'settings'  => gzcompress(json_encode(['prop_tin' => $dealer['tin']]), 9),
    ];

    $columns = $db->getColumnsTable('dealers');
    $result = $db->insert($columns, 'dealers', [$id => $param], true);

    $this->app->main->dealer->create($id, [
      'dealerName' => $dealerName,
      'dbConfig'   => $this->app->main->getSettings(\VC::DB_CONFIG),
    ], [
      'prefix' => $dbPrefix,
      'login'  => 'dealer' . $id,
      'pass'   => password_hash(1111, PASSWORD_BCRYPT),
    ]);

    return [
      'id' => $id,
    ];
  }

  /**
   * Load all dealers by salon code using recursive query
   * @param string $salonCode
   * @return array
   */
  public function getBySalon(string $salonCode): array
  {
    return $this->app->db->getDealersBySalonRecursive($salonCode);
  }

  public function getByUserCode(string $userCode, bool $rename = false): array
  {
    $data = $this->app->db->getDealersByUserCode($userCode);

    if ($rename) {
      return array_map(function ($row) {
        return [
          'id'   => $row['id'],
          'tin'  => $row['dealerTin'],
          'name' => $row['dealerName']
        ];
      }, $data);
    }

    return $data;
  }

}
