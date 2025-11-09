<?php

declare(strict_types=1);

namespace OrderApi\DTO\Auth;

readonly class JwtPayload
{
  public function __construct(
    public string $iss,
    public int $iat,
    public int $exp,
    public UserDTO $user
  ) {}

  public function toArray(): array
  {
    return [
      'iss' => $this->iss,
      'iat' => $this->iat,
      'exp' => $this->exp,
      'user' => $this->user->toArray()
    ];
  }

  public static function fromArray(array $data): self
  {
    return new self(
      iss: $data['iss'],
      iat: $data['iat'],
      exp: $data['exp'],
      user: UserDTO::fromArray($data['user'])
    );
  }
}