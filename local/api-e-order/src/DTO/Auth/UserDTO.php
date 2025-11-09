<?php

declare(strict_types=1);

namespace OrderApi\DTO\Auth;

readonly class UserDTO
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
    public ?string $dealer_prefix = null
  ) {}

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
      'role' => $this->role
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
      dealer_prefix: $data['dealer_prefix'] ?? null
    );
  }
}