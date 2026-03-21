<?php
declare(strict_types=1);

namespace OrderApiV2\DTO\Auth;

use OrderApiV2\Constants\ProviderType;

final readonly class UserDTO
{
  public function __construct(
    public int     $id,
    public string  $login,
    public string  $name,
    public string  $provider,
    public string  $role, // M, MS, LM (Дилеры) или ML, OML (Лигрон)
    public ?string $email = null,
    public ?string $phone = null,
    public ?string $salon_code = null,
    public ?string $inn_dealer = null,
    public ?string $user_code = null, // Только для менеджеров Лигрон
  ) {}

  public function isDealer(): bool
  {
    return $this->provider === ProviderType::DEALER;
  }

  public function isManager(): bool
  {
    return $this->provider === ProviderType::LIGRON && $this->role === 'ML';
  }

  public function isOfficeManager(): bool
  {
    return $this->provider === ProviderType::LIGRON && $this->role === 'OML';
  }

  public function isLigronStaff(): bool
  {
    return $this->provider === ProviderType::LIGRON;
  }

  public function toArray(): array
  {
    return array_filter([
      'id'         => $this->id,
      'login'      => $this->login,
      'name'       => $this->name,
      'email'      => $this->email,
      'phone'      => $this->phone,
      'salon_code' => $this->salon_code,
      'inn_dealer' => $this->inn_dealer,
      'user_code'  => $this->user_code,
      'provider'   => $this->provider,
      'role'       => $this->role,
    ], fn($value) => $value !== null);
  }

  public static function fromArray(array $data): self
  {
    return new self(
      id:         (int)$data['id'],
      login:      $data['login'],
      name:       $data['name'],
      provider:   $data['provider'],
      role:       $data['role'],
      email:      $data['email'] ?? null,
      phone:      $data['phone'] ?? null,
      salon_code: $data['salon_code'] ?? null,
      inn_dealer: $data['inn_dealer'] ?? null,
      user_code:  $data['user_code'] ?? null,
    );
  }

  public static function fromStdClass(\stdClass $obj): self
  {
    return self::fromArray((array)$obj);
  }

}