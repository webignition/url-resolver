<?php

namespace webignition\Tests\Url\Resolver\Factory;

use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Message\Response as GuzzleResponse;

class HttpFixtureFactory
{
    /**
     * @param int $statusCode
     * @param array $headers
     * @param string $body
     *
     * @return GuzzleResponse
     */
    public static function createResponse(
        $statusCode,
        $headers = [],
        $body = ''
    ) {
        $messageFactory = new MessageFactory();

        return $messageFactory->createResponse($statusCode, $headers, $body);
    }

    /**
     * @return GuzzleResponse
     */
    public static function createNotFoundResponse()
    {
        return static::createResponse(404);
    }

    /**
     * @param array $headers
     * @param string $body
     *
     * @return GuzzleResponse
     */
    public static function createSuccessResponse($headers = [], $body = '')
    {
        return static::createResponse(200, $headers, $body);
    }

    /**
     * @param int $statusCode
     * @param string $location
     * @return GuzzleResponse
     */
    public static function createRedirect($statusCode, $location)
    {
        return static::createResponse($statusCode, [
            'location' => $location,
        ]);
    }

    /**
     * @param string $contentType
     * @param string $url
     *
     * @return GuzzleResponse
     */
    public static function createMetaRedirectResponse($contentType, $url = null)
    {
        $fixture = HtmlDocumentFactory::load('meta-redirect');
        $content = '0;';

        if (!empty($url)) {
            $content .= 'url=' . $url;
        }

        $body = str_replace('{{ content }}', $content, $fixture);

        return static::createResponse(200, [
            'content-type' => $contentType,
        ], $body);
    }
}
