<?php

declare(strict_types=1);

namespace App\Shared\Domain\Gateway;

interface CommitterRepositoryInterface
{
    /**
     * @return string[]
     */
    public function findAll(string $organisation): array;

    public function isNewContributor(string $owner, string $repo, string $committer): bool;
}
