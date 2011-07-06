<?php

	/**
	 * @author Evgeny Kokovikhin <e.kokovikhin@co.wapstart.ru>
	 * @copyright Copyright (c) 2011, Wapstart
	 * @version 2.0
	 */
	class Plus1BannerAsker
	{
		/**
		 *  {{{ Predefined params
		**/
		const BASE_ROTATOR_URI		= 'http://ro.plus1.wapstart.ru/';
		const COOKIE_NAME			= 'wssid';
		const DEFAULT_FORMAT		= 'xhtml';
		const DEFAULT_ENCODING		= 'utf-8';
		const DEFAULT_MARKUP		= 'xhtmlmp';
		const DEFAULT_CON_TIMEOUT	= 1000;
		const DEFAULT_TIMEOUT		= 1000;
		const BANNER_LABEL			= '<!-- i4jgij4pfd4ssd -->';
		const VERSION				= 2;

		private $markupList = array(
			'wml_1_3' => 2,
			'xhtmlmp' => 3
		);

		protected $formatList = array(
			'xhtml'		=> 'viewBanner',
			'xml'		=> 'viewBannerXml',
			'json'		=> 'viewBannerJson'
		);

		private $sexList = array(
			'man'		=> 1,
			'woman'		=> 2
		);
		
		private $bannerTypeList = array(
			'text'		=> 2,
			'mixed'		=> 1,
			'graphic'	=> 3
		);
		
		private $encodingList = array(
			'utf-8'		=> 1,
			'cp1251'	=> 2
		);

		private $baseRotatorUri = self::BASE_ROTATOR_URI;
		
		/**
		 *  }}} Predefined params
		**/

		/**
		 *  {{{ set up params
		**/
		private static $pageId		= null;

		private $timeout			= self::DEFAULT_TIMEOUT;
		private $connectionTimeout	= self::DEFAULT_CON_TIMEOUT;

		private $id					= null;
		
		private $geodata			= array();
		private $sex				= null; //yes, please
		private $age				= null;
		private $types				= array();
		private $bannerAmount		= null;
		private $onlySingleLine		= null;
		private $defaultDecorator	= false;
		private $disableShield		= false;
		private $disableCounter		= false;
		private $disableBorder		= false;
		private $disableStatistic	= false;
		private $login				= null;
		private $markup				= self::DEFAULT_MARKUP;
		private $encoding			= self::DEFAULT_ENCODING;
		private $format				= self::DEFAULT_FORMAT;

		private $silent				= true;

		/**
		 *  }}} set up params
		**/

		/**
		 * {{{ runtime cache
		**/
		
		private $url				= null;
		private $headerList			= array();
		private $responseHeaderList	= array();
		
		/**
		 * }}} runtime cache
		**/

		public static function checkCookie()
		{
			if (isset($_COOKIE[self::COOKIE_NAME]))
				return;

			self::setCookie();
		}

		public static function setCookie()
		{
			if (headers_sent())
				return;

			try {
				@setcookie(
					self::COOKIE_NAME,
					self::generateCookie(),
					time() + 60 * 60 * 24  * 180
				);
				
			} catch (Exception $e) {/*boo!*/}
		}

		/**
		 * @return Plus1BannerAsker
		 */
		public static function create()
		{
			return new self();
		}
		
		public function fetch()
		{
			return
				$this->{$this->getFormat().'ParseResponse'}(
					$this->sendRequest()
				);
		}

		public function setId($id)
		{
			$this->id = $id;
			$this->url = null;

			return $this;
		}

		public function getId()
		{
			return $this->id;
		}

		public function getTimeout()
		{
			return $this->timeout;
		}

		public function setTimeout($timeout)
		{
		 	if (is_int($timeout))
				$this->timeout = $timeout;
			else
				throw new Plus1BannerAskerException('non-integer timeout value given');

			return $this;
		}

		public function getConnectionTimeout()
		{
			return $this->connectionTimeout;
		}

		public function setConnectionTimeout($connectionTimeout)
		{
			if (is_int($connectionTimeout))
				$this->connectionTimeout = $connectionTimeout;
			else
				throw new Plus1BannerAskerException('non-integer timeout value given');

			return $this;
		}

		public function setBaseRotatorUri($uri)
		{
			$this->baseRotatorUri = $uri;
			$this->url = null;

			return $this;
		}

		public function getBaseRotatorUri()
		{
			return $this->baseRotatorUri;
		}

		public function setSilent($silent = true)
		{
			$this->silent = ($silent === true);

			return $this;
		}

		public function isSilent()
		{
			return ($this->silent === true);
		}

		public function getPageId()
		{
			if (!self::$pageId)
				self::$pageId =
					sha1(
						(isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null)
						.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null)
						.(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null)
						.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null)
						.mt_rand(1, 10000000)
						.microtime(true)
					);
			
			return self::$pageId;
		}

		public function getIp()
		{
			return
				isset($_SERVER['REMOTE_ADDR'])
					? $_SERVER['REMOTE_ADDR']
					: null;
		}

		public function getMarkup()
		{
			return $this->markup;
		}

		public function setMarkup($markup)
		{
			if (isset($this->markupList[$markup]))
				$this->markup = $markup;
			else
				throw new Plus1BannerAskerException(' wrong markup id given');

			$this->url = null;

			return $this;
		}

		public function getGeodata()
		{
			return $this->geodata;
		}

		public function setGeodata($geodata)
		{
			$this->geodata = $geodata;
			$this->url = null;

			return $this;
		}

		public function getSex()
		{
			return $this->sex;
		}

		public function setSex($sex)
		{
			if (isset($this->sexList[$sex]))
				$this->sex = $sex;
			else
				throw new Plus1BannerAskerException('wrong gender id given');

			$this->url = null;

			return $this;
		}

		public function getAge()
		{
			return $this->age;
		}

		public function setAge($age)
		{
			$this->age = $age;
			$this->url = null;

			return $this;
		}

		public function getTypes()
		{
			return $this->types;
		}

		public function addType($bannerType)
		{
			if (isset($this->bannerTypeList[$bannerType]))
				$this->types[] = $bannerType;
			else
				throw new Plus1BannerAskerException('wrong banner type given');

			$this->url = null;

			return $this;
		}

		public function getBannerAmount()
		{
			return $this->bannerAmount;
		}

		public function setBannerAmount($bannerAmount)
		{
			if ($bannerAmount >= 1 && $bannerAmount <= 3)
				$this->bannerAmount = $bannerAmount;
			else
				throw new Plus1BannerAskerException('wrong banner amount given');

			$this->url = null;

			return $this;
		}

		public function isOnlySingleLine()
		{
			return ($this->onlySingleLine === true);
		}

		public function setOnlySingleLine($onlySingleLine = true)
		{
			$this->onlySingleLine = ($onlySingleLine === true);
			$this->url = null;

			return $this;
		}

		public function isDefaultDecorator()
		{
			return ($this->defaultDecorator === true);
		}

		public function setDefaultDecorator($defaultDecorator = true)
		{
			$this->defaultDecorator = ($defaultDecorator === true);
			$this->url = null;

			return $this;
		}

		public function isDisableShield()
		{
			return ($this->disableShield === true);
		}

		public function setDisableShield($disableShield = true)
		{
			$this->disableShield = ($disableShield === true);

			return $this;
		}

		public function isDisableCounter()
		{
			return ($this->disableCounter === true);
		}

		public function setDisableCounter($disableCounter = true)
		{
			$this->disableCounter = ($disableCounter === true);
			$this->url = null;

			return $this;
		}

		public function isDisableBorder()
		{
			return ($this->disableBorder === true);
		}

		public function setDisableBorder($disableBorder = true)
		{
			$this->disableBorder = ($disableBorder === true);
			$this->url = null;

			return $this;
		}

		public function isDisableStatistic()
		{
			return ($this->disableStatistic === true);
		}

		public function setDisableStatistic($disableStatistic = true)
		{
			$this->disableStatistic = ($disableStatistic === true);
			$this->url = null;

			return $this;
		}

		public function getLogin()
		{
			return $this->login;
		}

		public function setLogin($login)
		{
			if (!is_scalar($login))
				throw new Plus1BannerAskerException('what did you give as login?');
			
			$this->login = $login;
			$this->url = null;
			
			return $this;
		}

		public function getEncoding()
		{
			return $this->encoding;
		}

		public function setEncoding($encoding)
		{
			if (isset($this->encodingList[$encoding]))
				$this->encoding = $encoding;
			else
				throw new Plus1BannerAskerException('wrong encoding given');

			$this->url = null;
			
			return $this;
		}

		public function getFormat()
		{
			return $this->format;
		}

		public function setFormat($format)
		{
			if (isset($this->formatList[$format]))
				$this->format = $format;
			else
				throw new Plus1BannerAskerException('wrong code format given');

			$this->url = null;
			
			return $this;
		}
		
		private function sendRequest()
		{
			$curl = curl_init($this->getUrl());

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, true);

			curl_setopt($curl, CURLOPT_TIMEOUT_MS, $this->getTimeout());

			curl_setopt(
				$curl,
				CURLOPT_CONNECTTIMEOUT_MS,
				$this->getConnectionTimeout()
			);
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaderList());

			curl_setopt(
				$curl,
				CURLOPT_USERAGENT,
				isset($_SERVER['HTTP_USER_AGENT'])
					? $_SERVER['HTTP_USER_AGENT']
					: null
			);

			curl_setopt(
				$curl,
				CURLOPT_COOKIE,
				isset($_COOKIE[self::COOKIE_NAME])
					? self::COOKIE_NAME.'='.$_COOKIE[self::COOKIE_NAME]
					: null
			);

			$response = curl_exec($curl);
			
			if ($response === false) {
				if ($this->isSilent())
					return null;
				else
					throw new Plus1BannerAskerException(
						'curl error occuped: '
						.curl_error($curl)
					);
			}
			
			list($header, $body) = explode("\r\n\r\n", $response);

			curl_close($curl);
			
			$this->responseHeaderList = explode("\r\n", $header);

			if (substr($this->responseHeaderList[0], -6) != '200 OK') {
				if ($this->isSilent())
					return null;
				else
					throw new Plus1BannerAskerException(
						'http status of response is not equal to 200: '
						.$this->responseHeaderList[0]
					);
			}

			if (trim($body) == self::BANNER_LABEL) {
				if ($this->isSilent())
					return null;
				else
					throw new Plus1BannerAskerException(
						'response does not contain any banner'
					);
			}
			
			return $body;
		}
		
		public function getUrl()
		{
			if ($this->url)
				return $this->url;

			$this->url =
				$this->getBaseRotatorUri()
				.'?area='.$this->formatList[$this->getFormat()]
				.'&version='.self::VERSION
				.'&id='.$this->getId()
				.'&pageId='.$this->getPageId()
				.'&ip='.$this->getIp()
				.'&encoding='.$this->encodingList[$this->getEncoding()];

			if ($this->getFormat() == 'xhtml')
				$this->url .= '&markup='.$this->markupList[$this->getMarkup()];

			if ($this->getSex())
				$this->url .=
					'&sex='.$this->sexList[$this->getSex()];

			if ($this->getAge())
				$this->url .=
					'&age='.$this->getAge();

			if ($this->getLogin())
				$this->url .= '&login='.$this->getLogin();

			if ($this->getGeodata())
				$this->url .= '&geoData='.$this->getCompiledGeoData();

			if ($this->getTypes()) {
				array_unique($this->getTypes());
				
				foreach ($this->getTypes() as $type)
					$this->url .= '&types[]='.$this->bannerTypeList[$type];
			}

			if ($this->getBannerAmount())
				$this->url.=
					'&bannerAmount='.$this->getBannerAmount()
					.'&textBannerAmount='.$this->getBannerAmount(); //bc

			if ($this->isOnlySingleLine())
				$this->url .= '&onlySingleLine=1';

			if ($this->isDisableStatistic())
				$this->url .= '&noSaveStatistic=1';

			if ($this->getFormat() == 'xhtml') {
				if ($this->isDefaultDecorator())
					$this->url .= '&defaultDecorator=1';

				if ($this->isDisableShield())
					$this->url .= '&disableShield=1';

				if ($this->isDisableCounter())
					$this->url .= '&disableCounter=1';

				if ($this->isDisableBorder())
					$this->url .= '&disableBorder=1';
			}

			return $this->url;
		}

		private function getHeaderList()
		{
			if ($this->headerList)
				return $this->headerList;
			
			foreach ($_SERVER as $headerName => $headerValue)
				if ($this->isSuitableHeader($headerName))
					$this->headerList[] =
						'x-plus-'
						.mb_convert_case(
							str_replace('_', '-', $headerName), MB_CASE_LOWER
						)
						.': '.$headerValue;
			
			return $this->headerList;
		}

		private function xhtmlParseResponse($response)
		{
			if (strpos($response, '<!-- i4jgij4pfd4ssd -->') === false)
				return null;

			return $response;
		}

		private function xmlParseResponse($response)
		{
			try {
				$xml = @simplexml_load_string($response);

				return $xml;
			} catch (Exception $e) {
				return null;
			}
		}
		
		private function jsonParseResponse($response)
		{
			throw new Plus1BannerAskerException('implement me plz');
		}

		private static function generateCookie()
		{
			return
				sha1(
					microtime()
					.rand(1, 1000000)
					.(
						isset($_SERVER['HTTP_USER_AGENT'])
							? $_SERVER['HTTP_USER_AGENT']
							: null
					)
				);
		}

		private function isSuitableHeader($headerName)
		{
			return
				(
					in_array(
						$headerName,
						array(
							'REMOTE_ADDR',
							'HTTP_USER_AGENT',
							'HTTP_HOST',
							'HTTP_REFERER',
							'HTTP_VIA'
						)
					)
					|| strstr($headerName, 'HTTP_ACCEPT_') !== false
					|| strstr($headerName, 'HTTP_X_') !== false
				);
		}

		private function getCompiledGeoData()
		{
			return
				urlencode(
					serialize(
						array(
							'id' => $this->getId(),
							'geoData' => $this->getGeodata()
						)
					)
				);
		}
	}

	final class Plus1BannerAskerException extends Exception {}
	
	if (!defined('PLUS1_ASKER_GOD_MODE'))
		Plus1BannerAsker::checkCookie();
?>
