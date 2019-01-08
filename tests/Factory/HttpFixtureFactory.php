<?php

namespace webignition\Tests\Url\Resolver\Factory;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class HttpFixtureFactory
{
    public static function createMetaRedirectResponse(
        string $contentType,
        string $url = null,
        string $fixtureName = 'meta-redirect'
    ): ResponseInterface {
        $fixture = HtmlDocumentFactory::load($fixtureName);
        $content = '0;';

        if (!empty($url)) {
            $content .= 'url=' . $url;
        }

        $body = str_replace('{{ content }}', $content, $fixture);

        return new Response(200, ['content-type' => $contentType], $body);
    }
}
