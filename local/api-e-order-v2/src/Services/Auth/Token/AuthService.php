<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OrderApiV2\Config\ApiConfig;
use OrderApiV2\DTO\Auth\{JwtPayload, UserDTO};
use OrderApiV2\Constants\ProviderType;
use OrderApiV2\Services\Auth\Session\AuthSession;
use OrderApiV2\Services\LogService;

class AuthService
{
    public function login(string $login, string $password, string $providerType): ?array
    {
        AuthSession::clear();

        $provider = $this->getAuthProvider($providerType);
        return $provider?->login($login, $password);
    }

    public function logout(): void
    {
        AuthSession::clear();
    }


    /**
     * @param string $providerType
     * @return AuthProviderInterface|null
     */
    private function getAuthProvider(string $providerType): ?AuthProviderInterface
    {
        return match ($providerType) {
            DealerUserAuthProvider::PROVIDER => new DealerUserAuthProvider(),
            LigronUserAuthProvider::PROVIDER => new LigronUserAuthProvider(),
            default => null,
        };
    }

    /**
     * Валидация JWT токена
     */
    public static function validateToken(string $token): ?JwtPayload
    {
        try {
            $decoded = JWT::decode($token, new Key(ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO));
            $payload = JwtPayload::fromStdClass($decoded);
            $payloadArray = $payload->toArray();

            // Проверяем структуру payload
            if (!isset($payloadArray['user']) || !is_array($payloadArray['user'])) {
                return null;
            }

            $userData = $payloadArray['user'];
            $provider = $userData['provider'] ?? null;

            $service = match ($provider) {
                DealerUserAuthProvider::PROVIDER => DealerUserAuthProvider::class,
                LigronUserAuthProvider::PROVIDER => LigronUserAuthProvider::class,
                default => null,
            };

            if ($service && $service::validatePayload($payloadArray)) {
                return JwtPayload::fromArray($payloadArray);
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Получить UserDTO из JWT payload
     */
    public static function getUserFromToken(string $token): ?UserDTO
    {
        $payload = self::validateToken($token);
        return $payload?->user;
    }

    /**
     * Авторизация пользователя по внешнему SSO-токену
     *
     * @param string $token Зашифрованная строка (base64)
     * @return array{
     *     user: array,
     *     token: string,
     *     expires_in: int,
     *     token_type: string,
     *     provider: string
     * }|null Возвращает null при ошибке дешифровки, истечении срока или если пользователь не найден
     */
    public function loginByToken(string $token): ?array
    {
        try {

            $decrypted = AuthCrypto::decryptSso($token);

            if ($decrypted === null) {
                throw new \Exception("Ошибка расшифровки или неверный формат токена");
            }

            // Парсим строку: LOGIN | TYPE | TIMESTAMP | EXPIRED (формат из калькулятора)
            $parts = explode('|', $decrypted);
            if (count($parts) !== 4) {
                throw new \Exception("Нарушена структура токена.");
            }

            [$login, $type, $timestamp, $expired] = $parts;

            if (time() > ((int)$timestamp + (int)$expired)) {
                throw new \Exception("Срок действия токена истек");
            }

            $providerType = match ($type) {
                'dealer' => ProviderType::DEALER,
                'manager' => ProviderType::LIGRON,
                default => null
            };

            if (!$providerType) {
                throw new \Exception("Неизвестный тип пользователя в токене: {$type}");
            }

            $provider = $this->getAuthProvider($providerType);

            AuthSession::clear();

            return $provider?->loginWithoutPassword($login);

        } catch (\Throwable $e) {
            LogService::error('Login by token error: ' . $e->getMessage(), ['token' => $token], 'auth');
            return null;
        }

    }

}