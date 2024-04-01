<?php

declare(strict_types=1);

namespace SnapAuth;

class AuthResponse
{
    public readonly User $user;

    // @phpstan-ignore-next-line
    public function __construct(array $data)
    {
        $this->user = new User($data['user']);
    }
}
