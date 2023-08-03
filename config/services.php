<?php

use App\PullRequest\Domain\Gateway\PullRequestRepositoryInterface;
use App\PullRequest\Infrastructure\Adapter\RestPullRequestRepository;
use App\PullRequestDashboard\Domain\Gateway\PullRequestCardRepositoryInterface;
use App\PullRequestDashboard\Infrastructure\Adapter\GraphqlGithubPullRequestCardRepository;
use App\Shared\Domain\Gateway\CommitterRepositoryInterface;
use App\Shared\Infrastructure\Adapter\RestGithubCommitterRepository;
use App\Shared\Infrastructure\Adapter\SpyMessageBus;
use App\Shared\Infrastructure\Factory\CommandFactory\CommandFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\MessageBusInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return function (ContainerConfigurator $configurator) {
    $configurator->parameters()
        ->set('app.version', '1.2.0')
        ->set('pull_request_dashboard_number', '17')
    ;
    $services = $configurator->services();
    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->bind('$pullRequestDashboardNumber', '%pull_request_dashboard_number%')
        ->bind('$webhookSecret', '%env(WEBHOOK_SECRET)%')
        ->bind('$appVersion', '%app.version%')
    ;

    $services->load('App\\', '../src/')
        ->exclude([
            '../src/DependencyInjection/',
            '../src/Entity/',
            '../src/Kernel.php',
        ]);

    // Todo: Config about messenger.message_handler not tested
    $services->load('App\\PullRequest\\Application\\CommandHandler\\', '../src/PullRequest/Application/CommandHandler/')
        ->tag('messenger.message_handler');

    $services->load('App\\PullRequestDashboard\\Application\\CommandHandler\\', '../src/PullRequestDashboard/Application/CommandHandler/')
        ->tag('messenger.message_handler');

    $services->alias(PullRequestRepositoryInterface::class, RestPullRequestRepository::class);
    $services->alias(PullRequestCardRepositoryInterface::class, GraphqlGithubPullRequestCardRepository::class);
    $services->alias(CommitterRepositoryInterface::class, RestGithubCommitterRepository::class);
    $services->set(CommandFactory::class)
        ->args([tagged_iterator('app.shared.command_strategy')])
    ;

    if ('test' === $configurator->env()) {
        $configurator->parameters()
            ->set('test_tmp_dir', '%kernel.project_dir%/var/tests/tmp')
            ->set('sandbox_pr_owner', 'PrestaShop')
            ->set('sandbox_pr_repository', 'PrestaShop')
            ->set('sandbox_pr_number', '32852')
        ;

        $services
            ->defaults()
                ->public()
                ->autowire()
        ;

        $services->set(RestPullRequestRepository::class);
        $services->set(GraphqlGithubPullRequestCardRepository::class);
        $services->set(RestGithubCommitterRepository::class);
        $services->alias(MessageBusInterface::class, SpyMessageBus::class);
    }
};
