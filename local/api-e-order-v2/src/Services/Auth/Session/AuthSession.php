<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Session;

use Bitrix\Main\Application;
use OrderApiV2\DTO\Auth\UserDTO;

/**
 * Фасад для работы с детальными данными пользователя в сессии (Bitrix D7)
 *
 * Для пользователя дилера:
 * @method static string|null getSalonCode()
 * @method static string|null getSalonName()
 * @method static string|null getInn()
 * @method static array getManagers()
 * @method static array getAvailableSalons()
 *
 * Для пользователя менеджера:
 * @method static array getSubstitutingCodes()
 *
 * Для всех пользователей:
 * @method static array getAvailableInns()
 * @method static array getHierarchy()
 */
final class AuthSession
{
  private const string SESSION_KEY = 'order_api_auth_detailed_data_v2';
  private const int SESSION_LIFETIME = 3600;

  private static array $providers = [];

  /**
   * @return AuthSessionProviderInterface[]
   */
  private static function getProviders(): array
  {
    if (self::$providers === []) {
      self::$providers = [
        new DealerAuthSessionProvider(),
        new LigronManagerAuthSessionProvider(),
      ];
    }
    return self::$providers;
  }

  private static function session(): \Bitrix\Main\Session\SessionInterface
  {
    return Application::getInstance()->getSession();
  }

  public static function load(UserDTO $user): bool
  {
    if (self::isLoaded($user)) {
      return true;
    }

    foreach (self::getProviders() as $provider) {
      if ($provider->supports($user)) {

        $data = $provider->fetchDetailedData($user);

        if (empty($data)) {
          return false;
        }

        // Системные поля
        $data['session_id'] = self::session()->getId();
        $data['fetched_at'] = time();
        $data['validation_key'] = "{$user->login}_{$user->id}_{$user->provider}_{$user->role}";

        self::session()->set(self::SESSION_KEY, $data);
        self::session()->save();

        return true;
      }
    }
    return false;
  }

  private static function isLoaded(UserDTO $user): bool
  {
    $validationKey = "{$user->login}_{$user->id}_{$user->provider}_{$user->role}";
    if ($validationKey !== self::get('validation_key')) return false;

    $fetchedAt = self::get('fetched_at');
    if ($fetchedAt && (time() - $fetchedAt) > self::SESSION_LIFETIME) {
      self::clear();
      return false;
    }
    return true;
  }

  public static function get(string $key, mixed $default = null): mixed
  {
    $data = self::session()->get(self::SESSION_KEY);
    return is_array($data) ? ($data[$key] ?? $default) : $default;
  }

  public static function has(string $key): bool
  {
    $data = self::session()->get(self::SESSION_KEY);
    return is_array($data) && array_key_exists($key, $data);
  }

  public static function all(): array
  {
    $data = self::session()->get(self::SESSION_KEY);
    return is_array($data) ? $data : [];
  }

  public static function publicData(): array
  {
    $data = self::session()->get(self::SESSION_KEY);
    if (isset($data['password'])) unset($data['password']);
    return is_array($data) ? $data : [];
  }

  public static function clear(): void
  {
    if (self::session()->has(self::SESSION_KEY)) {
      self::session()->remove(self::SESSION_KEY);
      self::session()->save();
    }
  }

  public static function __callStatic(string $name, array $arguments)
  {
    if (!str_starts_with($name, 'get')) {
      throw new \BadMethodCallException("Method $name does not exist");
    }
    $key = lcfirst(substr($name, 3));
    $key = ltrim(strtolower(preg_replace('/[A-Z]([A-Z]?[^A-Z])/', '_$0', $key)), '_');
    return self::get($key, $arguments[0] ?? null);
  }

}