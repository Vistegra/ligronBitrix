<?php

namespace RemoteDb;

use DbMain;
use RedBeanPHP\RedException;

class Db
{
  use DbSsoTrait;

  /**
   * @var bool
   */
  private $isConnected = false;

  /**
   * @var array
   */
  private $dbConfig;

  /**
   * @var DbMain|DBQuery
   */
  private $handler;

  /**
   * @throws RedException
   */
  public function __construct(\Main $main, bool $useQuery = !false)
  {
    $this->handler = $useQuery ? new DBQuery() : new DbMain($main);
  }

  public function setConfig(array $dbConfig): self
  {
    $this->dbConfig = $dbConfig;

    return $this;
  }

  /**
   * @throws RedException
   */
  public function connect()
  {
    if (!$this->isConnected) {
      if ($this->handler instanceof DBQuery) {
        $this->isConnected = true;
        return;
      }

      if (!count($this->dbConfig)) {
        die('DB: Config error');
      }

      //$this->dbName = $this->dbConfig['dbName'];

      $this->handler->addDb('devDB', $this->dbConfig);
      $this->handler->selectDb('devDB');

      !$this->handler->testConnection() && die('DB: Data base connect error!');

      $this->handler->freeze();

      $this->isConnected = true;
    }
  }

  /**
   * @param string $snake
   * @param bool $capitalizeFirstCharacter
   * @return string
   */
  public function snakeCamels(string $snake, bool $capitalizeFirstCharacter = false): string {
    $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));

    if (!$capitalizeFirstCharacter) {
      $str[0] = strtolower($str[0]);
    }

    return $str;
  }

  /**
   * Trims values
   * @param array $row
   * @param bool $camelize - snake_case to camelCase
   * @return array
   */
  public function normalizeField(array $row, bool $camelize = false): array
  {
    foreach ($row as $k => $v) {
      if ($camelize) $k = $this->snakeCamels($k);
      $row[$k] = trim($v);
    }

    return $row;
  }

  // -------------------------------------------------------------------------------------------------------------------
  // USERS
  // -------------------------------------------------------------------------------------------------------------------

  private function getUserBaseSql(string $type): string
  {
    if ($type === 'dealer') {
      return "
        SELECT 
          u.id,
          u.username AS login,
          u.password,
          u.name,
          u.phone,
          u.email,
          dr.role_code AS roleCode,
          dr.name      AS roleName,
          dr.role_code AS roleCode,
          s.salon_code AS salonCode,
          s.name       AS salonName,
          d.inn_dealer AS dealerTin,
          d.name       AS dealerName
        FROM dealer_users u
        LEFT JOIN dealer_roles dr ON dr.role_code = u.role_code
        LEFT JOIN salons s ON s.salon_code = u.salon_code
        LEFT JOIN combination_dealer_salons cds ON cds.salon_code = u.salon_code
        LEFT JOIN dealers d ON d.inn_dealer = cds.inn_dealer
      ";
    }

    return "
      SELECT 
        u.id,
        u.user_code AS userCode,
        u.username  AS login,
        u.password,
        u.name,
        u.email,
        u.phone,
        lr.role_code AS roleCode,
        lr.name      AS roleName,
        d.inn_dealer AS dealerTin,
        d.name       AS dealerName
      FROM ligron_users u
      LEFT JOIN ligron_roles lr ON lr.role_code = u.role_code
      LEFT JOIN combination_dealer_ligron cmd ON cmd.user_code = u.user_code AND cmd.active = 1
      LEFT JOIN dealers d ON d.inn_dealer = cmd.inn_dealer AND d.active = 1
    ";
  }

  public function getUserByLoginType(string $loginType, string $login): array
  {
    $sql = $this->getUserBaseSql($loginType) . ' WHERE u.active = 1';
    $users = $this->handler->getAll($sql);
    $user = array_find($users, function (array $row) use ($login) {
      return $row['login'] === $login;
    });

    return empty($user) ? [] : $user;
  }

  public function getUserByLoginPasswordType(string $loginType, string $login, string $password): array
  {
    $sql = $this->getUserBaseSql($loginType) . ' WHERE u.active = 1';
    $users = $this->handler->getAll($sql);
    $user = array_find($users, function (array $row) use ($login, $password) {
      return $row['login'] === $login && $row['password'] === $password;
    });

    return empty($user) ? [] : $user;
  }

  public function getUsersBySalonCode(string $salonCode): array
  {
    $sql = $this->getUserBaseSql('dealer');
    $users = $this->handler->getAll($sql);

    return array_values(array_filter($users, function (array $row) use ($salonCode) {
      return $row['salonCode'] === $salonCode;
    }));
  }

  /**
   * Get all users by dealer INN and their salons
   * @param string $loginType
   * @param string $dealerTin
   * @param string $salonCode
   * @return array
   */
  public function getUsersByInnDealer(string $loginType, string $dealerTin, string $salonCode = ''): array
  {
    $sql   = $this->getUserBaseSql($loginType);
    $users = $this->handler->getAll($sql);

    return array_values(array_filter($users, function (array $row) use ($dealerTin, $salonCode) {
      return $row['dealerTin'] === $dealerTin
             &&
             (!($salonCode !== '') || $row['salonCode'] === $salonCode);
    }));
  }

  public function getAllUsersByType(string $loginType): array
  {
    if ($loginType === 'dealer') {
      $sql = "
        SELECT 
          u.username AS login,
          u.password,
          u.role_code AS roleCode
        FROM dealer_users u
        WHERE u.active = 1
      ";
    } else {
      $sql = "
        SELECT 
          u.username  AS login,
          u.password,
          u.role_code AS roleCode
        FROM ligron_users u
        WHERE u.active = 1
      ";
    }

    return $this->handler->getAll($sql);
  }

  // -------------------------------------------------------------------------------------------------------------------
  // DEALERS
  // -------------------------------------------------------------------------------------------------------------------

  public function getDealers(): array
  {
    return $this->handler->getAll("
      SELECT id, 
             inn_dealer AS dealerTin,
             name       AS dealerName 
      FROM dealers
      WHERE active = 1");
  }

  /**
   * Build graph and find related dealers using BFS
   * @param string $startValue
   * @param string $startType 'dealer' or 'salon'
   * @return array
   */
  private function findRelatedDealers(string $startValue, string $startType): array
  {
    // Надо ли добавить активность салона?
    $sql = "SELECT inn_dealer AS dealerTin, salon_code as salonCode FROM combination_dealer_salons";
    $relations = $this->handler->getAll($sql);

    $salonToDealers = [];
    $dealerToSalons = [];

    foreach ($relations as $r) {
      $salonToDealers[$r['salonCode']][] = $r['dealerTin'];
      $dealerToSalons[$r['dealerTin']][] = $r['salonCode'];
    }

    $visitedDealers = [];
    $visitedSalons = [];
    $queue = [$startValue];
    $foundDealers = [];

    while ($queue) {
      $current = array_shift($queue);

      if ($startType === 'salon') {
        if (!empty($visitedSalons[$current])) {
          continue;
        }
        $visitedSalons[$current] = true;

        foreach ($salonToDealers[$current] ?? [] as $dealer) {
          if (!empty($visitedDealers[$dealer])) {
            continue;
          }
          $visitedDealers[$dealer] = true;
          $foundDealers[] = $dealer;

          foreach ($dealerToSalons[$dealer] ?? [] as $nextSalon) {
            if (empty($visitedSalons[$nextSalon])) {
              $queue[] = $nextSalon;
            }
          }
        }
      } else {
        if (!empty($visitedDealers[$current])) {
          continue;
        }
        $visitedDealers[$current] = true;
        $foundDealers[] = $current;

        foreach ($dealerToSalons[$current] ?? [] as $salon) {
          if (!empty($visitedSalons[$salon])) {
            continue;
          }
          $visitedSalons[$salon] = true;

          foreach ($salonToDealers[$salon] ?? [] as $nextDealer) {
            if (empty($visitedDealers[$nextDealer])) {
              $queue[] = $nextDealer;
            }
          }
        }
      }
    }

    return $foundDealers;
  }

  /**
   * Get dealers by list of INN
   * @param array $tinList
   * @return array
   */
  public function getDealersByTinList(array $tinList): array
  {
    $dealers = $this->getDealers();

    if (count($tinList) === 0) return $dealers;

    return array_values(array_filter($dealers, function ($row) use ($tinList) {
      return includes($tinList, $row['dealerTin']);
    }));
  }

  /**
   * Get all dealers by dealer TIN using PHP recursion
   * @param string $tin
   * @return array
   */
  public function getDealersByTinRecursive(string $tin): array
  {
    $foundDealers = $this->findRelatedDealers($tin, 'dealer');

    return $this->getDealersByTinList($foundDealers);
  }

  /**
   * Get all dealers by salon code using PHP recursion
   * @param string $salonCode
   * @return array
   */
  public function getDealersBySalonRecursive(string $salonCode): array
  {
    $foundDealers = $this->findRelatedDealers($salonCode, 'salon');

    if (empty($foundDealers)) {
      return [];
    }

    return $this->getDealersByTinList($foundDealers);
  }

  /**
   * Get all dealers by manager user code
   * @param string $userCode
   * @param bool $one
   * @return array
   */
  public function getDealersByUserCode(string $userCode, bool $one = false): array
  {
    $sql = "
      SELECT
        d.id,
        d.inn_dealer  AS dealerTin,
        d.name        AS dealerName,
        cmd.user_code AS userCode
      FROM dealers d
      INNER JOIN combination_dealer_ligron cmd ON cmd.inn_dealer = d.inn_dealer
      WHERE cmd.active = 1 AND d.active = 1";
    $dealers = $this->handler->getAll($sql);
    $dealers = array_values(array_filter($dealers, function (array $row) use ($userCode) {
      return $row['userCode'] === $userCode;
    }));

    return $one ? $dealers[0] : $dealers;
  }

  // -------------------------------------------------------------------------------------------------------------------
  // SALONS
  // -------------------------------------------------------------------------------------------------------------------

  public function getSalons(): array
  {
    return $this->handler->getAll(
      "SELECT DISTINCT
        s.id,
        s.salon_code AS salonCode,
        s.name       AS salonName,
        cds.inn_dealer AS dealerTin
      FROM salons s
      INNER JOIN combination_dealer_salons cds ON cds.salon_code = s.salon_code
      WHERE s.active = 1"
    );
  }

  /**
   * Get all salons by dealer TIN
   * @param string $dealerTin
   * @return array
   */
  public function getSalonsByInnDealer(string $dealerTin): array
  {
    $salons = $this->getSalons();

    return array_values(array_filter($salons, function ($row) use ($dealerTin) {
      return $row['dealerTin'] === $dealerTin;
    }));
  }

  // -------------------------------------------------------------------------------------------------------------------
  // COMBINATION QUERIES
  // -------------------------------------------------------------------------------------------------------------------

  /**
   * Get all dealers with their salons
   * @return array
   */
  public function getDealersWithSalons(): array
  {
    $dealers = $this->getDealers();
    $salons  = $this->getSalons();

    $result = [];
    foreach ($dealers as $dealer) {
      $result[] = [
        'dealer' => [
          'id'         => (int)$dealer['id'],
          'dealerTin'  => $dealer['dealerTin'],
          'dealerName' => $dealer['dealerName'],
          'active'     => 1,
        ],
        'salons' => array_reduce($salons, function (array $r, array $salon) use ($dealer) {
          if ($salon['dealerTin'] === $dealer['dealerTin']) {
            $r[$salon['id']] = $salon;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }

  /**
   * Get all dealers with their Ligron users (Managers)
   * @return array
   */
  public function getDealersWithManagers(): array
  {
    $dealers = $this->handler->getAll("
        SELECT 
          d.id,
          d.inn_dealer AS dealerTin,
          d.name       AS dealerName,
          d.active,
          cmd.user_code AS userCode
        FROM dealers d
        INNER JOIN combination_dealer_ligron cmd ON cmd.inn_dealer = d.inn_dealer
        WHERE cmd.active = 1 AND d.active = 1
      ");
    $users   = $this->handler->getAll($this->getUserBaseSql('manager'));

    $result = [];
    foreach ($dealers as $dealer) {
      $result[] = [
        'dealer' => [
          'id'         => (int)$dealer['id'],
          'dealerTin'  => $dealer['dealerTin'],
          'dealerName' => $dealer['dealerName'],
          'active'     => 1,
        ],
        'users' => array_reduce($users, function (array $r, array $user) use ($dealer) {
          if ($user['userCode'] === $dealer['userCode']) {
            $r[$user['id']] = $user;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }

  /**
   * Get all dealers with their dealer users
   * @return array
   */
  public function getDealersWithUsers(): array
  {
    $dealers = $this->handler->getAll("
        SELECT 
          d.id,
          d.inn_dealer AS dealerTin,
          d.name       AS dealerName,
          d.active,
          cmd.user_code AS userCode
        FROM dealers d
        INNER JOIN combination_dealer_ligron cmd ON cmd.inn_dealer = d.inn_dealer
        WHERE cmd.active = 1 AND d.active = 1
      ");
    $users   = $this->handler->getAll($this->getUserBaseSql('dealer'));

    $result = [];
    foreach ($dealers as $dealer) {
      $result[] = [
        'dealer' => [
          'id'         => (int)$dealer['id'],
          'dealerTin'  => $dealer['dealerTin'],
          'dealerName' => $dealer['dealerName'],
          'active'     => 1,
        ],
        'users' => array_reduce($users, function (array $r, array $user) use ($dealer) {
          if ($user['dealerTin'] === $dealer['dealerTin']) {
            $r[$user['id']] = $user;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }

  /**
   * Get all Ligron users (Managers) with their dealers
   * @return array
   */
  public function getManagersWithDealers(): array
  {
    $users = $this->handler->getAll("
      SELECT 
        u.id,
        u.user_code AS userCode,
        u.username,
        u.name,
        u.email,
        u.phone,
        u.role_code AS roleCode,
        lr.name     AS roleName
      FROM ligron_users u
      LEFT JOIN ligron_roles lr ON lr.role_code = u.role_code
      WHERE u.active = 1
    ");
    $dealers = $this->handler->getAll("
        SELECT 
          d.id,
          d.inn_dealer AS dealerTin,
          d.name       AS dealerName,
          d.active,
          cmd.user_code AS userCode
        FROM dealers d
        INNER JOIN combination_dealer_ligron cmd ON cmd.inn_dealer = d.inn_dealer
        WHERE cmd.active = 1 AND d.active = 1
      ");

    $result = [];
    foreach ($users as $user) {
      $result[] = [
        'user' => [
          'id'       => (int)$user['id'],
          'userCode' => $user['userCode'],
          'username' => $user['username'],
          'roleCode' => $user['roleCode'],
          'name'     => $user['name'],
          'email'    => $user['email'],
          'phone'    => $user['phone'],
          'active'   => 1,
        ],
        'dealers' => array_reduce($dealers, function (array $r, array $dealer) use ($user) {
          if ($dealer['userCode'] === $user['userCode']) {
            $r[$dealer['id']] = $dealer;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }

  /**
   * Get all dealers with their salons
   * @return array
   */
  public function getSalonsWithDealers(): array
  {
    $salons  = $this->getSalons();
    $dealers = $this->getDealers();

    $result = [];
    foreach ($salons as $salon) {
      $result[] = [
        'salon' => [
          'id'        => (int)$salon['id'],
          'salonCode' => $salon['salonCode'],
          'salonName' => $salon['salonName'],
          'active'    => 1,
        ],
        'dealers' => array_reduce($dealers, function (array $r, array $dealer) use ($salon) {
          if ($dealer['dealerTin'] === $salon['dealerTin']) {
            $r[$dealer['id']] = $dealer;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }

  /**
   * Get all salons with their dealer users
   * @return array
   */
  public function getSalonsWithUsers(): array
  {
    $salons = $this->getSalons();
    $users = $this->handler->getAll($this->getUserBaseSql('dealer'));

    $result = [];
    foreach ($salons as $salon) {
      $result[] = [
        'salon' => [
          'id'        => (int)$salon['id'],
          'salonCode' => $salon['salonCode'],
          'salonName' => $salon['salonName'],
          'active'    => 1,
        ],
        'users' => array_reduce($users, function (array $r, array $user) use ($salon) {
          if ($user['salonCode'] === $salon['salonCode']) {
            $r[$user['id']] = $user;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }

  /**
   * Get all dealer users with their salons
   * @return array
   */
  public function getUsersWithSalons(): array
  {
    $users = $this->handler->getAll($this->getUserBaseSql('dealer'));
    $salons = $this->getSalons();

    $result = [];
    foreach ($users as $user) {
      $result[] = [
        'user' => [
          'id'        => (int)$user['id'],
          'username'  => $user['username'],
          'password'  => $user['password'],
          'salonCode' => $user['salonCode'],
          'roleCode'  => $user['roleCode'],
          'name'      => $user['name'],
          'phone'     => $user['phone'],
          'email'     => $user['email'],
          'active'    => 1,
        ],
        'salons' => array_reduce($salons, function (array $r, array $s) use ($user) {
          if ($user['salonCode'] === $s['salonCode']) {
            $r[$s['id']] = $s;
          }
          return $r;
        }, []),
      ];
    }

    return $result;
  }
}
