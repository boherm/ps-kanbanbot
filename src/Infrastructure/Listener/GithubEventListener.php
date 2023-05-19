<?php

declare(strict_types=1);

namespace App\Infrastructure\Listener;

use App\Infrastructure\Event\GithubEvent;
use App\PullRequest\Application\Command\RequestChangesCommand;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
class GithubEventListener
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function __invoke(GithubEvent $event): void
    {
        /**
         * @var array{
         *     action: string,
         *     pull_request: array{
         *         base: array{
         *             repo: array{
         *                 name: string,
         *                 owner: array{
         *                     login: string
         *                 }
         *             }
         *         },
         *         number: int
         *     }
         * } $payloadAsArray
         */
        $payloadAsArray = json_decode($event->payload, true);

        // todo: test and use enum
        if ('pull_request_review' !== $event->eventType || 'submitted' !== $payloadAsArray['action']) {
            return;
        }

        $this->commandBus->dispatch(new RequestChangesCommand(
            $payloadAsArray['pull_request']['base']['repo']['owner']['login'],
            $payloadAsArray['pull_request']['base']['repo']['name'],
            (string) $payloadAsArray['pull_request']['number']
        ));
    }
}
