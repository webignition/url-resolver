<?php

namespace webignition\Tests\Url\Resolver;

use GuzzleHttp\Exception\ConnectException;
use QueryPath\Exception as QueryPathException;
use webignition\Tests\Url\Resolver\Factory\HttpFixtureFactory;
use GuzzleHttp\Client as HttpClient;
use webignition\Url\Resolver\Resolver;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpClient
     */
    private $httpClient;

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

        $this->httpClient = new HttpClient();
        $this->resolver = new Resolver($this->httpClient);
    }

    /**
     * @throws QueryPathException
     */
    public function testResolveTimeout()
    {
        $this->resolver->setTimeoutMs(1);

        $this->expectException(ConnectException::class);
        $this->expectExceptionMessage('cURL error 28: Resolving timed out after');

        $this->resolver->resolve('http://example.com/');
    }

    /**
     * @dataProvider resolveHttpRedirectDataProvider
     *
     * @param array $httpFixtures
     * @param string $url
     * @param string $expectedResolvedUrl
     *
     * @throws QueryPathException
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
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 302' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(302, 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 303' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(303, 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 307' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(307, 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'single 308' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(308, 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'multiple 301' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/bar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foobar'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foobar',
            ],
        ];
    }

    /**
     * @dataProvider resolveTooManyRedirectsDataProvider
     *
     * @param array $httpFixtures
     * @param bool $enableHistory
     * @param string $url
     * @param string $expectedResolvedUrl
     *
     * @throws QueryPathException
     */
    public function testResolveTooManyRedirects(
        $httpFixtures,
        $enableHistory,
        $url,
        $expectedResolvedUrl
    ) {
        if ($enableHistory) {
            $this->httpClient->getEmitter()->attach(new HttpHistorySubscriber());
        }

        $this->setHttpFixtures($httpFixtures);
        $this->assertEquals($expectedResolvedUrl, $this->resolver->resolve($url));
    }

    /**
     * @return array
     */
    public function resolveTooManyRedirectsDataProvider()
    {
        return [
            'too many redirects; no history' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/bar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foobar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/bar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foobar'),
                ],
                'enableHistory' => false,
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/bar',
            ],
            'too many redirects; has history' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/bar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foobar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/bar'),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foobar'),
                ],
                'enableHistory' => true,
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
        return [
            'no redirects' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            '404' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            '404 initially, then 200' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            '404 initially, then 301, then 200; no retry with url encoding disabled' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createRedirect(301, 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect success' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'meta redirect invalid content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/plain', 'http://example.com/foo'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect no url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect same url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', 'http://example.com/bar/'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                 'url' => 'http://example.com/bar/',
                'expectedResolvedUrl' => 'http://example.com/bar/',
            ],
            'meta redirect relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', '/foobar/'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'url' => 'http://example.com/',
                'expectedResolvedUrl' => 'http://example.com/foobar/',
            ],
            'meta redirect protocol-relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMetaRedirectResponse('text/html', '//example.com/bar/'),
                    HttpFixtureFactory::createSuccessResponse(),
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
        $httpMockSubscriber = new HttpMockSubscriber($fixtures);

        $this->httpClient->getEmitter()->attach($httpMockSubscriber);
    }
}
