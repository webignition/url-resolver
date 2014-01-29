<?php

namespace webignition\Tests\Url\Resolver\Resolve;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class ResolveTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';

    
    public function testNoHttpRedirect() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));
    }
    
    
    public function testBadResponseWithRetryWithoutUrlEncodingTo301To200WithMetaRedirectToTarget() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 404",
            "HTTP/1.0 301\nLocation: http://example.com/foo.html",
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=".self::EFFECTIVE_URL."\"></head></html>",
            "HTTP/1.0 200 OK",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        $resolver->getConfiguration()->enableFollowMetaRedirects();
        $resolver->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));        
        $this->assertEquals(200, $resolver->getLastResponse()->getStatusCode());
    }
    
    
    public function testBadResponseWithRetryWithoutUrlEncodingTo301To200WithMetaRedirectTo301To200WithTarget() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 404",
            "HTTP/1.0 301\nLocation: http://example.com/foo.html",
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=http://example.com/bar.html\"></head></html>",
            "HTTP/1.0 301\nLocation: " . self::EFFECTIVE_URL,
            "HTTP/1.0 200 OK",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        $resolver->getConfiguration()->enableFollowMetaRedirects();
        $resolver->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));        
        $this->assertEquals(200, $resolver->getLastResponse()->getStatusCode());        
    }
    
}