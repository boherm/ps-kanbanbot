<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\RestGithubCommitterRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RestGithubCommitterRepositoryTest extends KernelTestCase
{
    public function testFindAllMethod(): void
    {
        $kernel = self::bootKernel();
        /** @var RestGithubCommitterRepository $restGithubCommitterRepository */
        $restGithubCommitterRepository = $kernel->getContainer()->get(RestGithubCommitterRepository::class);
        $expectedCommitters = [
            '0x346e3730', 'FabienPapet', 'Hlavtox', 'PululuK', 'SharakPL', 'NeOMakinG', 'atomiix', 'boherm', 'eternoendless',
            'jolelievre', 'kpodemski', 'lartist', 'marsaldev', 'matks', 'matthieu-rolland', 'mflasquin', 'mparvazi', 'nicosomb',
            'sowbiba', 'zuk3975', 'leemyongpakvn', 'tleon', 'M0rgan01', 'ga-devfront', 'jokesterfr',
        ];
        $actualCommitters = $restGithubCommitterRepository->findAll('PrestaShop');
        sort($expectedCommitters);
        sort($actualCommitters);
        $this->assertEquals(
            $expectedCommitters,
            $actualCommitters
        );
    }
}
