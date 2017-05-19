## Routing Component

### 要求
- PHP 5.4

### 安装
需要使用composer，如果您没有安装composer，请参考[composer](https://getcomposer.org)，中文网站[http://www.phpcomposer.com/](http://www.phpcomposer.com/)
```
composer require slince/routing *@dev
```

### 使用
```
use Slince\Routing\RouterFactory;
use Slince\Routing\RequestContext;
use Slince\Routing\RouteBuilder;
use Slince\Routing\Exception\MethodNotAllowedException;
use Slince\Routing\Exception\RouteNotFoundException;


$routes = new RouteCollection();
$routes->create('/home', 'Pages::home')
    ->name('homepage'):
    
$routes->group(['prefix'=>'/admin'], function(){
    
});
```






