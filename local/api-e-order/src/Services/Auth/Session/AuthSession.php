<?php

declare(strict_types=1);

namespace OrderApi\Services\Auth\Session;

use Bitrix\Main\Application;
use OrderApi\DTO\Auth\UserDTO;

/**
 * Фасад для работы с детальными данными пользователя в сессии (Bitrix D7)
 * @method static getSalonCode()
 * @method static getSalonName()
 * @method static getManagedDealers()
 */
final class AuthSession
{
  private const string SESSION_KEY = 'order_api_auth_detailed_data';

  private const int SESSION_LIFETIME = 3600; // 1 час

  /** @var AuthSessionProviderInterface[] */
  private static array $providers = [];

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

  /**
   * Загружает детальные данные пользователя в сессию (один раз)
   */
  public static function load(UserDTO $user): bool
  {
    if (self::isLoaded($user)) {
      return true;
    }

    foreach (self::getProviders() as $provider) {
      if ($provider->supports($user)) {
        $data = $provider->fetchDetailedData($user);

        $data['session_id'] = self::session()->getId();
        $data['fetched_at'] = time();
        $data['validation_key'] = "{$user->login}_{$user->id}_{$user->provider}_{$user->role}";

        if (empty($data)) {
          return false;
        }

        self::session()->set(self::SESSION_KEY, $data);
        self::session()->save();

        return true;
      }
    }

    return false;
  }

  /**
   * Данные уже загружены и еще не истекли?
   */
  private static function isLoaded(UserDTO $user): bool
  {
    $validationKey = "{$user->login}_{$user->id}_{$user->provider}_{$user->role}";
    $currentValidationKey = self::get('validation_key');
    $fetchedAt = self::get('fetched_at');

    // Проверяем совпадение ключа валидации
    if ($validationKey !== $currentValidationKey) {
      return false;
    }

    // Проверяем не истекло ли время
    if ($fetchedAt && (time() - $fetchedAt) > self::SESSION_LIFETIME) {
      // Время истекло - очищаем данные
      self::clear();
      return false;
    }

    return true;
  }

  /**
   * Получить значение по ключу
   */
  public static function get(string $key, mixed $default = null): mixed
  {
    $data = self::session()->get(self::SESSION_KEY);
    return is_array($data) ? ($data[$key] ?? $default) : $default;
  }

  /**
   * Проверить существование ключа
   */
  public static function has(string $key): bool
  {
    $data = self::session()->get(self::SESSION_KEY);
    return is_array($data) && array_key_exists($key, $data);
  }

  /**
   * Получить все данные
   */
  public static function all(): array
  {
    $data = self::session()->get(self::SESSION_KEY);
    return is_array($data) ? $data : [];
  }

  /**
   * Получить только публичные данные данные
   */
  public static function publicData(): array
  {
    $data = self::session()->get(self::SESSION_KEY);

    if ($data['password']) unset($data['password']);

    return is_array($data) ? $data : [];
  }

  /**
   * Очистить данные авторизации
   */
  public static function clear(): void
  {
    if (self::session()->has(self::SESSION_KEY)) {
      self::session()->remove(self::SESSION_KEY);
      self::session()->save();
    }
  }

  /**
   * Магические геттеры: getSalonCode(), getDealerPrefix(), getManagedDealers() и т.д.
   */
  public static function __callStatic(string $name, array $arguments)
  {
    if (!str_starts_with($name, 'get')) {
      throw new \BadMethodCallException("Method $name does not exist");
    }

    $key = lcfirst(substr($name, 3));
    // Поддержка camelCase → snake_case
    $key = ltrim(strtolower(preg_replace('/[A-Z]([A-Z]?[^A-Z])/', '_$0', $key)), '_');

    $default = $arguments[0] ?? null;
    return self::get($key, $default);
  }
}