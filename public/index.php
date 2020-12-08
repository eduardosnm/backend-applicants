<?php

use DI\Container;
use Dotenv\Dotenv;
use Osana\Challenge\Http\Controllers\FindUsersController;
use Osana\Challenge\Http\Controllers\ShowUserController;
use Osana\Challenge\Http\Controllers\StoreUserController;
use Osana\Challenge\Http\Controllers\VersionController;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// env vars
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// service container
$container = new Container();
$container->set(LocalUsersRepository::class, function () {
    return new LocalUsersRepository();
});
$container->set(GitHubUsersRepository::class, function () {
    return new GitHubUsersRepository();
});

// application
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->add(new WhoopsMiddleware(['enable' => env('API_ENV') === 'local']));

$app->addRoutingMiddleware();
// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails

) use ($app) {
//    var_dump($exception->getCode());exit;
    $payload = ['error' => $exception->getMessage()];

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response->withHeader('Content-Type', 'application/json')
        ->withStatus($exception->getCode(), $exception->getMessage());
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

// routes
$app->get('/', VersionController::class);
$app->get('/users', FindUsersController::class);
$app->get('/users/{type}/{login}', ShowUserController::class);
$app->post('/users', StoreUserController::class);


$app->run();
