<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Infrastructure\Adapter\RestPRRepository;
use App\PullRequest\Domain\Aggregate\PR\PR;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RestPRRepositoryTest extends KernelTestCase
{
    public function testFindUpdateMethods(): void
    {
        $kernel = self::bootKernel();
        /** @var RestPRRepository $restPRRepository */
        $restPRRepository = $kernel->getContainer()->get(RestPRRepository::class);
        //todo: user sandbox pr
        $pr = $restPRRepository->find('PrestaShop', 'PrestaShop', '32608');
        $this->assertNotNull($pr);

        $reflectionPr = new ReflectionClass($pr);
        $labelsPropertyPR = $reflectionPr->getProperty('labels');
        //todo: use enum for label
        /** @var string[] $labels */
        $labels = $labelsPropertyPR->getValue($pr);
        $labelsPropertyPR->setValue($pr, array_merge($labels, ['Waiting for author']));

        $restPRRepository->update($pr);
        $pr = $restPRRepository->find('PrestaShop', 'PrestaShop', '32608');
        $this->assertNotNull($pr);
        $this->assertContains('Waiting for author', $pr->getLabels());
        //todo: remove added label
    }
}