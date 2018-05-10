<?php

namespace webignition\Tests\Url\Resolver\Factory;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class HttpFixtureFactory
{
    /**
     * @param string $contentType
     * @param string $url
     *
     * @return ResponseInterface
     */
    public static function createMetaRedirectResponse($contentType, $url = null)
    {
        $fixture = HtmlDocumentFactory::load('meta-redirect');
        $content = '0;';

        if (!empty($url)) {
            $content .= 'url=' . $url;
        }

        $body = str_replace('{{ content }}', $content, $fixture);

        return new Response(200, ['content-type' => $contentType], $body);
    }
}
