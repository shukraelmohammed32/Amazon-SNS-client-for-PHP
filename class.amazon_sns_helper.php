<?php
/**
 * Amazon SNS helper class
 * 
 * Just does request's using CURL and signs things
 * 
 * @author Russell Smith <russell.smith@ukd1.co.uk>
 */
class amazon_sns_helper {

	/**
	 * Default API host and version.
	 */
	const DEFAULT_HOST = 'sns.us-east-1.amazonaws.com';
	const API_VERSION = '2010-03-31';
	const USER_AGENT = 'amazon-sns-php-client/0.2';
	const CONNECTION_TIMEOUT = 10;
	const TIMEOUT = 30;

	/**
	 * Sign and perform a request.
	 *
	 * @param array $params key/value list of parameters to pass
	 * @param string $httpMethod HTTP verb, defaults to POST
	 * @return SimpleXMLElement
	 */
	public static function request (array $params, $httpMethod = 'POST') {
		$accessKey = self::getAccessKey();
		$secretKey = self::getSecretKey();
		$host = self::getHost();

		$params = self::injectDefaultParams($params, $accessKey);
		$canonicalQuery = self::buildCanonicalQuery($params);
		$method = strtoupper($httpMethod);
		$stringToSign = $method . "\n" . $host . "\n/\n" . $canonicalQuery;
		$signature = base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));
		$payload = $canonicalQuery . '&Signature=' . rawurlencode($signature);

		list($body, $status) = self::executeCurl($payload, $host, $method);

		return self::parseResponse($body, $status);
	}

	/**
	 * Inject shared parameters required by AWS.
	 */
	private static function injectDefaultParams (array $params, $accessKey) {
		$params['AWSAccessKeyId'] = $accessKey;
		$params['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		$params['SignatureMethod'] = 'HmacSHA256';
		$params['SignatureVersion'] = 2;
		if (!isset($params['Version'])) {
			$params['Version'] = self::API_VERSION;
		}

		return $params;
	}

	/**
	 * Execute the HTTP request via cURL.
	 */
	private static function executeCurl ($payload, $host, $method) {
		$url = 'https://' . $host . '/';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECTION_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
		curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if ($method === 'GET') {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_URL, $url . '?' . $payload);
		} else {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}

		$output = curl_exec($ch);
		if ($output === false) {
			$errmsg = curl_error($ch);
			curl_close($ch);
			throw new RuntimeException('cURL error while calling Amazon SNS: ' . $errmsg);
		}

		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return array($output, $status);
	}

	/**
	 * Parse the XML body and surface AWS errors.
	 */
	private static function parseResponse ($body, $statusCode) {
		$previous = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($body);
		if ($xml === false) {
			$snippet = substr(trim($body), 0, 200);
			libxml_clear_errors();
			libxml_use_internal_errors($previous);
			throw new RuntimeException('Unable to parse SNS response: ' . $snippet);
		}
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ($statusCode >= 400 || isset($xml->Error)) {
			$message = isset($xml->Error->Message) ? (string) $xml->Error->Message : 'Unknown AWS SNS error';
			$code = isset($xml->Error->Code) ? (string) $xml->Error->Code : 'AWS.SNS.Error';
			throw new RuntimeException('AWS SNS error (' . $code . '): ' . $message);
		}

		return $xml;
	}

	/**
	 * Build the canonical query string for signing and sending.
	 */
	private static function buildCanonicalQuery (array $params) {
		ksort($params);
		$encoded = array();
		foreach ($params as $key => $param) {
			$encoded[] = rawurlencode($key) . '=' . rawurlencode($param);
		}

		return implode('&', $encoded);
	}

	/**
	 * Resolve the host to connect to, preferring explicit overrides.
	 */
	private static function getHost () {
		$host = self::readConfigValue('AWS_SNS_HOST', 'AWS_SNS_HOST');
		if (!empty($host)) {
			return $host;
		}

		$region = self::readConfigValue('AWS_REGION', 'AWS_REGION');
		if (!empty($region)) {
			return 'sns.' . $region . '.amazonaws.com';
		}

		return self::DEFAULT_HOST;
	}

	private static function getAccessKey () {
		$value = self::readConfigValue('AWS_ACCESS_KEY', 'AWS_ACCESS_KEY_ID');
		if (empty($value)) {
			throw new RuntimeException('AWS access key missing. Define AWS_ACCESS_KEY or set AWS_ACCESS_KEY_ID.');
		}

		return $value;
	}

	private static function getSecretKey () {
		$value = self::readConfigValue('AWS_PRIVATE_KEY', 'AWS_SECRET_ACCESS_KEY');
		if (empty($value)) {
			throw new RuntimeException('AWS secret key missing. Define AWS_PRIVATE_KEY or set AWS_SECRET_ACCESS_KEY.');
		}

		return $value;
	}

	/**
	 * Reads a value from either a PHP constant or environment variable.
	 */
	private static function readConfigValue ($constant, $envVariable) {
		if (defined($constant)) {
			$value = constant($constant);
			if (!empty($value)) {
				return $value;
			}
		}

		$value = getenv($envVariable);
		if ($value !== false && $value !== '') {
			return $value;
		}

		return null;
	}
}