<?php

declare(strict_types=1);

namespace OrderApi\DTO\Auth;

use OrderApi\Constants\ProviderType;
use OrderApi\Constants\UserRole;

final readonly class UserDTO
{
  public function __construct(
    public int     $id,
    public string  $login,
    public string  $name,
    public string  $provider,
    public string  $role,
    public ?string $email = null,
    public ?string $phone = null,
    public ?int    $dealer_id = null,
    public ?string $dealer_prefix = null,
    public ?string $code = null,
  ) {}

  public function isDealer(): bool
  {
    return $this->provider === ProviderType::DEALER;
  }

  public function isManager(): bool
  {
    return $this->provider === ProviderType::LIGRON && $this->role === UserRole::MANAGER;
  }

  public function isOfficeManager(): bool
  {
    return $this->provider === ProviderType::LIGRON && $this->role === UserRole::OFFICE_MANAGER;
  }

  public function isLigronStaff(): bool
  {
    return $this->provider === ProviderType::LIGRON;
  }

  public function toArray(): array
  {
    return array_filter([
      'id' => $this->id,
      'login' => $this->login,
      'name' => $this->name,
      'email' => $this->email,
      'phone' => $this->phone,
      'dealer_id' => $this->dealer_id,
      'dealer_prefix' => $this->dealer_prefix,
      'provider' => $this->provider,
      'role' => $this->role,
      'code' => $this->code
    ], fn($value) => $value !== null);
  }

  public static function fromArray(array $data): self
  {
    return new self(
      id: (int)$data['id'],
      login: $data['login'],
      name: $data['name'],
      provider: $data['provider'],
      role: $data['role'],
      email: $data['email'] ?? null,
      phone: $data['phone'] ?? null,
      dealer_id: isset($data['dealer_id']) ? (int)$data['dealer_id'] : null,
      dealer_prefix: $data['dealer_prefix'] ?? null,
      code: $data['code'] ?? null,
    );
  }
  public static function fromStdClass(\stdClass $obj): self
  {
    return new self(
      id: (int)$obj->id,
      login: $obj->login,
      name: $obj->name,
      provider: $obj->provider,
      role: $obj->role,
      email: $obj->email ?? null,
      phone: $obj->phone ?? null,
      dealer_id: isset($obj->dealer_id) ? (int)$obj->dealer_id : null,
      dealer_prefix: $obj->dealer_prefix ?? null,
      code: $obj->code ?? null,
    );
  }

}