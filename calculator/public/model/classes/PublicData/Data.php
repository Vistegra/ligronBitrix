<?php

namespace PublicData;

use Exception;
use Main;
use RedBeanPHP\RedException;
use RemoteDb\App;

class Data
{
  /**
   * @var array
   */
  public $data;

  public string $dbContent = '';

  public Main $main;

  public function __construct(Main $main) {
    $this->main = $main;
  }

  public function loadCurrentUser(App $app)
  {
    $user = $app->loadCurrentUser();

    $this->data['loginType'] = $user['type'] ?? ($this->main->isDealer() ? 'manager' : 'dealer');
    $this->data['userCode']  = $user['userCode'] ?? '';

    $this->dbContent .= $this->main->getFrontContent('dataCurrentUser', $user);
  }

  /**
   * @throws RedException
   */
  public function loadDealersSetting(App $app)
  {
    // Загружаем параметры текущего дилера, если есть
    if ($this->main->isDealer()) {
      $dealer = $this->main->getLogin('dealer');
      $dealer['tin'] = $dealer['settings']['tin'];
      // Особое свойство: Регион
      try {
        $data = $this->main->db->selectDb('default')->loadDealerById();
        $this->main->db->selectDb('devDB');
        $dealer['region'] = $data['settings']['prop_region'] ?? '';
      } catch (Exception $e) {}

      $this->dbContent .= $this->main->getFrontContent('dataCurrentDealer', $dealer);

      $data = $app->loadSalons(['dealerTin' => $dealer['tin']]);
      $this->dbContent .= $this->main->getFrontContent('dataSalon', $data);
    }

    if ($this->main->isDealer() && $this->data['loginType'] === 'manager') {
      // Для менеджера лигрон загружаем все его дилеры
      $data = $app->dealer->getByUserCode($this->data['userCode'], true);
    } else {
      // Загружаем всех дилеров от текущего или всех
      $dealerTin = $dealer['tin'] ?? $app::QUERY_ALL_DEALERS;
      $data = $app->loadDealers(['dealerTin' => $dealerTin]);
    }

    $this->dbContent .= $this->main->getFrontContent('dataDealers', $data);
  }

  // Если загрузка через токен
  public function loadOrderByToken()
  {
    if (!$this->main->checkStatus()) return;

    $orderNumber = $this->main->url->request->get('ligron_number');
    $searchValue = 'orderNumber":"' . $orderNumber . '"';

    $order = $this->main->db->searchOrders(['countPerPage' => 1], $searchValue, [], true);
    $order = count($order) === 1 ? $order[0] : false;

    if ($order) {
      $this->dbContent .= $this->main->getFrontContent('dataOrder', $order);

      $customer = $this->main->db->loadCustomerByOrderId($order['ID']);
      if ($customer) $this->dbContent .= $this->main->getFrontContent('dataCustomer', $customer);
    }
  }

  // Редактор контента
  public function getContentData()
  {
    $data = $this->main->db->getContentData(true, true);
    $this->dbContent .= $this->main->getFrontContent('dataContent', $data);
  }
}
