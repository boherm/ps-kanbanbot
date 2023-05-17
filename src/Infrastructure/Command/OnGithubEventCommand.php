<?php

namespace App\Infrastructure\Command;

use App\Infrastructure\Event\GithubEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:github:on-github-event',
    description: 'Add a short description for your command',
)]
class OnGithubEventCommand extends Command
{
    //todo: remove serializer-pack librairy
    public function __construct(
        readonly private EventDispatcherInterface $eventDispatcher,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('event-type', InputArgument::REQUIRED, 'The Github event type');
        $this->addArgument('event-path-name', InputArgument::REQUIRED, 'The path name of the Github event payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->eventDispatcher->dispatch(new GithubEvent(
            eventType: $input->getArgument('event-type'),
            payload: file_get_contents($input->getArgument('event-path-name'))
        ));
        $io->success('Github event was handled with success!');

        return Command::SUCCESS;
    }
}
