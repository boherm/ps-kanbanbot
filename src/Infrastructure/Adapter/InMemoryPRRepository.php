<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Core\Domain\Aggregate\PR\PR;
use App\Core\Domain\Aggregate\PR\PRId;
use App\Core\Domain\Gateway\PRRepositoryInterface;

class InMemoryPRRepository implements PRRepositoryInterface
{
    /** @var PR[]  */
    private $prs = [];

    public function find(string $repositoryOwner, string $repositoryName, string $pullRequestNumber): ?PR
    {
        $pr = $this->prs[$this->getIdByPrId(PrId::create($repositoryOwner, $repositoryName, $pullRequestNumber))] ?? null;
        if ($pr instanceof PR) {
            $pr = clone $pr;
        }

        return $pr;
    }

    /**
     * @param PR[] $prs
     */
    public function feed(array $prs): void
    {
        foreach ($prs as $pr) {
            $this->prs[$this->getIdByPrId($pr->getId())] = $pr;
        }
    }

    public function update(PR $pr): void
    {
        $this->prs[$this->getIdByPrId($pr->getId())] = $pr;
    }

    private function getIdByPrId(PrId $prId): string
    {
        return $prId->repositoryOwner . '-' . $prId->repositoryName . '-' . $prId->pullRequestNumber;
    }
}