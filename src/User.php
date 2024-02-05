<?php

declare(strict_types=1);

namespace SnapAuth;

readonly class User
{
    public string $id;
    public string $handle;

    // @phpstan-ignore-next-line
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->handle = $data['handle'];
    }
}
