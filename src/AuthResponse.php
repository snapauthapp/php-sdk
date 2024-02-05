<?php

declare(strict_types=1);

namespace SnapAuth;

readonly class AuthResponse
{
    public User $user;

    // @phpstan-ignore-next-line
    public function __construct(array $data)
    {
        $this->user = new User($data['user']);
    }
}
