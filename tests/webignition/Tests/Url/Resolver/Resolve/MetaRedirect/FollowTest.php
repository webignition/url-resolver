<?php

namespace webignition\Tests\Url\Resolver\Resolve\MetaRedirect;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class FollowTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';
    
    public function testWithInvalidContentType() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/plain\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . self::EFFECTIVE_URL . "\"></head></html>",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));            
    }

    
    public function testWithNoUrl() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0\"></head></html>",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));         
    }
    
    
    public function testWithSameUrl() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . self::SOURCE_URL . "\"></head></html>",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));         
    }
    
    public function testWithDifferentUrl() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . self::EFFECTIVE_URL . "\"></head></html>",
            "HTTP/1.0 200 OK"
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));         
    }    
    
    public function testWithRelativeUrl() {
        $relativeUrl = 'foo/bar.html';
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=".$relativeUrl."\"></head></html>",
            "HTTP/1.0 200 OK"
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::SOURCE_URL . $relativeUrl, $resolver->resolve(self::SOURCE_URL));         
    }       
    
    public function testWithProtocolRelativeUrl() {
        $url = '//example.com/foo/bar.html';
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=".$url."\"></head></html>",
            "HTTP/1.0 200 OK"
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals('http:' . $url, $resolver->resolve(self::SOURCE_URL));         
    }
    
}