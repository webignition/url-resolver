<?php

namespace webignition\Tests\Url\Resolver;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use QueryPath\Exception as QueryPathException;
use webignition\Tests\Url\Resolver\Factory\HttpFixtureFactory;
use GuzzleHttp\Client as HttpClient;
use webignition\Url\Resolver\Resolver;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        $this->httpClient = new HttpClient([
            'handler' => $handlerStack,
        ]);

        $this->resolver = new Resolver($this->httpClient);
    }

    /**
     * @dataProvider resolveHttpRedirectDataProvider
     *
     * @param array $httpFixtures
     * @param string $url
     * @param string $expectedResolvedUrl
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function testResolveHttpRedirect($httpFixtures, $url, $expectedResolvedUrl)
    {
        $this->setHttpFixtures($httpFixtures);
        $this->assertEquals($expectedResolvedUrl, $this->resolver->resolve($url));
    }

    /**
     * @return array
     */
    public function resolveHttpRedirectDataProvider()
    {
        return [
            'single 301' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/foo']),
                    new Response(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 302' => [
                'httpFixtures' => [
                    new Response(302, ['location' => 'http://example.com/foo']),
                    new Response(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 303' => [
                'httpFixtures' => [
                    new Response(303, ['location' => 'http://example.com/foo']),
                    new Response(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 307' => [
                'httpFixtures' => [
                    new Response(307, ['location' => 'http://example.com/foo']),
                    new Response(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 308' => [
                'httpFixtures' => [
                    new Response(308, ['location' => 'http://example.com/foo']),
                    new Response(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'multiple 301' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/foo']),
                    new Response(301, ['location' => 'http://example.com/bar']),
                    new Response(301, ['location' => 'http://example.com/foobar']),
                    new Response(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foobar',
            ],
            'too many redirects' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/foo']),
                    new Response(301, ['location' => 'http://example.com/bar']),
                    new Response(301, ['location' => 'http://example.com/foobar']),
                    new Response(301, ['location' => 'http://example.com/foo']),
                    new Response(301, ['location' => 'http://example.com/bar']),
                    new Response(301, ['location' => 'http://example.com/foobar']),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/bar',
            ],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param array $httpFixtures
     * @param string $url
     * @param string $expectedResolvedUrl
     *
     * @throws QueryPathException
     * @throws GuzzleException
     */
    public function testResolve($httpFixtures, $url, $expectedResolvedUrl)
    {
        $this->setHttpFixtures($httpFixtures);
        $this->assertEquals($expectedResolvedUrl, $this->resolver->resolve($url));
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        $successResponse = new Response();
        $notFoundResponse = new Response(404);

        return [
            'no redirects' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            '404' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            '404 initially, then 200' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    $successResponse
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            '404 initially, then 301, then 200' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    new Response(301, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect success' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', 'http://example.com/foo'),
                    $successResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'meta redirect invalid content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/plain', 'http://example.com/foo'),
                    $successResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect no url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html'),
                    $successResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect same url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', 'http://example.com/bar/'),
                    $successResponse,
                ],
                 'url' => 'http://example.com/bar/',
                'expectedResolvedUrl' => 'http://example.com/bar/',
            ],
            'meta redirect relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', '/foobar/'),
                    $successResponse,
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foobar/',
            ],
            'meta redirect protocol-relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', '//example.com/bar/'),
                    $successResponse,
                ],
                'url' => 'https://example.com/',
                'expectedResolvedUrl' => 'https://example.com/bar/',
            ],
        ];
    }

    /**
     * @param array $fixtures
     */
    private function setHttpFixtures($fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->mockHandler->append($fixture);
        }
    }
}
