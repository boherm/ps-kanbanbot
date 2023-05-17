<?php

declare(strict_types=1);

namespace App\Tests\Core\Application\CommandHandler;

use App\Infrastructure\Adapter\InMemoryPRRepository;
use App\Core\Application\Command\RequestChangesCommand;
use App\Core\Application\CommandHandler\RequestChangesCommandHandler;
use App\Core\Domain\Aggregate\PR\PR;
use App\Core\Domain\Aggregate\PR\PRId;
use PHPUnit\Framework\TestCase;

class RequestChangesCommandHandlerTest extends TestCase
{

        public function testHandle(): void
        {
            $prRepository = new InMemoryPRRepository();
            $repositoryOwner = 'repositoryOwner';
            $repositoryName = 'repositoryName';
            $pullRequestNumber = 'pullRequestNumber';

            $prRepository->feed([
                //Todo: replace create by foundry
                PR::create(
                    id: PRId::create(
                        repositoryOwner: $repositoryOwner,
                        repositoryName: $repositoryName,
                        pullRequestNumber: $pullRequestNumber
                    ),
                    labels: []
                )
            ]);

            $addLabelCommandHandler = new RequestChangesCommandHandler($prRepository);
            $addLabelCommandHandler->__invoke(new RequestChangesCommand(
                repositoryOwner: $repositoryOwner,
                repositoryName: $repositoryName,
                pullRequestNumber: $pullRequestNumber
            ));
            $pr = $prRepository->find($repositoryOwner, $repositoryName, $pullRequestNumber);
            //todo : add enum instead
            $this->assertContains('Waiting for author', $pr->getLabels());
        }
}