<?php
include __DIR__ . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Slince\Routing\Exception\MethodNotAllowedException;
use Slince\Routing\Exception\RouteNotFoundException;
use Slince\Routing\Matcher;
use Slince\Routing\RouteCollection;
use Slince\Routing\Route;
use Zend\Diactoros\ServerRequest;

$routes = new \Slince\Routing\RouteCollection();

$routes = new RouteCollection();
$route = $routes->create('/{w}{x}{y}{z}.{_format}', 'action')
    ->setDefaults(['z' => 'default-z', '_format' => 'html'])
    ->setRequirements(['y' => 'y|Y']);

$matcher = new Matcher($routes);
echo $regex = $route->compile()->getPathRegex();
var_dump(preg_match($regex, '/text1-text2-text3-text4-'));
