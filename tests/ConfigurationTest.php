<?php

namespace webignition\Tests\Url\Resolver;

use webignition\Url\Resolver\Configuration;
use GuzzleHttp\Client as HttpClient;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = new HttpClient();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array $configurationValues
     * @param bool $expectedIsClassHttpClient
     * @param bool $expectedRetryWithUrlEncodingDisabled
     * @param bool $expectedFollowMetaRedirects
     */
    public function testCreate(
        $configurationValues,
        $expectedIsClassHttpClient,
        $expectedRetryWithUrlEncodingDisabled,
        $expectedFollowMetaRedirects
    ) {
        if (isset($configurationValues[Configuration::CONFIG_KEY_HTTP_CLIENT])) {
            $configurationValues[Configuration::CONFIG_KEY_HTTP_CLIENT] = $this->httpClient;
        }

        $configuration = new Configuration($configurationValues);

        if ($expectedIsClassHttpClient) {
            $this->assertEquals(
                spl_object_hash($this->httpClient),
                spl_object_hash($configuration->getHttpClient())
            );
        } else {
            $this->assertNotEquals(
                spl_object_hash($this->httpClient),
                spl_object_hash($configuration->getHttpClient())
            );
        }

        $this->assertEquals($expectedRetryWithUrlEncodingDisabled, $configuration->getRetryWithUrlEncodingDisabled());
        $this->assertEquals($expectedFollowMetaRedirects, $configuration->getFollowMetaRedirects());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'defaults' => [
                'configurationValues' => [],
                'expectedIsClassHttpClient' => false,
                'expectedRetryWithUrlEncodingDisabled' => false,
                'expectedFollowMetaRedirects' => true,
            ],
            'non-defaults' => [
                'configurationValues' => [
                    Configuration::CONFIG_KEY_HTTP_CLIENT => true,
                    Configuration::CONFIG_KEY_RETRY_WITH_URL_ENCODING_DISABLED => true,
                    Configuration::CONFIG_KEY_FOLLOW_META_REDIRECTS => false,
                ],
                'expectedIsClassHttpClient' => true,
                'expectedRetryWithUrlEncodingDisabled' => true,
                'expectedFollowMetaRedirects' => false,
            ],
        ];
    }

//    public function testSetGetFollowMetaRedirects()
//    {
//        $this->assertTrue($this->configuration->getFollowMetaRedirects());
//
//        $this->configuration->setFollowMetaRedirects(false);
//        $this->assertFalse($this->configuration->getFollowMetaRedirects());
//
//        $this->configuration->setFollowMetaRedirects(true);
//        $this->assertTrue($this->configuration->getFollowMetaRedirects());
//    }
//
//    public function testSetGetHttpClient()
//    {
//        $this->assertNull($this->configuration->getHttpClient());
//
//        $httpClient = new HttpClient();
//
//        $this->configuration->setHttpClient($httpClient);
//
//        $this->assertEquals($httpClient, $this->configuration->getHttpClient());
//    }
//
//    public function testSetGetRetryWithUrlEncodingDisabled()
//    {
//        $this->assertFalse($this->configuration->getRetryWithUrlEncodingDisabled());
//
//        $this->configuration->setRetryWithUrlEncodingDisabled(true);
//        $this->assertTrue($this->configuration->getRetryWithUrlEncodingDisabled());
//
//        $this->configuration->setRetryWithUrlEncodingDisabled(false);
//        $this->assertFalse($this->configuration->getRetryWithUrlEncodingDisabled());
//    }
//
//    public function testSetGetHasRetriedWithUrlEncodingDisabled()
//    {
//        $this->assertFalse($this->configuration->getHasRetriedWithUrlEncodingDisabled());
//
//        $this->configuration->setHasRetriedWithUrlEncodingDisabled(true);
//        $this->assertTrue($this->configuration->getHasRetriedWithUrlEncodingDisabled());
//
//        $this->configuration->setHasRetriedWithUrlEncodingDisabled(false);
//        $this->assertFalse($this->configuration->getHasRetriedWithUrlEncodingDisabled());
//    }
}
