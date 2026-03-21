<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Migration;

use OrderApiV2\DB\Models\DealerTable;
use OrderApiV2\DB\Models\SalonTable;
use OrderApiV2\DB\Models\DealerUserTable;
use OrderApiV2\DB\Models\DealerSalonTable;
use OrderApiV2\Traits\CacheableTrait;

class HierarchyCheckService
{
  use CacheableTrait;

  private const int CACHE_TTL = 86400; // 24 часа
  private const string CACHE_ID = 'full_hierarchy_audit_v2_';

  public function getFullTree(): array
  {
    return self::cache(
      cacheId: self::CACHE_ID,
      ttl: self::CACHE_TTL,
      callback: fn() => $this->buildTree(),
      cacheDir: '/order_api_v2/audit'
    );
  }

  private function buildTree(): array
  {
    // 1. Загружаем справочники в память (Maps) для быстрой сборки
    $dealers = DealerTable::getList(['filter' => ['=active' => 1]])->fetchAll();

    $salonsRaw = SalonTable::getList(['filter' => ['=active' => 1]])->fetchAll();
    $salonsMap = [];
    foreach ($salonsRaw as $s) {
      $salonsMap[$s['salon_code']] = $s;
    }

    $usersRaw = DealerUserTable::getList(['filter' => ['=active' => 1]])->fetchAll();
    $salonUsersMap = [];
    foreach ($usersRaw as $u) {
      $salonUsersMap[$u['salon_code']][] = $u;
    }

    // 2. Загружаем все связи (та самая таблица-клей)
    $links = DealerSalonTable::getList()->fetchAll();
    $dealerToSalons = [];
    foreach ($links as $link) {
      $dealerToSalons[$link['inn_dealer']][] = $link['salon_code'];
    }

    // 3. Собираем дерево
    $tree = [];
    foreach ($dealers as $d) {
      $inn = $d['inn_dealer'];
      $dealerNode = [
        'name' => $d['name'],
        'inn' => $inn,
        'salons' => []
      ];

      // Находим все коды салонов этого дилера
      $attachedCodes = $dealerToSalons[$inn] ?? [];
      foreach ($attachedCodes as $code) {
        if (isset($salonsMap[$code])) {
          $dealerNode['salons'][] = [
            'name' => $salonsMap[$code]['name'],
            'code' => $code,
            // Пользователи привязаны к коду салона,
            // поэтому они "появятся" под каждым дилером этого салона
            'users' => $salonUsersMap[$code] ?? []
          ];
        }
      }
      $tree[] = $dealerNode;
    }

    return $tree;
  }

  public function renderHtml(): string
  {
    $tree = $this->getFullTree();
    $html = '<div class="audit-container">';

    foreach ($tree as $dealer) {
      $html .= "<details class='dealer-card'>";
      $html .= "<summary class='dealer-summary'>
                        <span class='icon'>🏢</span>
                        <span class='name'>{$dealer['name']}</span>
                        <span class='badge inn'>ИНН: {$dealer['inn']}</span>
                        <span class='badge count'>Салонов: " . count($dealer['salons']) . "</span>
                      </summary>";

      if (empty($dealer['salons'])) {
        $html .= "<p class='empty'>У этого дилера нет привязанных салонов</p>";
      } else {
        $html .= "<div class='salon-wrapper'>";
        foreach ($dealer['salons'] as $salon) {
          $uCount = count($salon['users']);
          $html .= "<details class='salon-card " . ($uCount === 0 ? 'warning' : '') . "'>";
          $html .= "<summary class='salon-summary'>
                                <span class='icon'>🏠</span>
                                <span class='name'>{$salon['name']}</span>
                                <span class='code'>({$salon['code']})</span>
                                <span class='u-count'>Сотрудников: {$uCount}</span>
                              </summary>";

          if ($uCount > 0) {
            $html .= "<table class='user-table'>
                                    <thead><tr><th>ФИО</th><th>Логин</th><th>Роль</th><th>Email / Телефон</th></tr></thead>
                                    <tbody>";
            foreach ($salon['users'] as $user) {
              $html .= "<tr>
                                        <td class='u-name'>{$user['name']}</td>
                                        <td class='u-login'><code>{$user['username']}</code></td>
                                        <td class='u-role'><span class='role-tag'>{$user['role_code']}</span></td>
                                        <td class='u-contacts'>{$user['email']}<br><small>{$user['phone']}</small></td>
                                      </tr>";
            }
            $html .= "</tbody></table>";
          } else {
            $html .= "<p class='empty-alert'>⚠️ Внимание: в салоне нет ни одного активного пользователя.</p>";
          }
          $html .= "</details>";
        }
        $html .= "</div>";
      }
      $html .= "</details>";
    }

    $html .= '</div>';
    return $html;
  }
}