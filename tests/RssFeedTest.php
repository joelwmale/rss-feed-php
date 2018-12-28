<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

use RSSFeedPHP;

class RssFeedTest extends TestCase {
    
    // Test feed to pull in for all tests
    const TEST_FEED = 'https://abcnews.go.com/abcnews/topstories';

    public function test_can_retrieve_rss_feed() {
        $feed = RSSFeedPHP::load(self::TEST_FEED);

        $this->assertInstanceOf(RSSFeedPHP::class, $feed);
        $this->assertInstanceOf(\SimpleXMLElement::class, $feed->xml);
    }
}