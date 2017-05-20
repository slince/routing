<?php
namespace Slince\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Routing\Exception\MethodNotAllowedException;
use Slince\Routing\Exception\RouteNotFoundException;
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

    public function testMatch()
    {
        // test the patterns are matched and parameters are returned
        $routes = new RouteCollection();
        $route = $routes->http('/foo/{bar}', 'action1');
        $matcher = new Matcher($routes);
        try {
            $matcher->match('/no-match');
            $this->fail();
        } catch (RouteNotFoundException $e) {
        }
        $this->assertTrue ($route === $matcher->match('/foo/baz'));
        $this->assertEquals(['foo' => 'baz'], $route->getComputedParameters());

        // test defaults
        $routes = new RouteCollection();
        $routes->http('/foo/{bar}', 'action1')
            ->setDefaults(['def' => 'test']);
        $matcher = new Matcher($routes);
        $route = $matcher->match('/foo/baz');
        $this->assertEquals(['bar' => 'baz', 'def' => 'test'], $route->getComputedParameters());

        // test that route "method" is ignored if match simple path
        $routes = new RouteCollection();
        $route = $routes->http('/foo', 'action1')->setMethods(['GET', 'HEAD']);
        $matcher = new Matcher($routes);
        $this->assertTrue ($route === $matcher->match('/foo'));

        //optional placeholder
        $routes = new RouteCollection();
        $route = $routes->http('/foo/{bar}', 'action1')
            ->setDefaults(['bar' => 'baz']);
        $this->assertTrue ($route === $matcher->match('/foo'));
    }

    public function testHeadAllowedWhenMethodGet()
    {
        $routes = new RouteCollection();
        $route = $routes->get('/foo', 'foo');
        $matcher = new Matcher($routes);
        $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'HEAD'], [], 'http://domain.com/foo');
        $this->assertTrue ($route === $matcher->matchRequest($request));
    }

    public function testMethodNotAllowedMethods()
    {
        $routes = new RouteCollection();
        $routes->post('/foo', 'action1');
        $routes->http('/foo', 'action2')->setMethods(['put', 'delete']);
        $matcher = new Matcher($routes);
        try {
            $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'HEAD'], [], 'http://domain.com/foo');
            $matcher->matchRequest($request);
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(array('POST', 'PUT', 'DELETE'), $e->getAllowedMethods());
        }
    }


    public function testMatchWithPrefixes()
    {
        $routes = new RouteCollection();
        $routes->group('foo', function(RouteCollection $routes){
            $routes->get('/bar', 'action');
        });
        $matcher = new Matcher($routes);
        $this->assertTrue($matcher->match('/foo/bar') === $routes->getByAction('action'));
    }

    public function testMatchWithDynamicPrefix()
    {
        $routes = new RouteCollection();
        $routes->group('{locale}', function(RouteCollection $routes){
            $routes->get('/bar', 'action');
        });
        $matcher = new Matcher($routes);
        $this->assertEquals(['locale' => 'en'], $matcher->match('/en/foo')->getComputedParameters());
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