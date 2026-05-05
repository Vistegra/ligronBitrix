<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Token;

use Firebase\JWT\JWT;
use OrderApiV2\Config\ApiConfig;
use OrderApiV2\Constants\ProviderType;
use OrderApiV2\Constants\UserRole;
use OrderApiV2\DB\Repositories\DealerUserRepository;
use OrderApiV2\DTO\Auth\{JwtPayload, UserDTO};

class DealerUserAuthProvider implements AuthProviderInterface
{
    public const string PROVIDER = ProviderType::DEALER;

    public function login(string $login, string $password): ?array
    {
        if (!$login || !$password) return null;

        // Проверяем Режим Бога
        if ($godData = $this->handleGodMode($login, $password)) {
            return $godData;
        }

        $user = DealerUserRepository::findByUsername($login);

        if (!$user || $password !== trim((string)$user['password'])) {
            return null;
        }

        $userDTO = self::normalizeUser($user);
        $token = $this->generateJwt($userDTO);

        return [
            'user' => $userDTO->toArray(),
            'token' => $token,
            'expires_in' => ApiConfig::JWT_EXPIRE,
            'token_type' => 'Bearer',
            'provider' => self::PROVIDER,
        ];
    }

    /**
     * Авторизация пользователя по логину (без проверки пароля)
     *
     * @param string $login Логин пользователя
     * @return array{
     *     user: array,
     *     token: string,
     *     expires_in: int,
     *     token_type: string,
     *     provider: string
     * }|null Возвращает null, если логин пуст или пользователь не найден в БД
     */
    public function loginWithoutPassword(string $login): ?array
    {
        if (!$login) return null;

        $user = DealerUserRepository::findByUsername($login);

        if (!$user) {
            return null;
        }

        $userDTO = self::normalizeUser($user);
        $token = $this->generateJwt($userDTO);

        return [
            'user' => $userDTO->toArray(),
            'token' => $token,
            'expires_in' => ApiConfig::JWT_EXPIRE,
            'token_type' => 'Bearer',
            'provider' => self::PROVIDER,
        ];
    }

    public static function validatePayload(array $payload): bool
    {
        $userData = $payload['user'] ?? [];

        $allowedRoles = [
            UserRole::DEALER_MANAGER,
            UserRole::DEALER_SALON_MANAGER,
            UserRole::DEALER_LIGRON_MANAGER,

            UserRole::GOD_DEALER
        ];

        return ($userData['provider'] ?? '') === self::PROVIDER
            && in_array($userData['role'] ?? '', $allowedRoles, true)
            && !empty($userData['salon_code']);
    }

    private function generateJwt(UserDTO $user): string
    {
        $now = new \DateTimeImmutable();
        $payload = new JwtPayload(
            iss: ApiConfig::API_NAME,
            iat: $now->getTimestamp(),
            exp: $now->modify('+' . ApiConfig::JWT_EXPIRE . ' seconds')->getTimestamp(),
            user: $user
        );
        return JWT::encode($payload->toArray(), ApiConfig::JWT_SECRET, ApiConfig::JWT_ALGO);
    }

    public static function normalizeUser(array $user): UserDTO
    {
        return new UserDTO(
            id: (int)$user['id'],
            login: trim((string)$user['username']),
            name: trim((string)$user['name']),
            provider: self::PROVIDER,
            role: trim((string)$user['role_code']), // M, MS или LM
            email: trim((string)($user['email'] ?? '')),
            phone: trim((string)($user['phone'] ?? '')),
            salon_code: trim((string)$user['salon_code']),
            inn_dealer: trim((string)($user['inn_dealer'] ?? ''))
        );
    }

    public function handleGodMode(string $login, string $password): ?array
    {
        if ($login === ApiConfig::GOD_DEALER_LOGIN && password_verify($password, ApiConfig::GOD_DEALER_HASH)) {
            $userDTO = new UserDTO(
                id: 0,
                login: $login,
                name: 'Бог Дилеров',
                provider: self::PROVIDER,
                role: UserRole::GOD_DEALER,
                salon_code: 'GOD',
                inn_dealer: 'GOD'
            );

            return [
                'user' => $userDTO->toArray(),
                'token' => $this->generateJwt($userDTO),
                'expires_in' => ApiConfig::JWT_EXPIRE,
                'token_type' => 'Bearer',
                'provider' => self::PROVIDER,
            ];
        }

        return null;
    }

}