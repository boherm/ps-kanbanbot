<?php

declare(strict_types=1);

namespace App\Shared\Domain\Gateway;

interface CommitterRepositoryInterface
{
    /**
     * @return string[]
     */
    public function findAll(string $organisation): array;
}
