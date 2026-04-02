<?php

namespace CustomMode;

use Main;
use DbMain;
use Docs;
use Exception;
use RemoteDb\App;


class Mode
{
  use LocalUpdate;
  use LocalLoad;
  /**
   * @throws Exception
   */
  public static function __callStatic(string $method, $args) {
    throw new Exception("CustomMode method $method does not exist");
  }

  /**
   * @throws Exception
   */
  static function remoteDB(Main $main): array
  {
    $parameters = [];

    try {
      $app = App::getInstance($main, DB_REMOTE_CONFIG);
    } catch (Exception $e) {
      die(gTxt($e->getMessage()));
    }

    if ($main->url->server->get('REMOTE_ADDR') === $main->url->server->get('SERVER_ADDR')) {
      return $app->exec($parameters);
    } else {
      try {
        return $app->exec($parameters);
      } catch (Exception $e) {
        die(gTxt($e->getMessage()));
      }
    }
  }

  /**
   * Включение выключение калькулятора для дилеров
   */
  static function calcSwitch(Main $main)
  {
    file_put_contents(ABS_SITE_PATH . SHARE_PATH . 'calcStatus', $main->url->request->get('status'));
    $main->reDirect();
  }

  /**
   * Сохранение параметров для "чертилки"
   * @param Main $main
   * @return array|string[]
   */
  static function saveInParam(Main $main): array
  {
    $param = $main->url->request->get('param');

    if ($param === null) return ['error' => 'Ошибка сохранения'];

    file_put_contents(ABS_SITE_PATH . SHARE_PATH . 'dev/inputParams.json', $main->url->request->get('param'));
    return [];
  }

  /**
   * Загрузка данных с сервера Лигрон
   * @param Main $main
   * @return array|mixed|string
   */
  static function loadQuery(Main $main)
  {
    $queryMode = $main->url->request->get('queryMode');

    $result = httpRequest(MAIN_SERVER_URL, [
      'method'      => 'get',
      'contentType' => 'application/json; charset=utf-8',
      'json'        => false,
    ], [
      'mode'   => $queryMode,
    ]);
    if (!is_string($result)) $result['error'] = 'Error load ' . $queryMode;

    return $result;
  }

  /**
   * Запрос на расчет в 1с
   * @param Main $main
   * @return array|string[]
   */
  static function queryCalc(Main $main): array
  {
    $result = [
      'calcResult' => httpRequest(MAIN_1C_SERVER_URL, [
        'method' => 'post',
        'auth'   => 'Basic ' . base64_encode( 'http_service:L3g2n0_2'),
        'contentType' => 'application/json; charset=utf-8',
        'json'    => false,
        'timeout' => 119,
      ], $main->url->request->get('data'))
    ];

    $dataResult = is_string($result['calcResult']) ? json_decode($result['calcResult'], true) : $result['calcResult'];
    // If the "calcResult" field does not exist, then send an error to the TG bot.
    if (json_last_error() !== JSON_ERROR_NONE || !isset($dataResult['tab_elements']) || isset($dataResult['error'])) {
      if (isset($dataResult['error']['data_error'])) {
        $filename   = $dataResult['error']['TelegramFile'] ?? null;
        $dataResult = $dataResult['error']['data_error'];
        $result = [
          'filename'   => $filename,
          'calcResult' => $dataResult,
        ];
      } else $result = ['calcResult' => ''];
      $botList = file_exists(SUBSCRIBE) ? json_decode(file_get_contents(SUBSCRIBE), true) : false;

      // Send a bug report to the TG bot
      if (is_array($dataResult) && is_array($botList)) {
        $msg = '';
        foreach ($dataResult as $k => $v) {
          $v = is_string($v) ? str_replace(['<', '>'], ['&lt;', '&gt;'], $v) : 'Не строка';
          $msg .= "<b>$k</b>: <i>$v</i>\n\n";
        }
        $sendRg = [
          'text' => empty($msg) ? 'Ответ с сервера не содержит данных' : $msg,
          'parse_mode' => 'HTML',
        ];

        foreach ($botList as $chatId => $user) {
          if ($user['type'] === 'maker') {
            $sendRg['chat_id'] = $chatId;
            $res = httpRequest(URL_TELEGRAM . TOKEN_TELEGRAM . '/sendMessage', ['method' => 'post'], json_encode($sendRg));

            if (!$res['ok']) {
              def($res, false);
              $result['error'] = 'Telegram error: ' . $res['description'];
              break;
            }
          }
        }
      }
    }

    return $result;
  }

  static function savePdf(Main $main): array
  {
    $request = $main->url->request;

    $data = [
      'reportValue' => json_decode($request->get('reportValue') ?? '[]', true)
    ];

    if (!count($data['reportValue'])) {
      return ['error' => 'ReportValue is empty'];
    }

    $userData = $main->db->getUserById($main->getLogin('id'));
    $data['userData'] = $userData;
    unset($userData);

    // Поменять папку для файлов
    $docs = new Docs($main, ['orientation' => 'L'], $data, 'mainPdf');

    $dir = boolValue($request->get('tempDir', false)) ? 'temp/' : 'documents/' ;
    $result['fileLink'] = $docs->getDocs('save', $dir);
    $result['fileLink'] = str_replace(ABS_SITE_PATH, $main->url->getBaseUri(), $result['fileLink']);

    return $result;
  }

  static function saveNewsContent(Main $main): array
  {
    $contentList = $main->url->request->get('contentList', '{}');
    $contentPath = ABS_SITE_PATH . SHARE_PATH . 'newsContent.json';
    $contentList = json_decode($contentList, true);
    $result = file_put_contents($contentPath, json_encode($contentList, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));

    return !$result ? ['error' => 'Save error'] : [];
  }

  /**
   * @throws Exception
   */
  static function getJpg(Main $main): array
  {
    $request = $main->url->request;

    $result['data'] = [];
    $mimePng = 'data:image/png;base64,';
    $mimeJpg = 'data:image/jpg;base64,';

    $images = json_decode($request->get('reportValue', '[]'), true)['images'];
    if (!count($images)) throw new Exception('getJpg: Ошибка отправки картинок!');

    $totalH = 0; // Общая высота всех чертежей
    $width  = 0; // Ширина

    $images = array_map(function ($item) use ($mimePng, &$totalH, &$width) {
      $imgRes = imagecreatefromstring(base64_decode(str_replace($mimePng, '', $item)));

      $totalH += imagesy($imgRes);
      if ($width === 0) $width = imagesx($imgRes);

      return $imgRes;
    }, $images);

    $resultImage = imagecreatetruecolor($width, $totalH);
    $white = imagecolorallocate($resultImage, 255, 255, 255);
    imagefill($resultImage, 0, 0, $white);

    $dstY = 0;
    foreach ($images as $image) {
      $w = imagesx($image);
      $h = imagesy($image);
      imagecopy($resultImage, $image, 0, $dstY, 0, 0, $w, $h);

      $dstY += $h;
    }

    if (boolValue($request->get('links', false))) {
      $dir = boolValue($request->get('tempDir', false)) ? 'temp/' : 'documents/' ;
      $filename = $request->get('filename', uniqid()) . '.jpg';
      imagejpeg($resultImage, ABS_SITE_PATH . SHARE_PATH . $dir . $filename, 85);

      $result['link'] = $main->url->getBaseUri() . SHARE_PATH . $dir . $filename;
    } else {
      ob_start();
      imagejpeg($resultImage, null, 100);
      $resultImage = ob_get_clean();

      $result['name'] = 'ligron_' . substr(uniqid(), -5, 5) . '.jpg';
      $result['body'] = $mimeJpg . base64_encode($resultImage);
    }

    return $result;
  }

   static function saveLog(Main $main): array
   {
     $log = $main->url->request->get('log', '');
     if (empty($log)) return [];

     file_put_contents(LOG_FOLDER . $main->url->request->get('filename', 'errorFileName.json'), $log);
     return [];
   }

   static function order1c(Main $main)
   {
     $request = $main->url->request;

     $token = $request->get('token');
     $orderNumber = $request->get('ligron_number');

     if (!$token) { return ['error' => "The Token does not exist."]; }
     if (!$orderNumber) { return ['error' => "The Number does not exist."]; }

     // Загрузить пользователей дилеров (первых 10ти)
     $dealers = $main->db->loadDealers(true, false);
     $dealers = array_map(function ($dealer) {
       return [
         'id'       => $dealer['id'],
         'dbPrefix' => $dealer['cmsParam']['dbPrefix'] ?? $dealer['cmsParam']['prefix'], // Support old name
       ];
     }, $dealers);

     $countPerPart = 20;
     $countDealers = count($dealers);
     $countPart = ceil($countDealers / $countPerPart);

     for ($i = 0; $i < $countPart; $i++) {
       $sql = '';

       for ($j = 0; $j < $countPerPart; $j++) {
         $index = $i * $countPerPart + $j;
         if ($index >= $countDealers) break;

         $dealer = $dealers[$index];
         $sql .= " UNION SELECT ID as 'id', name, login, password, "
           . "'$dealer[id]' as dealerId "
           . "FROM $dealer[dbPrefix]users "
           . ' WHERE activity = 1';

         $sql .= ' AND contacts LIKE "%' . $token . '%"';
       }

       $result = DbMain::getAll(substr($sql, 7));
       if (count($result) === 1) { $result = $result[0]; break; }
     }

     if (isset($result['dealerId'])) {
       $requestParams = $request->all();
       unset($requestParams['customMode']);
       unset($requestParams['token']);
       unset($requestParams['PHPSESSID']);

       $target = $main->url->getUri() . "dealer/$result[dealerId]/?";
       $target .= http_build_query(array_merge($requestParams, [
         'mode'      => 'auth',
         'cmsAction' => 'token',
         'param'     => $main->encrypt($token),
       ]));

       $main->response->header('Location', $target)->setStatusCode(303)->sendHeaders();
       exit();
     }

     return ['error' => 'Token not found'];
   }
}
