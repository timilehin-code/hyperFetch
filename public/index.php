<?php
require __DIR__ . "/../vendor/autoload.php";

use Bramus\Router\Router;

$router = new Router();


$router->get("/", App\Controllers\HomeController@index);

$router->run();