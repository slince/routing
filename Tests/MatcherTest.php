<?php
namespace Slince\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Routing\Exception\MethodNotAllowedException;
use Slince\Routing\Exception\RouteNotFoundException;
use Slince\Routing\Matcher;
use Slince\Routing\RouteCollection;
use Slince\Routing\Route;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

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
        $route = $routes->create('/foo/{bar}', 'action1');
        $matcher = new Matcher($routes);
        try {
            $matcher->match('/no-match');
            $this->fail();
        } catch (RouteNotFoundException $e) {
        }
        $this->assertTrue ($route === $matcher->match('/foo/baz'));
        $this->assertEquals(['bar' => 'baz'], $route->getComputedParameters());

        // test defaults
        $routes = new RouteCollection();
        $routes->create('/foo/{bar}', 'action1')
            ->setDefaults(['def' => 'test']);
        $matcher = new Matcher($routes);
        $route = $matcher->match('/foo/baz');
        $this->assertEquals(['bar' => 'baz', 'def' => 'test'], $route->getComputedParameters());

        // test that route "method" is ignored if match simple path
        $routes = new RouteCollection();
        $route = $routes->create('/foo', 'action1')->setMethods(['GET', 'HEAD']);
        $matcher = new Matcher($routes);
        $this->assertTrue ($route === $matcher->match('/foo'));

        //optional placeholder
        $routes = new RouteCollection();
        $route = $routes->create('/foo/{bar}', 'action1')
            ->setDefaults(['bar' => 'baz']);
        $matcher = new Matcher($routes);
        $this->assertTrue ($route === $matcher->match('/foo'));
    }

    public function testHeadAllowedWhenMethodGet()
    {
        $routes = new RouteCollection();
        $route = $routes->get('/foo', 'foo');
        $matcher = new Matcher($routes);
        $request = new ServerRequest([], [], 'http://domain.com/foo', 'HEAD');
        $this->assertTrue ($route === $matcher->matchRequest($request));
    }

    public function testMethodNotAllowedMethods()
    {
        $routes = new RouteCollection();
        $routes->post('/foo', 'action1');
        $routes->create('/foo', 'action2')->setMethods(['put', 'delete']);
        $matcher = new Matcher($routes);
        try {
            $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'HEAD'], [], 'http://domain.com/foo');
            $matcher->matchRequest($request);
            $this->fail();
        } catch (MethodNotAllowedException $e) {
            print_r($e->getAllowedMethods());exit;
            $this->assertEquals(['POST', 'PUT', 'DELETE'], $e->getAllowedMethods());
        }
    }

    public function testMatchWithPrefix()
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
            $routes->get('/{foo}', 'action');
        });
        $matcher = new Matcher($routes);
        $this->assertEquals(['locale' => 'en', 'foo' => 'bar'], $matcher->match('/en/bar')->getComputedParameters());
    }

    public function testMatchSpecialRouteName()
    {
        $routes = new RouteCollection();
        $routes->create('/foo', 'action1')->setName('$péß^a|');
        $matcher = new Matcher($routes);
        $this->assertEquals('$péß^a|', $matcher->match('/foo')->getName());
    }

    public function testMatchNonAlpha()
    {
        $routes = new RouteCollection();
        $chars = '!"$%éà &\'()*+,./:;<=>@ABCDEFGHIJKLMNOPQRSTUVWXYZ\\[]^_`abcdefghijklmnopqrstuvwxyz{|}~-';
        $routes->create('/{foo}/bar', 'action')
            ->setRequirements([
                'foo' => '['.preg_quote($chars).']+'
            ]);
        $matcher = new Matcher($routes);
        $this->assertEquals(['foo' => $chars], $matcher->match('/'.rawurlencode($chars).'/bar')->getComputedParameters());
        $this->assertEquals(['foo' => $chars], $matcher->match('/'.strtr($chars, array('%' => '%25')).'/bar')->getComputedParameters());
    }


    public function testMatchRegression()
    {
        $routes = new RouteCollection();
        $routes->create('/foo/{foo}', 'action');
        $routes->create('/foo/bar/{foo}', 'action2');

        $matcher = new Matcher($routes);
        $this->assertEquals(['foo' => 'bar'], $matcher->match('/foo/bar/bar')->getComputedParameters());

        $routes = new RouteCollection();
        $routes->create('/{bar}', 'action');
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $matcher->match('/');
    }

    public function testDefaultRequirementForOptionalVariables()
    {
        $routes = new RouteCollection();
        $routes->create('/{page}.{_format}', 'action')->setDefaults([
            '_format' => 'html'
        ]);
        $matcher = new Matcher($routes);
        $this->assertEquals(['page' => 'my-page', '_format' => 'xml'], $matcher->match('/personal-page.xml')
            ->getComputedParameters());
    }

    public function testMatchingIsEager()
    {
        $routes = new RouteCollection();
        $routes->create('/{foo}-{bar}-', 'action')
            ->setRequirements([
                'foo' => '.+',
                'bar' => '.+'
            ]);
        $matcher = new Matcher($routes);
        $this->assertEquals(['foo' => 'text1-text2-text3', 'bar' => 'text4'],
            $matcher->match('/text1-text2-text3-text4-')->getComputedParameters());
    }

    public function testAdjacentVariables()
    {
        $routes = new RouteCollection();
        $route = $routes->create('/{w}{x}{y}{z}.{_format}', 'action')
            ->setDefaults(['z' => 'default-z', '_format' => 'html'])
            ->setRequirements(['y' => 'y|Y']);

        $matcher = new Matcher($routes);

        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'Y', 'z' => 'Z', '_format' => 'xml'),
            $matcher->match('/wwwwwxYZ.xml')->getComputedParameters());

        // As 'y' has custom requirement and can only be of value 'y|Y', it will leave  'ZZZ' to variable z.
        // So with carefully chosen requirements adjacent variables, can be useful.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'y', 'z' => 'ZZZ', '_format' => 'html'),
            $matcher->match('/wwwwwxyZZZ')->getComputedParameters());

        // z and _format are optional.
        $this->assertEquals(array('w' => 'wwwww', 'x' => 'x', 'y' => 'y', 'z' => 'default-z', '_format' => 'html'),
            $matcher->match('/wwwwwxy')->getComputedParameters());
        $this->expectException(RouteNotFoundException::class);
        $matcher->match('/wxy.html');
    }

    public function testOptionalVariableWithNoRealSeparator()
    {
        $routes = new RouteCollection();
        $routes->create('/get{what}', 'action')
            ->setDefaults(['what' => 'All']);

        $matcher = new Matcher($routes);

        $this->assertEquals(['what' => 'All'], $matcher->match('/get')->getComputedParameters());
        $this->assertEquals(['what' => 'Sites'], $matcher->match('/getSites')->getComputedParameters());
        $this->expectException(RouteNotFoundException::class);
        $matcher->match('/ge');
    }

    public function testRequiredVariableWithNoRealSeparator()
    {
        $routes = new RouteCollection();
        $routes->create('/get{what}Suffix', 'action');
        $matcher = new Matcher($routes);
        $this->assertEquals(['what' => 'Sites'], $matcher->match('/getSitesSuffix')->getComputedParameters());
    }

    public function testDefaultRequirementOfVariable()
    {
        $routes = new RouteCollection();
        $route = $routes->create('/{page}.{_format}', 'action')
            ->setRequirement('_format', '[\w\.]+');


//                echo $regex = $route->compile()->getPathRegex();
//        var_dump(preg_match($regex, '/index.mobile.html'));
//exit;

        $matcher = new Matcher($routes);
        $this->assertEquals(['page' => 'index', '_format' => 'mobile.html'],
            $matcher->match('/index.mobile.html')->getComputedParameters());
    }

    public function testDefaultRequirementOfVariableDisallowsSlash()
    {
        $routes = new RouteCollection();
        $routes->create('/{page}.{_format}', 'action');
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $matcher->match('/index.sl/ash');
    }

    public function testDefaultRequirementOfVariableDisallowsNextSeparator()
    {
        $routes = new RouteCollection();
        $routes->create('/{page}.{_format}', 'action')
            ->setDefaults(['_format' => 'html|xml']);
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $matcher->match('/do.t.html');
    }

    public function testSchemeRequirement()
    {
        $routes = new RouteCollection();
        $routes->https('/foo', 'action')
            ->setDefaults(['_format' => 'html|xml']);
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $request = new ServerRequest([], [], 'http://www.domain.com/foo');
        $matcher->matchRequest($request);
    }

    public function testCondition()
    {
        $routes = new RouteCollection();
        $routes->post('/foo', 'action')
            ->setDefaults(['_format' => 'html|xml']);
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'post'], [], 'http://www.domain.com/foo');
        $matcher->matchRequest($request);
    }

    public function testDecodeOnce()
    {
        $routes = new RouteCollection();
        $routes->create('/foo/{foo}', 'action');
        $matcher = new Matcher($routes);
        $this->assertEquals(['foo' => 'bar%23'], $matcher->match('/foo/bar%2523')->getComputedParameters());
    }

    public function testWithHost()
    {
        $routes = new RouteCollection();
        $routes->create('/foo/{foo}', 'action')->setHost('{locale}.example.com');

        $matcher = new Matcher($routes);
        $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'post'], [], 'http:// en.example.com/foo/bar');
        $this->assertEquals(['foo' => 'bar', 'locale' => 'en'], $matcher->matchRequest($request));
    }

    public function testWithOutHostHostDoesNotMatch()
    {
        $routes = new RouteCollection();
        $routes->create('/foo/{foo}', 'action')->setHost('{locale}.example.com');
        $matcher = new Matcher($routes);
        $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'post'], [], 'http://example.com/foo/bar');
        $this->expectException(RouteNotFoundException::class);
        $matcher->matchRequest($request);
    }

    public function testPathIsCaseSensitive()
    {
        $routes = new RouteCollection();
        $routes->create('/locale','action')->setRequirements(['locale' => 'EN|FR|DE']);
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $matcher->match('/en');
    }

    public function testHostIsCaseInsensitive()
    {
        $routes = new RouteCollection();
        $routes->create('/','action')->setRequirements(['locale' => 'EN|FR|DE'])
            ->setHost('{locale}.example.com');
        $matcher = new Matcher($routes);
        $this->expectException(RouteNotFoundException::class);
        $request = new ServerRequest(['HTTP_REQUEST_METHOD' => 'post'], [], 'http://en.example.com/');
        $this->assertEquals(array('locale' => 'en'), $matcher->matchRequest($request)->getComputedParameters());
    }
}