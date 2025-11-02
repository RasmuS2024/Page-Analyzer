<?php

session_start();
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use WPA\Connection;
use WPA\Url;
use WPA\UrlRepository;
use WPA\Check;
use WPA\CheckRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DiDom\Document;

$container = new Container();

$container->set('renderer', function () {
    $renderer = new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');

    return $renderer;
});

$container->set('flash', function () {

    return new \Slim\Flash\Messages();
});

$container->set(\PDO::class, function () {
    $conn = Connection::get()->connect();

    return $conn;
});

$initFilePath = implode('/', [dirname(__DIR__), 'database.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(\PDO::class)->exec($initSql);

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('index');

$app->get('/urls', function ($request, $response) {
    $urlRepository = $this->get(UrlRepository::class);
    $urls = $urlRepository->getUrlsWithLastChecks();
    $params = [
        'urls' => $urls
    ];

    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $urlRepository = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepository->find($id);

    if (is_null($url)) {
        return $this->get('renderer')->render($response, '404.phtml');
    }

    $checkRepository = $this->get(CheckRepository::class);
    $checks = $checkRepository->getAllChecksForUrl($id);
    $messages = $this->get('flash')->getMessages();
    $params = [
        'url' => $url,
        'checks' => $checks,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $urlData = $request->getParsedBodyParam('url');
    $v = new Valitron\Validator($urlData);
    $v->rules([
        'required' => ['name'],
        'url' => ['name'],
    //    'urlActive' => ['name']
    ]);

    if (!$v->validate()) {
        $urlName = $urlData['name'] ?? '';
        $params = [
            'errors' => 'Некорректный URL',
            'url' => $urlName
        ];

        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
    }

    $id = $urlRepository->findIdByName($urlData['name']);

    if ($id) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
    }

    $CreatedDT = date("Y-m-d H:i:s");
    $url = Url::fromArray([$urlData['name'], $CreatedDT]);
    $id = $urlRepository->save($url);
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    $params = [
        'id' => $id
    ];

    return $response->withRedirect($router->urlFor('urls.show', $params));
})->setName('urls.store');

$app->post('/urls/{url_id}/checks', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $checkRepository = $this->get(CheckRepository::class);
    $urlId = $request->getAttribute('url_id');
    $url = $urlRepository->find($urlId);
    $client = new Client();
    $params = ['id' => $urlId];

    try {
        $responseUrl = $client->request('GET', $url->getName());
        $code = $responseUrl->getStatusCode();
        $body = $responseUrl->getBody()->getContents();
        $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $body);
        $document = new Document($body);
        $h1Content = optional($document->first('h1'))->text() ?? '';
        $titleContent = optional($document->first('title'))->text() ?? '';
        $metaDescription = $document->first('meta[name="description"]');
        $descriptionContent = $metaDescription ? $metaDescription->getAttribute('content') : '';
        $CreatedDT = date("Y-m-d H:i:s");
        $check = Check::fromArray([(int)$urlId, $code, $h1Content, $titleContent, $descriptionContent, $CreatedDT]);
        $checkRepository->create($check);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        return $response->withRedirect($router->urlFor('urls.show', $params));
    } catch (GuzzleException $e) {
        $this->get('flash')->addMessage('errors', 'Произошла ошибка при проверке, не удалось подключиться');
    } catch (\Exception $e) {
        $this->get('flash')->addMessage('errors', 'Произошла непредвиденная ошибка');
    }

    return $response->withRedirect($router->urlFor('urls.show', $params));
})->setName('checks.store');

$app->run();
