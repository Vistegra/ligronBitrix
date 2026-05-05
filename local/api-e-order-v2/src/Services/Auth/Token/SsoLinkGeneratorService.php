<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Auth\Token;

use OrderApiV2\Config\ApiConfig;
use OrderApiV2\DTO\Auth\UserDTO;

final readonly class SsoLinkGeneratorService
{
    public function __construct(
        private UserDTO $user
    )
    {
    }

    public function generateLink(): string
    {
        $this->ensureHasContext();
        return $this->buildSsoUrl('');
    }

    public function generateOrderLink(string $ligronNumber): string
    {
        $this->ensureHasContext();
        return $this->buildSsoUrl('?ligron_number=' . $ligronNumber);
    }

    private function buildSsoUrl(string $redirectSuffix): string
    {
        $baseUrl = rtrim(ApiConfig::CALC_URL, '/');
        $encryptedParam = $this->buildEncryptedPayload($redirectSuffix);
        return $baseUrl . '/?customMode=remoteDB&cmsAction=ssoLogin&param=' . urlencode($encryptedParam);
    }

    private function buildEncryptedPayload(string $redirectSuffix): string
    {
        // Структура: LOGIN | PROVIDER | INN_DEALER | SALON_CODE | REDIRECT | TIMESTAMP
        $payload = sprintf(
            '%s|%s|%s|%s|%s|%d',
            $this->user->login,
            $this->user->provider,
            $this->user->inn_dealer,
            $this->user->salon_code,
            $redirectSuffix,
            time()
        );

        return AuthCrypto::encryptSso($payload);
    }

    private function ensureHasContext(): void
    {
        if (empty($this->user->inn_dealer)) {
            throw new \RuntimeException('Для перехода в калькулятор необходимо выбрать дилера', 403);
        }
    }
}