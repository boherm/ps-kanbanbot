<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

use App\Shared\Domain\Gateway\CommitterRepositoryInterface;

class InMemoryCommitterRepository implements CommitterRepositoryInterface
{
    public function findAll(string $organisation): array
    {
        return [
            'lartist',
            'nicosomb',
        ];
    }
}
