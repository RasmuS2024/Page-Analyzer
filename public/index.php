<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use WPA\Connection;
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
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

try {
    Connection::get()->connect();
    echo 'A connection to the PostgreSQL database sever has been established successfully.';
} catch (\PDOException $e) {
    echo $e->getMessage();
}

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params = [
      'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('index');

$app->post('/urls', function ($request, $response) use ($router) {
    //$carRepository = $this->get(CarRepository::class);
    $urlData = $request->getParsedBodyParam('url');
    $v = new Valitron\Validator($urlData);
    $v->rules([
    	'url' => [['name']],
    	'required' => [['name']],
    ]);
    if($v->validate()) {
    	echo "Yay! We're all good!";
    } else {
    	$errors = $v->errors();
    	var_dump($v->errors());
    }

    if (count($errors) === 0) {
        //$car = Car::fromArray([$carData['make'], $carData['model']]);
        //$carRepository->save($car);
        $this->get('flash')->addMessage('success', 'Web site was added successfully');
        return $response->withRedirect($router->urlFor('index'));
    }
    $params = [
        'url' => $urlData,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), 'cars/new.phtml', $params);
})->setName('urls.store');





/*
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("Hello, tt!!!");
    return $response;
});
*/


/*
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});
*/
$app->run();
