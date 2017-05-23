## Routing Component

[![Build Status](https://img.shields.io/travis/slince/routing/master.svg?style=flat-square)](https://travis-ci.org/slince/routing)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/routing.svg?style=flat-square)](https://codecov.io/github/slince/routing)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/routing.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/routing)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/slince/routing.svg?style=flat-square)](https://scrutinizer-ci.com/g/slince/routing/?branch=master)

A flexible web routing component.

### Installation

Install via composer

```bash
composer require slince/routing
```

### Usage

#### Creates route

```
$routes = new RouteCollection();
$routes->create('/home', 'Pages::home')
    ->name('homepage'):
```






