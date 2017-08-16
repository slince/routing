# Routing Component

[![Build Status](https://img.shields.io/travis/slince/routing/master.svg?style=flat-square)](https://travis-ci.org/slince/routing)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/routing.svg?style=flat-square)](https://codecov.io/github/slince/routing)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/routing.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/routing)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/slince/routing.svg?style=flat-square)](https://scrutinizer-ci.com/g/slince/routing/?branch=master)

A flexible web routing component.

## Installation

Install via composer

```bash
composer require slince/routing
```

## Quick example

```php
$routes = new Slince\Routing\RouteCollection();
$routes->get('/products', 'Products::index')->setName('product_index');

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(); //Creates the psr7 request instance

$matcher = new Slince\Routing\Matcher($routes);
$generator = new Slince\Routing\Generator($request);

$route = $matcher->matchRequest($request); //Matches the current request
var_dump($route->getComputedParamters()); //Dumps route computed paramters
echo $generator->generate($route); //Generates path 

$route = $routes->getByAction('Products::index');
echo $generator->generate($route); //Generates path 

$route = $routes->getByName('product_index');
echo $generator->generate($route); //Generates path 
```

## Usage

### Defines routes

#### Creates an instance of `Slince\Routing\RouteCollection` first,

```php
$routes = new Slince\Routing\RouteCollection();
$route = new Slince\Routing\Route('/products/{id}', 'Products::view');
$routes->add($route);
```
The route path contain the placeholder `{id}` which matches everything except "/" and "."
You can set custom requirements with `setRequirement`  method or `setRequirements` method.

```php
$route->setRequirements([
    'id' => '\d+'
]);
```

Routing supports optional placeholder, you can provide a default value for the placeholder.

```php
$route->setDefaults([
    'id' => 1
]);
```
The route can match `/products` and `/products/1`.

#### Shorthands for HTTP methods are also provided.

```php
$routes = new RouteCollection();

$routes->get('/pattern', 'action');
$routes->post('/pattern', 'action');
$routes->put('/pattern', 'action');
$routes->delete('/pattern', 'action');
$routes->options('/pattern', 'action');
$routes->patch('/pattern', 'action');
```

### Customize HTTP verb 

```php
$route->setMethods(['GET', 'POST', 'PUT']);
```

### Host matching

You can limit a route to specified host with `setHost` method.

```php
$routes->create('/products', 'Products::index')
    ->setHost('product.domain.com');
```

The route will only match the request with `product.domain.com` domain

### Force route use HTTPS or HTTP

Routing also allow you to define routes using `http` and `https`.

```php
$routes = new Slince\Routing\RouteCollection();

$routes->https('/pattern', 'action');
$routes->http('/pattern', 'action');
```
Or customize this.

```php
$route->setSchemes(['http', 'https']);
```

### Match a path or psr7 request.

```php
$routes = new Slince\Routing\RouteCollection();
$routes->create('/products/{id}.{_format}', 'Products::view');
$matcher = new Slince\Routing\Matcher($routes);

try {
    $route = $matcher->match('/products/10.html');
    
    print_r($route->getComputedParameters())// ['id' => 10, '_format' => 'html']
    
} catch (Slince\Routing\Exception\RouteNotFoundException $e) {
    //404
}
```
Matcher will return the matching route. If no matching route can be found, matcher will throw a `RouteNotFoundException`.

Match a ` Psr\Http\Message\ServerRequestInterface`.

```php
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
try {
    $route = $matcher->matchRequest($request);
} catch (Slince\Routing\Exception\MethodNotAllowedException $e) {
    //403
    var_dump($e->getAllowedMethods());
} catch (Slince\Routing\Exception\RouteNotFoundException $e) {
    //404
}
```

### Generate path for a route

```php
$generator = new Slince\Routing\Generator();

$route = new Slince\Routing\Route('/foo/{id}', 'action');
echo $generator->generate($route, ['id' => 10]); //will output "/foo/10"
```

If you want generate the absolute url for the route, you need to provide generator with a request as request context.

```php
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$generator->setRequest($request);

echo $generator->generate($route, ['id' => 10], true); //will output "{scheme}://{domain}/foo/10"
```

## License
 
The MIT license. See [MIT](https://opensource.org/licenses/MIT)