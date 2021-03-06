<?php
namespace Slince\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slince\Routing\Exception\InvalidArgumentException;
use Slince\Routing\Generator;
use Slince\Routing\Route;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

class GeneratorTest extends TestCase
{
    public function testRequest()
    {
        $generator = new Generator();
        $this->assertNull($generator->getRequest());
        $request = ServerRequestFactory::fromGlobals();
        $generator->setRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $generator->getRequest());
    }

    public function testAbsoluteUrlWithPort80()
    {
        $request = new ServerRequest([], [], 'http://localhost/foo-bar');
        $route = new Route('/foo', 'action');
        $generator = new Generator($request);
        $this->assertEquals('http://localhost/foo', $generator->generate($route, [], true));
    }

    public function testAbsoluteUrlWithoutRequest()
    {
        $route = new Route('/foo', 'action');
        $generator = new Generator();
        $this->expectException(InvalidArgumentException::class);
        $generator->generate($route, [], true);
    }

    public function testAbsoluteUrlWithRouteDefines()
    {
        $route = new Route('/foo', 'action');
        $route->setSchemes('https')->setHost('foo.domain.com');
        $request = new ServerRequest([], [], 'http://localhost/foo-bar');
        $generator = new Generator($request);
        $this->assertEquals('https://foo.domain.com/foo', $generator->generate($route, [], true));
    }

    public function testAbsoluteSecureUrlWithPort443()
    {
        $request = new ServerRequest([], [], 'https://localhost/foo-bar');
        $route = new Route('/foo', 'action');
        $generator = new Generator($request);
        $this->assertEquals('https://localhost/foo', $generator->generate($route, [], true));
    }

    public function testAbsoluteUrlWithNonStandardPort()
    {
        $request = new ServerRequest([], [], 'http://localhost:8080/foo-bar');
        $route = new Route('/foo', 'action');
        $generator = new Generator($request);
        $this->assertEquals('http://localhost:8080/foo', $generator->generate($route, [], true));
    }

    public function testAbsoluteSecureUrlWithNonStandardPort()
    {
        $request = new ServerRequest([], [], 'https://localhost:8080/foo-bar');
        $route = new Route('/foo', 'action');
        $generator = new Generator($request);
        $this->assertEquals('https://localhost:8080/foo', $generator->generate($route, [], true));
    }

    public function testRelativeUrlWithoutParameters()
    {
        $route = new Route('/foo', 'action');
        $generator = new Generator();
        $this->assertEquals('/foo', $generator->generate($route, []));
    }

    public function testRelativeUrlWithParameter()
    {
        $route = new Route('/foo/{bar}', 'action');
        $generator = new Generator();
        $this->assertEquals('/foo/hello', $generator->generate($route, ['bar' => 'hello']));
    }

    public function testRelativeUrlWithNullParameter()
    {
        $route = new Route('/foo.{format}', 'action');
        $route->setDefault('format', null);
        $generator = new Generator();
        $this->assertEquals('/foo', $generator->generate($route));
    }

    public function testNotPassedOptionalParameterInBetween()
    {
        $route = new Route('/foo/{page}', 'action');
        $route->setDefault('page', 1);
        $generator = new Generator();
        $this->assertEquals('/foo', $generator->generate($route));
        $this->assertEquals('/foo', $generator->generate($route, ['page' => 1]));
        $this->assertEquals('/foo/0', $generator->generate($route, ['page' => 0]));
    }

    public function testGenerateUrlWithExtraParameters()
    {
        $route = new Route('/foo/{page}', 'action');
        $route->setDefault('page', 1);
        $generator = new Generator();
        $this->assertEquals('/foo/2?foo=bar&bar=baz', $generator->generate($route, [
            'page' => 2,
            'foo' => 'bar',
            'bar' => 'baz'
        ]));
        $this->assertEquals('/foo', $generator->generate($route, [
            'page' => 1,
            'foo' => null,
        ]));
    }

    public function testCustomHasHigherPriorityThanDefault()
    {
        $route = new Route('/foo/{page}', 'action');
        $route->setDefault('page', 1);
        $generator = new Generator();
        $this->assertEquals('/foo/2', $generator->generate($route, [
            'page' => 2,
        ]));
    }

    public function testGenerateForRouteWithInvalidParameter()
    {
        $route = new Route('/foo/{page}', 'action');
        $route->setRequirement('page', '\d+');
        $generator = new Generator();
        $this->expectException(InvalidArgumentException::class);
        $generator->generate($route, [
            'page' => 'non_number',
        ]);
    }

    public function testRequiredParamAndEmptyPassed()
    {
        $this->expectException(InvalidArgumentException::class);
        $route = new Route('/foo/{page}', 'action');
        $route->setRequirement('page', '\d+');
        $generator = new Generator();
        $this->expectException(InvalidArgumentException::class);
        $generator->generate($route, [
            'page' => ''
        ]);
    }

    public function testSchemeRequirementForcesAbsoluteUrl()
    {
        $route = new Route('/foo', 'action');
        $route->setSchemes(['https']);
        $request = new ServerRequest([], [], 'http://localhost/foo-bar');
        $generator = new Generator($request);
        $this->assertEquals('https://localhost/foo', $generator->generate($route, [], true));
    }

    public function testSchemeRequirementCreatesUrlFoCurrentRequiredScheme()
    {
        $route = new Route('/foo', 'action');
        $route->setSchemes(['https', 'http']);
        $request = new ServerRequest([], [], 'http://localhost/foo-bar');
        $generator = new Generator($request);
        $this->assertEquals('http://localhost/foo', $generator->generate($route, [], true));
    }

    public function testUrlWithoutRequiredParameters()
    {
        $route = new Route('/foo/{bar}', 'action');
        $generator = new Generator();
        $this->expectException(InvalidArgumentException::class);
        $generator->generate($route, []);
    }

    public function testUrlWitNullRequiredParameters()
    {
        $route = new Route('/foo/{bar}', 'action');
        $generator = new Generator();
        $this->expectException(InvalidArgumentException::class);
        $generator->generate($route, ['bar' => null]);
    }

    public function testUrlWithInvalidDefaults()
    {
        $route = new Route('/foo/{bar}', 'action');
        $route->setRequirement('bar', '\d+')
            ->setDefault('bar',  'hello');
        $generator = new Generator();
        $this->expectException(InvalidArgumentException::class);
        echo $generator->generate($route);
    }
}