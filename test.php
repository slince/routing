<?php
include __DIR__ . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Slince\Routing\Exception\MethodNotAllowedException;
use Slince\Routing\Exception\RouteNotFoundException;
use Slince\Routing\Matcher;
use Slince\Routing\RouteCollection;
use Slince\Routing\Route;
use Zend\Diactoros\ServerRequest;
//
//$routes = new \Slince\Routing\RouteCollection();
//
//$routes = new RouteCollection();
//$route = $routes->create('/{w}{x}{y}{z}.{_format}', 'action')
//    ->setDefaults(['z' => 'default-z', '_format' => 'html'])
//    ->setRequirements(['y' => 'y|Y']);
//
//$matcher = new Matcher($routes);
//
//print_r($matcher->match('/wxy.xml'));
//
//
//$route = $routes->create('/foo{bar}', 'action')
//    ->setDefaults(['bar' => 'default-bar']);
//
//$matcher = new Matcher($routes);
//
//print_r($matcher->match('/fooall'));


$request = new ServerRequest([], [], 'http://www.domain.com:8088/page/foo/bar');

var_dump($request->getUri()->getHost());
var_dump($request->getUri()->getPort());
var_dump($request->getUri()->getPath());
exit;
