<?php

use App\Infrastructure\Adapter\RestPRRepository;
use App\Infrastructure\Adapter\SpyMessageBus;
use App\PullRequest\Domain\Gateway\PRRepositoryInterface;
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

    $services->alias(PRRepositoryInterface::class, RestPRRepository::class);

    if ('test' === $configurator->env()) {
        $configurator->parameters()
            ->set('test_tmp_dir', '%kernel.project_dir%/var/tests/tmp');

        $services
            ->defaults()
                ->public()
                ->autowire()
        ;

        $services->set(RestPRRepository::class);
        $services->alias(MessageBusInterface::class, SpyMessageBus::class);
    }
};
