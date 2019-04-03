<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\Url\Resolver\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\UriInterface;
use webignition\Url\Resolver\Tests\Factory\HttpFixtureFactory;
use GuzzleHttp\Client as HttpClient;
use webignition\Uri\Uri;
use webignition\Url\Resolver\Resolver;

class ResolverTest extends \PHPUnit\Framework\TestCase
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
     */
    public function testResolveHttpRedirect(array $httpFixtures, UriInterface $url, UriInterface $expectedResolvedUrl)
    {
        $this->setHttpFixtures($httpFixtures);
        $this->assertEquals((string) $expectedResolvedUrl, (string) $this->resolver->resolve($url));
    }

    public function resolveHttpRedirectDataProvider(): array
    {
        $successResponse = new Response(200, ['content-type' => 'text/html']);

        return [
            'single 301' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foo'),
            ],
            'single 302' => [
                'httpFixtures' => [
                    new Response(302, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foo'),
            ],
            'single 303' => [
                'httpFixtures' => [
                    new Response(303, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foo'),
            ],
            'single 307' => [
                'httpFixtures' => [
                    new Response(307, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foo'),
            ],
            'single 308' => [
                'httpFixtures' => [
                    new Response(308, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foo'),
            ],
            'multiple 301' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/foo']),
                    new Response(301, ['location' => 'http://example.com/bar']),
                    new Response(301, ['location' => 'http://example.com/foobar']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foobar'),
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
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/bar'),
            ],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(array $httpFixtures, UriInterface $url, UriInterface $expectedResolvedUrl)
    {
        $this->setHttpFixtures($httpFixtures);
        $this->assertEquals((string) $expectedResolvedUrl, (string) $this->resolver->resolve($url));
    }

    public function resolveDataProvider(): array
    {
        $successResponse = new Response(200, ['content-type' => 'text/html']);
        $notFoundResponse = new Response(404);

        return [
            'no redirects' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            '404' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            '404 initially, then 200' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    $successResponse
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            '404 initially, then 301, then 200' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    new Response(301, ['location' => 'http://example.com/foo']),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            'meta redirect success' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', 'http://example.com/foo'),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foo'),
            ],
            'meta redirect invalid content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/plain', 'http://example.com/foo'),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            'meta redirect unparseable content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/pl a i n', 'http://example.com/foo'),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            'meta redirect no url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html'),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/'),
            ],
            'meta redirect same url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', 'http://example.com/bar/'),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/bar/'),
                'expectedResolvedUrl' => new Uri('http://example.com/bar/'),
            ],
            'meta redirect relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', '/foobar/'),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/foobar/'),
            ],
            'meta redirect protocol-relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', '//example.com/bar/'),
                    $successResponse,
                ],
                'url' => new Uri('https://example.com/'),
                'expectedResolvedUrl' => new Uri('https://example.com/bar/'),
            ],
            'meta redirect within document containing unparseable content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse(
                        'text/html',
                        '//example.com/bar/',
                        'meta-redirect-with-unparseable-meta-content-type'
                    ),
                    $successResponse,
                ],
                'url' => new Uri('http://example.com/'),
                'expectedResolvedUrl' => new Uri('http://example.com/bar/'),
            ],
        ];
    }

    private function setHttpFixtures(array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->mockHandler->append($fixture);
        }
    }
}
