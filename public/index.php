<?php

session_start();
require __DIR__ . '/../vendor/autoload.php';

//use Psr\Http\Message\ResponseInterface as Response;
//use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use WPA\Connection;
use WPA\UrlRepository;
use WPA\Url;
use WPA\Check;
use WPA\CheckRepository;
use Carbon\Carbon;
//use Illuminate\Support;
//use Illuminate\Support\Optional;
//use Illuminate\Support\Arr;
//use Illuminate\Support\Collection;
use GuzzleHttp\Client;
//use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7;
//use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use DiDom\Document;
use DiDom\Query;



$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
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
    $messages = $this->get('flash')->getMessages();
    $urls = $urlRepository->getEntities();
    $params = [
      'flash' => $messages,
      'urls' => $urls
    ];
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $urlRepository = $this->get(UrlRepository::class);
    $checkRepository = $this->get(CheckRepository::class);
    $id = $args['id'];
    $url = $urlRepository->find($id);
    $checks = $checkRepository->findAllUrlId($id);
    if (is_null($url)) {
        return $response->write('Page not found')->withStatus(404);
    }
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
        'urlActive' => ['name']
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
        $this->get('flash')->addMessage('errors', 'Страница уже существует');
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
    $client = new Client();
    $errors = [];
    $url = $urlRepository->find($urlId);
    $code = 0;
    $h1Content = '';
    $titleContent = '';
    try {
        $responseUrl = $client->request('GET', $url->getName());
        $code = $responseUrl->getStatusCode();
        $body = $responseUrl->getBody()->getContents();
        $document = new Document($body);
        $h1Content = optional($document->first('h1'))->text() ?? '';
        $titleContent = optional($document->first('title'))->text() ?? '';
        $metaDescription = $document->first('meta[name="description"]');
        $descriptionContent = $metaDescription ? $metaDescription->getAttribute('content') : '';
    } catch (GuzzleException $e) {
        $errors[] = ['url' => 'Ошибка подключения'];
        $this->get('flash')->addMessage('errors', 'Произошла ошибка при проверке, не удалось подключиться');
    }
    if (count($errors) === 0) {
        $CreatedDT = date("Y-m-d H:i:s");
        $check = Check::fromArray([(int)$urlId, $code, $h1Content, $titleContent, $descriptionContent, $CreatedDT]);
        $id = $checkRepository->create($check);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        $params = [
            'id' => $urlId
        ];
        return $response->withRedirect($router->urlFor('urls.show', $params));
    }
    $params = [
        'id' => $urlId,
        'errors' => $errors
    ];
    return $response->withRedirect($router->urlFor('urls.show', $params));
})->setName('checks.store');


$app->run();
