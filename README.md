# Kanbanbot

A bot to automatize some repetitive actions on PR's and [PR Dashboard](https://github.com/orgs/PrestaShop/projects/17)

## Requirements and application running

Only php (see the version [here](composer.json)) and a server like apache are needed.

1. Run `composer install`
2. Setup a webserver to serve the root directory of the application. For example use Symfony built-in server with command `symfony serve`

You can also use Docker.

## Functioning

Kanbanbot is mainly based on one webhook. See the following code [here](config/packages/framework.yaml) :
```yaml
webhook:
        routing:
            github:
                service: App\Shared\Infrastructure\Webhook\GithubWebhookParser
```

The production application has been setup so that GitHub send every event that happens on PrestaShop organization to Kanbanbot as [webhook](https://docs.github.com/en/webhooks-and-events/webhooks/about-webhooks).

Kanbanbot listens to received events and then run the appropriate commands.

## How to add a usecase ?

There are two layers to consider when adding a new usecase :
1. The first layout is responsible to match the appropriate commands in terms of the triggered event. To do that you have to 
create a new class which implements [App\Shared\Infrastructure\Factory\CommandFactory\CommandStrategyInterface.php](src/Shared/Infrastructure/Factory/CommandFactory/CommandStrategyInterface.php).
You can see some examples in the [src/Shared/Infrastructure/Factory/CommandFactory/Strategy/Command](src/Shared/Infrastructure/Factory/CommandFactory/Strategy/Command) folder.
In this layer you can also add global github event exclusions. To do that you can create a new class which implements [App\Shared\Infrastructure\Factory\CommandFactory\ExclusionStrategyInterface.php](src/Shared/Infrastructure/Factory/CommandFactory/ExclusionStrategyInterface.php). You can see some examples in the [src/Shared/Infrastructure/Factory/CommandFactory/Strategy/Exclusion](src/Shared/Infrastructure/Factory/CommandFactory/Strategy/Exclusion) folder.
2. The second layout contains the commands themselves. They are dispatched in several Bounded context like in [PullRequest](src/PullRequest/Application/CommandHandler) and in [PullRequestDashboard](src/PullRequestDashboard/Application/CommandHandler).

## Testing

The whole application is tested. This widely avoids regressions and allows easy refactoring or library updating.

There are three kinds of tests:
1. Unit tests. In this application unit test means that no infrastructure (like db, api calls ...) are used. This allows a really fast execution and then it gives a quick feedback to practice TDD. Also unit tests adopts a functional approach. It means that it tests the behavior of the application and not the implementation. Concretely they test CommandHandler.
[Here is an example](tests/PullRequest/Application/CommandHandler/AddLabelByAapprovalCountCommandHandlerTest.php)
2. Integration tests. They test only adapters like implementations of repositories. [Here is an example](tests/Shared/Infrastructure/Adapter/RestGithubCommitterRepositoryTest.php)
3. EndToEnd tests. They test that commands are well dispatched in terms of the request. [Here is an example](tests/Shared/Infrastructure/Webhook/GithubWebhookTest.php)

## Composer scripts

There are some composer scripts to help you to develop (unit tests, integration tests, end to end tests, code style, phpstan ...).
Before pushing a commit you can run `composer local-ci to` check if everything is ok.

## Deployment of kanbanbot new version

1. Bump the version number in `app.version` variable in the [config/service.php](config/services.php) file!
2. Follow the GitHub workflow described below to deploy latest version of `main` branch

You can verify the deployed version : ping the `/healthcheck` route that will return the version number.

## Environments

* **Production**: kanbanbot.prestashop-project.org

## Workflow

The GitHub workflow is used as follow:

![alt text](pics/workflow.png "Github Workflow")

As you can see from the schema above
- add the label "integration-deployment" to a Pull Request to trigger the deployment of the integration environment and be able to test it
- merge a Pull Request against branch `main` to trigger the deployment of the preprod environment and be able to test it
- publish a GitHub release to trigger the deployment of the production environment