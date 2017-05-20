<?php
namespace Slince\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Routing\Exception\MethodNotAllowedException;
use Slince\Routing\Matcher;
use Slince\Routing\RouteCollection;
use Slince\Routing\Route;
use Zend\Diactoros\ServerRequest;

class MatcherTest extends TestCase
{
    public function testSimpleMatch()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo', 'action');
        $routes->add($route);
        $matcher = new Matcher($routes);
        $this->assertTrue ($route === $matcher->match('/foo'));
    }

    public function testMethodNotAllowed()
    {
        $routes = new RouteCollection();
        $routes->post('/foo', 'foo');
        $matcher = new Matcher($routes);
        try {
            $matcher->match(new ServerRequest([], [], '/foo'));
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(array('POST'), $e->getAllowedMethods());
        }
    }

    public function testHeadAllowedWhenRequirementContainsGet()
    {
        $routes = new RouteCollection();
        $route = $routes->get('/foo', 'foo');
        $matcher = new Matcher($routes);
        $this->assertTrue ($route === $matcher->match(new ServerRequest(['REQUEST'])));
    }
    public function testMethodNotAllowedAggregatesAllowedMethods()
    {
        $routes = new RouteCollection();
        $routes->add('foo1', new Route('/foo', array(), array(), array(), '', array(), array('post')));
        $routes->add('foo2', new Route('/foo', array(), array(), array(), '', array(), array('put', 'delete')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        try {
            $matcher->match('/foo');
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(array('POST', 'PUT', 'DELETE'), $e->getAllowedMethods());
        }
    }
    public function testMatch()
    {
        // test the patterns are matched and parameters are returned
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/foo/{bar}'));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        try {
            $matcher->match('/no-match');
            $this->fail();
        } catch (ResourceNotFoundException $e) {
        }
        $this->assertEquals(array('_route' => 'foo', 'bar' => 'baz'), $matcher->match('/foo/baz'));
        // test that defaults are merged
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/foo/{bar}', array('def' => 'test')));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'foo', 'bar' => 'baz', 'def' => 'test'), $matcher->match('/foo/baz'));
        // test that route "method" is ignored if no method is given in the context
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/foo', array(), array(), array(), '', array(), array('get', 'head')));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertInternalType('array', $matcher->match('/foo'));
        // route does not match with POST method context
        $matcher = new UrlMatcher($routesection, new RequestContext('', 'post'));
        try {
            $matcher->match('/foo');
            $this->fail();
        } catch (MethodNotAllowedException $e) {
        }
        // route does match with GET or HEAD method context
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertInternalType('array', $matcher->match('/foo'));
        $matcher = new UrlMatcher($routesection, new RequestContext('', 'head'));
        $this->assertInternalType('array', $matcher->match('/foo'));
        // route with an optional variable as the first segment
        $routesection = new RouteCollection();
        $routesection->add('bar', new Route('/{bar}/foo', array('bar' => 'bar'), array('bar' => 'foo|bar')));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'bar', 'bar' => 'bar'), $matcher->match('/bar/foo'));
        $this->assertEquals(array('_route' => 'bar', 'bar' => 'foo'), $matcher->match('/foo/foo'));
        $routesection = new RouteCollection();
        $routesection->add('bar', new Route('/{bar}', array('bar' => 'bar'), array('bar' => 'foo|bar')));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'bar', 'bar' => 'foo'), $matcher->match('/foo'));
        $this->assertEquals(array('_route' => 'bar', 'bar' => 'bar'), $matcher->match('/'));
        // route with only optional variables
        $routesection = new RouteCollection();
        $routesection->add('bar', new Route('/{foo}/{bar}', array('foo' => 'foo', 'bar' => 'bar'), array()));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'bar', 'foo' => 'foo', 'bar' => 'bar'), $matcher->match('/'));
        $this->assertEquals(array('_route' => 'bar', 'foo' => 'a', 'bar' => 'bar'), $matcher->match('/a'));
        $this->assertEquals(array('_route' => 'bar', 'foo' => 'a', 'bar' => 'b'), $matcher->match('/a/b'));
    }
    public function testMatchWithPrefixes()
    {
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/{foo}'));
        $routesection->addPrefix('/b');
        $routesection->addPrefix('/a');
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'foo', 'foo' => 'foo'), $matcher->match('/a/b/foo'));
    }
    public function testMatchWithDynamicPrefix()
    {
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/{foo}'));
        $routesection->addPrefix('/b');
        $routesection->addPrefix('/{_locale}');
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_locale' => 'fr', '_route' => 'foo', 'foo' => 'foo'), $matcher->match('/fr/b/foo'));
    }
    public function testMatchSpecialRouteName()
    {
        $routesection = new RouteCollection();
        $routesection->add('$péß^a|', new Route('/bar'));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => '$péß^a|'), $matcher->match('/bar'));
    }
    public function testMatchNonAlpha()
    {
        $routesection = new RouteCollection();
        $chars = '!"$%éà &\'()*+,./:;<=>@ABCDEFGHIJKLMNOPQRSTUVWXYZ\\[]^_`abcdefghijklmnopqrstuvwxyz{|}~-';
        $routesection->add('foo', new Route('/{foo}/bar', array(), array('foo' => '['.preg_quote($chars).']+'), array('utf8' => true)));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'foo', 'foo' => $chars), $matcher->match('/'.rawurlencode($chars).'/bar'));
        $this->assertEquals(array('_route' => 'foo', 'foo' => $chars), $matcher->match('/'.strtr($chars, array('%' => '%25')).'/bar'));
    }
    public function testMatchWithDotMetacharacterInRequirements()
    {
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/{foo}/bar', array(), array('foo' => '.+')));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'foo', 'foo' => "\n"), $matcher->match('/'.urlencode("\n").'/bar'), 'linefeed character is matched');
    }
    public function testMatchOverriddenRoute()
    {
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/foo'));
        $routesection1 = new RouteCollection();
        $routesection1->add('foo', new Route('/foo1'));
        $routesection->addCollection($routesection1);
        $matcher = new UrlMatcher($routesection, new RequestContext());
        $this->assertEquals(array('_route' => 'foo'), $matcher->match('/foo1'));
        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $this->assertEquals(array(), $matcher->match('/foo'));
    }
    public function testMatchRegression()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/foo/{foo}'));
        $routes->add('bar', new Route('/foo/bar/{foo}'));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('foo' => 'bar', '_route' => 'bar'), $matcher->match('/foo/bar/bar'));
        $routesection = new RouteCollection();
        $routesection->add('foo', new Route('/{bar}'));
        $matcher = new UrlMatcher($routesection, new RequestContext());
        try {
            $matcher->match('/');
            $this->fail();
        } catch (ResourceNotFoundException $e) {
        }
    }
    public function testDefaultRequirementForOptionalVariables()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/{page}.{_format}', array('page' => 'index', '_format' => 'html')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('page' => 'my-page', '_format' => 'xml', '_route' => 'test'), $matcher->match('/my-page.xml'));
    }
    public function testMatchingIsEager()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/{foo}-{bar}-', array(), array('foo' => '.+', 'bar' => '.+')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('foo' => 'text1-text2-text3', 'bar' => 'text4', '_route' => 'test'), $matcher->match('/text1-text2-text3-text4-'));
    }
    public function testAdjacentVariables()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/{w}{x}{y}{z}.{_format}', array('z' => 'default-z', '_format' => 'html'), array('y' => 'y|Y')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        // 'w' eagerly matches as much as possible and the other variables match the remaining chars.
        // This also shows that the variables w-z must all exclude the separating char (the dot '.' in this case) by default requirement.
        // Otherwise they would also consume '.xml' and _format would never match as it's an optional variable.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'Y', 'z' => 'Z', '_format' => 'xml', '_route' => 'test'), $matcher->match('/wwwwwxYZ.xml'));
        // As 'y' has custom requirement and can only be of value 'y|Y', it will leave  'ZZZ' to variable z.
        // So with carefully chosen requirements adjacent variables, can be useful.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'y', 'z' => 'ZZZ', '_format' => 'html', '_route' => 'test'), $matcher->match('/wwwwwxyZZZ'));
        // z and _format are optional.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'y', 'z' => 'default-z', '_format' => 'html', '_route' => 'test'), $matcher->match('/wwwwwxy'));
        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $matcher->match('/wxy.html');
    }
    public function testOptionalVariableWithNoRealSeparator()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/get{what}', array('what' => 'All')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('what' => 'All', '_route' => 'test'), $matcher->match('/get'));
        $this->assertEquals(array('what' => 'Sites', '_route' => 'test'), $matcher->match('/getSites'));
        // Usually the character in front of an optional parameter can be left out, e.g. with pattern '/get/{what}' just '/get' would match.
        // But here the 't' in 'get' is not a separating character, so it makes no sense to match without it.
        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $matcher->match('/ge');
    }
    public function testRequiredVariableWithNoRealSeparator()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/get{what}Suffix'));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('what' => 'Sites', '_route' => 'test'), $matcher->match('/getSitesSuffix'));
    }
    public function testDefaultRequirementOfVariable()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/{page}.{_format}'));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('page' => 'index', '_format' => 'mobile.html', '_route' => 'test'), $matcher->match('/index.mobile.html'));
    }
    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testDefaultRequirementOfVariableDisallowsSlash()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/{page}.{_format}'));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $matcher->match('/index.sl/ash');
    }
    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testDefaultRequirementOfVariableDisallowsNextSeparator()
    {
        $routes = new RouteCollection();
        $routes->add('test', new Route('/{page}.{_format}', array(), array('_format' => 'html|xml')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $matcher->match('/do.t.html');
    }
    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testSchemeRequirement()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/foo', array(), array(), array(), '', array('https')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $matcher->match('/foo');
    }
    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testCondition()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo');
        $route->setCondition('context.getMethod() == "POST"');
        $routes->add('foo', $route);
        $matcher = new UrlMatcher($routes, new RequestContext());
        $matcher->match('/foo');
    }
    public function testDecodeOnce()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/foo/{foo}'));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('foo' => 'bar%23', '_route' => 'foo'), $matcher->match('/foo/bar%2523'));
    }
    public function testCannotRelyOnPrefix()
    {
        $routes = new RouteCollection();
        $subColl = new RouteCollection();
        $subColl->add('bar', new Route('/bar'));
        $subColl->addPrefix('/prefix');
        // overwrite the pattern, so the prefix is not valid anymore for this route in the collection
        $subColl->get('bar')->setPath('/new');
        $routes->addCollection($subColl);
        $matcher = new UrlMatcher($routes, new RequestContext());
        $this->assertEquals(array('_route' => 'bar'), $matcher->match('/new'));
    }
    public function testWithHost()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/foo/{foo}', array(), array(), array(), '{locale}.example.com'));
        $matcher = new UrlMatcher($routes, new RequestContext('', 'GET', 'en.example.com'));
        $this->assertEquals(array('foo' => 'bar', '_route' => 'foo', 'locale' => 'en'), $matcher->match('/foo/bar'));
    }
    public function testWithHostOnRouteCollection()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/foo/{foo}'));
        $routes->add('bar', new Route('/bar/{foo}', array(), array(), array(), '{locale}.example.net'));
        $routes->setHost('{locale}.example.com');
        $matcher = new UrlMatcher($routes, new RequestContext('', 'GET', 'en.example.com'));
        $this->assertEquals(array('foo' => 'bar', '_route' => 'foo', 'locale' => 'en'), $matcher->match('/foo/bar'));
        $matcher = new UrlMatcher($routes, new RequestContext('', 'GET', 'en.example.com'));
        $this->assertEquals(array('foo' => 'bar', '_route' => 'bar', 'locale' => 'en'), $matcher->match('/bar/bar'));
    }
    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testWithOutHostHostDoesNotMatch()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/foo/{foo}', array(), array(), array(), '{locale}.example.com'));
        $matcher = new UrlMatcher($routes, new RequestContext('', 'GET', 'example.com'));
        $matcher->match('/foo/bar');
    }
    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testPathIsCaseSensitive()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/locale', array(), array('locale' => 'EN|FR|DE')));
        $matcher = new UrlMatcher($routes, new RequestContext());
        $matcher->match('/en');
    }
    public function testHostIsCaseInsensitive()
    {
        $routes = new RouteCollection();
        $routes->add('foo', new Route('/', array(), array('locale' => 'EN|FR|DE'), array(), '{locale}.example.com'));
        $matcher = new UrlMatcher($routes, new RequestContext('', 'GET', 'en.example.com'));
        $this->assertEquals(array('_route' => 'foo', 'locale' => 'en'), $matcher->match('/'));
    }
}