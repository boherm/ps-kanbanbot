<?php

declare(strict_types=1);

namespace App\Tests\PullRequest\Infrastructure\Adapter;

use App\PullRequest\Infrastructure\Adapter\RestPullRequestRepository;
use App\PullRequest\Domain\Aggregate\PullRequest\PullRequestId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RestPullRequestRepositoryTest extends KernelTestCase
{
    public function testFindUpdateMethods(): void
    {
        $kernel = self::bootKernel();
        /** @var RestPullRequestRepository $restPRRepository */
        $restPRRepository = $kernel->getContainer()->get(RestPullRequestRepository::class);
        // todo: (test) remove added Waiting for author label first before fetching it
        /** @var string $repositoryOwner */
        $repositoryOwner = self::$kernel->getContainer()->getParameter('sandbox_pr_owner');
        /** @var string $repositoryName */
        $repositoryName = self::$kernel->getContainer()->getParameter('sandbox_pr_repository');
        /** @var string $pullRequestNumber */
        $pullRequestNumber = self::$kernel->getContainer()->getParameter('sandbox_pr_number');
        $pr = $restPRRepository->find(new PullRequestId(
            $repositoryOwner,
            $repositoryName,
            $pullRequestNumber
        )
        );
        $this->assertNotNull($pr);

        $reflectionPr = new \ReflectionClass($pr);
        $labelsPropertyPR = $reflectionPr->getProperty('labels');
        // todo: use enum for label
        /** @var string[] $labels */
        $labels = $labelsPropertyPR->getValue($pr);
        $labelsPropertyPR->setValue($pr, array_merge($labels, ['Waiting for author']));

        $restPRRepository->update($pr);
        $pr = $restPRRepository->find(new PullRequestId('PrestaShop', 'PrestaShop', '32608'));
        $this->assertNotNull($pr);
        $this->assertContains('Waiting for author', $pr->getLabels());
    }
}
