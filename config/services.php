<?php

use App\Infrastructure\Adapter\RestPullRequestRepository;
use App\Infrastructure\Adapter\SpyMessageBus;
use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\MessageBusInterface;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();
    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true);

    $services->load('App\\', '../src/')
        ->exclude([
            '../src/DependencyInjection/',
            '../src/Entity/',
            '../src/Kernel.php',
        ]);

    $services->load('App\\PullRequest\\Application\\CommandHandler\\', '../src/PullRequest/Application/CommandHandler/')
        ->tag('messenger.message_handler');

    $services->alias(PullRequestRepositoryInterface::class, RestPullRequestRepository::class);

    if ('test' === $configurator->env()) {
        $configurator->parameters()
            ->set('test_tmp_dir', '%kernel.project_dir%/var/tests/tmp')
            ->set('sandbox_pr_owner', 'PrestaShop')
            ->set('sandbox_pr_repository', 'PrestaShop')
            ->set('sandbox_pr_number', '32618')
        ;

        $services
            ->defaults()
                ->public()
                ->autowire()
        ;

        $services->set(RestPullRequestRepository::class);
        $services->alias(MessageBusInterface::class, SpyMessageBus::class);
    }
};
