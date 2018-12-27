<?php

namespace joelwmale\RSSFeedPHP;

use Carbon\Carbon;
use SimpleXMLElement;

use joelwmale\RSSFeedPHP\Exceptions\FeedException;

/**
 * A small, lightweight, and easy-to-use library for consuming an RSS Feed in PHP
 *
 * @copyright  Copyright (c) 2018 Joel Male
 * @license    MIT Licence
 * @version    1.0
 */
class RSSFeedPHP
{
	/** @var int */
	public static $cacheExpire = '1 day';

	/** @var string */
	public static $cacheDir;

	/** @var SimpleXMLElement */
	protected $xml;

	/**
	 * Loads RSS or Atom feed.
	 * 
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * 
	 * @return Feed
	 * 
	 * @throws FeedException
	 */
	public static function load($url, $user = null, $pass = null): RSSFeedPHP
	{	
		$xml = self::loadXml($url, $user, $pass);

		if (!$xml->channel) {
			throw new FeedException('Invalid RSS feed.');
		}

		return self::fromRss($xml);
	}

	/**
	 * Loads RSS feed.
	 * 
	 * @param string $url RSS feed URL
	 * @param string $user optional user name
	 * @param string $pass optional password
	 * 
	 * @return Feed
	 * 
	 * @throws FeedException
	 */
	public static function loadRss($url, $user = null, $pass = null): RSSFeedPHP
	{
		return self::fromRss(self::loadXml($url, $user, $pass));
	}

	/**
	 * Formats the response xml object
	 * 
	 * @param SimpleXMLElement $xml
	 * 
	 * @return RSSFeedPHP
	 */
	private static function fromRss(SimpleXMLElement $xml): RSSFeedPHP
	{
		self::adjustNamespaces($xml);

		foreach ($xml->channel->item as $item) {
			// converts namespaces to dotted tags
			self::adjustNamespaces($item);

			// generate 'timestamp' tag
			if (isset($item->{'dc:date'})) {
				$date = Carbon::createFromTimestamp(strtotime($item->{'dc:date'}));
				$item->date = $date->toDateTimeString();
				$item->humanDifference = $date->diffForHumans();
			} elseif (isset($item->pubDate)) {
				$date = Carbon::createFromTimestamp(strtotime($item->pubDate));
				$item->date = $date->toDateTimeString();
				$item->humanDifference = $date->diffForHumans();
			}

			// if the title is a simplexmlelement
			if ($item->title instanceof SimpleXMLElement) {
				$item->title = $item->title->__toString();
			}
		}
		
		// Instantiate instance of self
		$feed = new self;
		$feed->xml = $xml->channel;

		return $feed;
	}

	/**
	 * Returns property value. Do not call directly.
	 * 
	 * @param string $name
	 * 
	 * @return SimpleXMLElement
	 */
	public function __get($name)
	{
		return $this->xml->{$name};
	}

	/**
	 * Sets value of a property. Do not call directly.
	 * 
	 * @param string $name
	 * @param mixed $value
	 * 
	 * @return void
	 */
	public function __set($name, $value)
	{
		throw new Exception("Cannot assign to a read-only property '$name'.");
	}

	/**
	 * Converts a SimpleXMLElement into an array.
	 * 
	 * @param SimpleXMLElement $xml
	 * 
	 * @return array
	 */
	public function toArray(SimpleXMLElement $xml = null)
	{
		if ($xml === null) {
			$xml = $this->xml;
		}

		if (!$xml->children()) {
			return (string) $xml;
		}

		$arr = array();
		foreach ($xml->children() as $tag => $child) {
			if (count($xml->$tag) === 1) {
				$arr[$tag] = $this->toArray($child);
			} else {
				$arr[$tag][] = $this->toArray($child);
			}
		}

		return $arr;
	}

	/**
	 * Load XML from cache or HTTP.
	 * 
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * 
	 * @return SimpleXMLElement
	 * 
	 * @throws FeedException
	 */
	private static function loadXml($url, $user, $pass)
	{
		$e = self::$cacheExpire;
		$cacheFile = self::$cacheDir . '/feed.' . md5(serialize(func_get_args())) . '.xml';

		if (self::$cacheDir
			&& (time() - @filemtime($cacheFile) <= (is_string($e) ? strtotime($e) - time() : $e))
			&& $data = @file_get_contents($cacheFile)
		) {
			// ok
		} elseif ($data = trim(self::httpRequest($url, $user, $pass))) {
			if (self::$cacheDir) {
				file_put_contents($cacheFile, $data);
			}
		} elseif (self::$cacheDir && $data = @file_get_contents($cacheFile)) {
			// ok
		} else {
			throw new FeedException('Cannot load feed.');
		}

		return new SimpleXMLElement($data, LIBXML_NOWARNING | LIBXML_NOERROR);
	}

	/**
	 * Process HTTP request.
	 * 
	 * @param string
	 * @param string
	 * @param string
	 * 
	 * @return string|false
	 * 
	 * @throws FeedException
	 */
	private static function httpRequest($url, $user, $pass): ?String
	{
		if (extension_loaded('curl')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			if ($user !== null || $pass !== null) {
				curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
			}
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_ENCODING, '');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // no echo, just return result
			if (!ini_get('open_basedir')) {
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // sometime is useful :)
			}
			$result = curl_exec($curl);
			return curl_errno($curl) === 0 && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200
				? $result
				: null;

		} elseif ($user === null && $pass === null) {
			return file_get_contents($url);

		} else {
			throw new FeedException('PHP extension CURL is not loaded.');
		}
	}

	/**
	 * Generates better accessible namespaced tags.
	 * 
	 * @param SimpleXMLElement $el
	 * 
	 * @return void
	 */
	private static function adjustNamespaces($el)
	{
		foreach ($el->getNamespaces(true) as $prefix => $ns) {
			$children = $el->children($ns);
			foreach ($children as $tag => $content) {
				$el->{$prefix . ':' . $tag} = $content;
			}
		}
	}
}