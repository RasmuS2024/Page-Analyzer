<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use WPA\Connection;
use WPA\UrlRepository;
use WPA\Url;
use Carbon\Carbon;
use Illuminate\Support;				//
use Illuminate\Support\Arr;			//
use Illuminate\Support\Collection;	//

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set(\PDO::class, function () {
    /*
    $conn = new \PDO('sqlite:database.sqlite');
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    */
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

/*
try {
    Connection::get()->connect();
    echo 'A connection to the PostgreSQL database sever has been established successfully.';
} catch (\PDOException $e) {
    echo $e->getMessage();
}
*/

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
    //var_dump($params);
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $urlRepository = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepository->find($id);
    if (is_null($url)) {
        return $response->write('Page not found')->withStatus(404);
    }
    $messages = $this->get('flash')->getMessages();
    $params = [
        'url' => $url,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $urlData = $request->getParsedBodyParam('url');

    $v = new Valitron\Validator($urlData);
    $v->rules([
    	'url' => [['name']],
    	'required' => [['name']],
    ]);
    $errors = [];
    if($v->validate()) {
    	//echo "Yay! We're all good!";
    } else {
    	$errors = $v->errors();
    	//var_dump($v->errors());
    }
    //var_dump($urlData['name']);
    //var_dump($urlData);
    $id = $urlRepository->findByName($urlData['name']);
    //var_dump($id);
    
    if ($id) {
        $this->get('flash')->addMessage('errors', 'Страница уже существует');
        $params = [
            'id' => $id,
            'errors' => $errors
        ];
        //$errors[] = 'Страница уже существует';
        //return $response->withRedirect($router->urlFor('urls.show'));
        //return $this->get('renderer')->render($response->withStatus(302), $router->urlFor('urls.show'), $params);
        return $response->withRedirect($router->urlFor('urls.show', $params));
    }
    if (count($errors) === 0) {
        $CreatedDT = date("Y-m-d H:i:s");
        $url = Url::fromArray([$urlData['name'], $CreatedDT]);
        $id = $urlRepository->save($url);
        $this->get('flash')->addMessage('success', 'Веб-страница успешно добавлена');
        $params = [
            'id' => $id
        ];
        return $response->withRedirect($router->urlFor('urls.show', $params));
    }
    $params = [
        'url' => $urlData,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), $router->urlFor('index'), $params);
})->setName('urls.store');


$app->run();
