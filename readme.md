# RSS Feeds for PHP

[![travis build](https://travis-ci.org/joelwmale/rss-feed-php.svg?branch=master)](https://travis-ci.org/joelwmale/riot-php-api)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

RSS Feeds for PHP is a very small, lightweight, and easy-to-use library for consuming an RSS feed.

It requires PHP 7.1 or newer with cURL installed on the system, and is licensed under the MIT License.

## Installation

Composer:

```
composer require joelwmale/rss-feed-php
```

## Usage

Download RSS feed from URL:

```php
use joelwmale\RSSFeedPHP;

$feed = RSSFeedPHP::load($url);
```

Elements are returned as `SimpleXMLElement` objects, with the outter most object being an std::class.

```php
echo 'Title: ', $feed->title;
echo 'Description: ', $feed->description;
echo 'Link: ', $feed->link;

foreach ($feed->item as $item) {
	echo 'Title: ', $item->title;
	echo 'Link: ', $item->link;
	echo 'Date: ', $item->date;
	echo 'Description ', $item->description;
	echo 'HTML encoded content: ', $item->{'content:encoded'};
}
```

A helper class is available if you wish to convert it to an array instead:

```php
use joelwmale\RSSFeedPHP;

$feed = RSSFeedPHP::load($url);
$feed->toArray();
```

Caching is available by adding the following:

```php
use joelwmale\RSSFeedPHP;

RSSFeedPHP::$cacheDir = __DIR__ . '/tmp';
RSSFeedPHP::$cacheExpire = '5 hours';
```

## Testing

Tests are run via phpunit:

```bash
phpunit
```
