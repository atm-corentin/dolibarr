<?php
/* Copyright (C) 2025       Open-Dsi		<support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/sirene/class/client/SireneClientApi.class.php
 *      \ingroup    sirene
 *      \brief      This file for managing Client API
 */

dol_include_once('/sirene/lib/sirene.lib.php');
dol_include_once('/sirene/class/SireneUtils.class.php');
if (!class_exists('GuzzleHttp\\Client')) dol_include_once('/sirene/vendor/autoload.php');
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * 	Class to manage Client API
 */
class SireneClientApi
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	/**
	 * @var string Error
	 */
	public $error = '';
	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var Client  Client REST handler
	 */
	public $client;

	/**
	 * @var string  API URL
	 */
	public $api_url = '';
	/**
	 * @var string  API URL prefix for requesting the API
	 */
	public $api_url_prefix = '';
	/**
	 * @var int  API timeout
	 */
	public $api_timeout = 10;
	/**
	 * @var bool  API no verify ssl
	 */
	public $api_no_verify_ssl = false;
	/**
	 * @var bool  API no verify ssl
	 */
	public $api_user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
	/**
	 * @var int  API count retry to connect to API
	 */
	public $api_retry_count = 0;
	/**
	 * @var bool  Debug mode
	 */
	public $debug_mode = false;

	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PUT = 'PUT';
	const METHOD_PATCH = 'PATCH';
	const METHOD_POST = 'POST';


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;
		$this->db = $db;

		$this->debug_mode = getSireneDolGlobalInt('SIRENE_API_DEBUG');
		$this->api_timeout = getSireneDolGlobalInt('SIRENE_API_TIMEOUT', 10);
	}

	/**
	 * Create config array for Guzzle Client
	 *
	 * @return array		Options for initialize Client
	 */
	protected function getConnectionConfig()
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $conf;

		$options = [];

		// Base URI
		$options['base_uri'] = rtrim($this->api_url, '/') . '/';

		// Timeout
		$options['timeout'] = max(0, (int) $this->api_timeout);

		// No verify SSL
		if ($this->api_no_verify_ssl) $options['verify'] = false;

		// User Agent
		if (!empty($this->api_user_agent)) $options['headers']['User-Agent'] = trim((string) $this->api_user_agent);

		// Proxy infos
		if (getSireneDolGlobalInt('MAIN_PROXY_USE') && getSireneDolGlobalString('MAIN_PROXY_HOST')) {
			$host = getSireneDolGlobalString('MAIN_PROXY_HOST');

			if (strpos($host, 'http') !== false) {
				$split_uri = explode('://', $host);
				$protocol = $split_uri[0];
				$uri = $split_uri[1];
			} else {
				$protocol = 'https';
				$uri = $host;
			}

			$proxy_str = $protocol . '://' . $uri;

			if (getSireneDolGlobalString('MAIN_PROXY_USER') && getSireneDolGlobalString('MAIN_PROXY_PASS')) {
				$login_data = getSireneDolGlobalString('MAIN_PROXY_USER') . ':' . getSireneDolGlobalString('MAIN_PROXY_PASS');
				$proxy_str = $protocol . '://' . $login_data . '@' . $uri;
			}

			if (getSireneDolGlobalInt('MAIN_PROXY_PORT')) {
				$proxy_str = $proxy_str . ':' . getSireneDolGlobalInt('MAIN_PROXY_PORT');
			}

			$options['proxy'] = ['https' => $proxy_str, 'http' => $proxy_str];

			if (getSireneDolGlobalInt('MAIN_USE_RESPONSE_TIMEOUT')) {
				$options['timeout'] = getSireneDolGlobalInt('MAIN_USE_RESPONSE_TIMEOUT');
			}

			if (getSireneDolGlobalInt('MAIN_USE_CONNECT_TIMEOUT')) {
				$options['connection_timeout'] = getSireneDolGlobalInt('MAIN_USE_CONNECT_TIMEOUT');
			}
		}

		// Retry connection
		if ($this->api_retry_count > 0) {
			$stack = GuzzleHttp\HandlerStack::create();

			// Define the retry middleware
			$retryMiddleware = GuzzleHttp\Middleware::retry(
				function ($retries, $request, $response, $exception) {
					// Limit the number of retries to x
					if ($retries >= $this->api_retry_count) {
						return false;
					}

					// Retry on server errors (5xx HTTP status codes)
					if ($response && $response->getStatusCode() >= 500) {
						dol_syslog(__CLASS__ . '::connection is retrying on status code ' . $response->getStatusCode(), LOG_WARNING);
						return true;
					}

					// Retry on connection exceptions
					if ($exception instanceof RequestException && $exception->getCode() === 0) {
						return true;
					}

					return false;
				},
				function ($retries) {
					// Define a delay function (e.g., exponential backoff)
					return (int) pow(2, $retries) * 1000; // Delay in milliseconds
				}
			);

			// Add the retry middleware to the handler stack
			$stack->push($retryMiddleware);

			/*
				// For example
				if (in_array($this->authentication_type, [ 'oauth1_header', 'oauth1_query' ])) {
					dol_include_once('/ecommerceng/includes/oauth-subscriber-woocommerce/src/Oauth1.php');
					$authenticationMiddleware = new GuzzleHttp\Subscriber\Oauth\Oauth1([
						'consumer_key'    => $this->authentication_login,
						'consumer_secret' => $this->authentication_password,
						'request_method' => $this->authentication_type == 'oauth1_header' ? GuzzleHttp\Subscriber\Oauth\Oauth1::REQUEST_METHOD_HEADER : GuzzleHttp\Subscriber\Oauth\Oauth1::REQUEST_METHOD_QUERY,
						'signature_method' => GuzzleHttp\Subscriber\Oauth\Oauth1::SIGNATURE_METHOD_HMACSHA256,
						'api_version' => $this->api_version,
						'include_post_parameters_in_signature' => $this->include_post_parameters_in_signature,
					]);

					$stack->push($authenticationMiddleware);
					$options['auth'] = 'oauth';
				}
			 */

			$options['handler'] = $stack;
		}

		return $options;
	}

	/**
	 *  Connect to the API
	 *
	 * @return	int		                <0 if KO, >0 if OK
	 */
	public function connection()
	{
		global $langs;
		dol_syslog(__METHOD__, LOG_DEBUG);
		$langs->load('sirene@sirene');
		$this->errors = array();
		$this->client = null;

		try {
			$connection_config = $this->getConnectionConfig();

			$this->client = new Client($connection_config);
		} catch (Exception $e) {
			$this->errors[] = $langs->trans('SireneErrorWhileConnectToAPI');
			$this->errors[] = $e->getMessage();
			dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 *  Send to the Api
	 *
	 * @param   string  $method     						Method request
	 * @param   string  $url        						Url request
	 * @param   array   $options    						Options request
	 * @param   bool  	$without_prefix						Without api url prefix
	 * @param   int  	$status_code						Status code returned
	 * @param   int  	$error_info							Error info returned
	 * @return	array                 						null if KO otherwise result data
	 */
	public function sendToApi($method, $url, $options = [], $without_prefix = false, &$status_code = null, &$error_info = null)
	{
		dol_syslog(__METHOD__ . " - method=" . $method . " url=" . $url . " options=" . json_encode($options) . " without_prefix=" . $without_prefix, LOG_NOTICE);
		global $conf, $langs;

		$log_processing_times = getSireneDolGlobalInt('SIRENE_LOG_PROCESSING_TIMES');
		$stopwatch_id = -1;
		try {
			if (isset($status_code)) $status_code = 0;
			$request_url = rtrim($this->api_url, '/') . '/' . ($without_prefix ? '' : trim($this->api_url_prefix, '/') . '/') . ltrim($url, '/');

			// Disabled send data to site
			if (in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE, self::METHOD_PATCH]) && getSireneDolGlobalInt('SIRENE_DISABLED_SEND_DATA_TO_API')) {
				return [];
			}

			$stopwatch_id = SireneUtils::startStopwatch(__METHOD__ . " - {$method} {$request_url}", $log_processing_times);
			switch ($method) {
				case self::METHOD_HEAD:
					$response = $this->client->head($request_url, $options);
					break;
				case self::METHOD_GET:
					$response = $this->client->get($request_url, $options);
					break;
				case self::METHOD_POST:
					$response = $this->client->post($request_url, $options);
					break;
				case self::METHOD_PUT:
					$response = $this->client->put($request_url, $options);
					break;
				case self::METHOD_DELETE:
					$response = $this->client->delete($request_url, $options);
					break;
				case self::METHOD_PATCH:
					$response = $this->client->patch($request_url, $options);
					break;
				default:
					$this->errors[] = 'Bad REST Method';
					dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
					return null;
			}
			SireneUtils::stopStopwatch($stopwatch_id, $log_processing_times);

			if (isset($status_code)) $status_code = $response->getStatusCode();
			$request_data = trim($response->getBody()->getContents());
			$msg_error =  "Method: " . $method . " Url: " . $request_url . " - Options: " . json_encode($options) . " - Data: " . $request_data;
			dol_syslog(__METHOD__ . " - " . $msg_error, LOG_DEBUG);

			$data =  json_decode($request_data, true);
			if (!is_array($data)) {
				if ($this->debug_mode) {
					$this->errors[] = $langs->trans('SireneErrorBadConvertResult') . ' - ' . $msg_error . ' - Converted data: ' . $data . " - Error: " . json_last_error_msg();
				} else {
					$this->errors[] = $langs->trans('SireneErrorBadConvertResult') . " - Error: " . json_last_error_msg();
				}
			}
			dol_syslog(__METHOD__ . " - method=" . $method . " url=" . $url . " options=" . json_encode($options) . " without_prefix=" . $without_prefix . " - Data=" . json_encode($data), LOG_NOTICE);
			return $data;
		} catch (RequestException $e) {
			SireneUtils::stopStopwatch($stopwatch_id, $log_processing_times);

			$request = $e->getRequest();
			$response = $e->getResponse();

			if (isset($response) && isset($status_code)) $status_code = $response->getStatusCode();
			if (isset($response)) $error_info = json_decode(trim($response->getBody()->getContents()), true);
			if (empty($error_info)) $error_info = [];

			$errors_details = array();
			if (isset($request)) $errors_details[] = $this->requestToString($request);
			if (isset($response)) $errors_details[] = $this->responseToString($response);
			else $errors_details[] = '<pre>' . dol_nl2br((string) $e) . '</pre>';

			if ($this->debug_mode) {
				$this->errors = array_merge($this->errors, $errors_details);
			} else {
				if (isset($response)) {
					$suggested_solution = $this->suggestSolutionToHTTPError($response->getStatusCode());
					$boby = strip_tags($response->getBody());
					$boby = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
						return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
					}, $boby);
					$this->errors[] = (empty($suggested_solution) ? '' : '<div class="bold warning">' . $suggested_solution . '</div>') .
						'<br><b class="bold">' . $langs->trans('SireneResponseCode') . ': </b>' . $response->getStatusCode() .
						'<br><b class="bold">' . $langs->trans('SireneResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() .
						(!empty($boby) ? '<br><em>' . $boby . '</em>' : '');
				} else $this->errors[] = $e->getMessage();
			}

			dol_syslog(__METHOD__ . " Error: " . dol_htmlentitiesbr_decode(implode(', ', $errors_details)), LOG_ERR);
			return null;
		} catch (Exception $e) {
			SireneUtils::stopStopwatch($stopwatch_id, $log_processing_times);
			if ($this->debug_mode) {
				$this->errors[] = (string) $e;
			} else {
				$this->errors[] = $e->getMessage();
			}

			dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
			return null;
		}
	}

	/**
	 *  Format the request to a string
	 *
	 * @param   RequestInterface    $request    Request handler
	 * @return	string		                    Formatted string of the request
	 */
	protected function requestToString(RequestInterface $request)
	{
		global $langs;

		$out = '<b>' . $langs->trans('SireneRequestData') . ': </b><br><hr>';
		$out .= '<div style="max-width: 1024px;">';
		$out .= '<b>' . $langs->trans('SireneRequestProtocolVersion') . ': </b>' . $request->getProtocolVersion() . '<br>';
		$out .= '<b>' . $langs->trans('SireneRequestUri') . ': </b>' . $request->getUri() . '<br>';
		$out .= '<b>' . $langs->trans('SireneRequestTarget') . ': </b>' . $request->getRequestTarget() . '<br>';
		$out .= '<b>' . $langs->trans('SireneRequestMethod') . ': </b>' . $request->getMethod() . '<br>';
		$out .= '<b>' . $langs->trans('SireneRequestHeaders') . ':</b><ul>';
		foreach ($request->getHeaders() as $name => $values) {
			$out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
		}
		$out .= '</ul>';
		$out .= '<b>' . $langs->trans('SireneRequestBody') . ': </b>';
		$out .= '<br><em>' . $request->getBody() . '</em><br>';
		$out .= '</div>';
		return $out;
	}

	/**
	 *  Format the response to a string
	 *
	 * @param   ResponseInterface   $response   Response handler
	 * @return	string		                    Formatted string of the response
	 */
	protected function responseToString(ResponseInterface $response)
	{
		global $langs;

		$out = '<b>' . $langs->trans('SireneResponseData') . ': </b><br><hr>';
		$out .= '<div style="max-width: 1024px;">';
		$out .= '<b>' . $langs->trans('SireneResponseProtocolVersion') . ': </b>' . $response->getProtocolVersion() . '<br>';
		$out .= '<b>' . $langs->trans('SireneResponseCode') . ': </b>' . $response->getStatusCode() . '<br>';
		$out .= '<b>' . $langs->trans('SireneResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() . '<br>';
		$out .= '<b>' . $langs->trans('SireneResponseHeaders') . ':</b><ul>';
		foreach ($response->getHeaders() as $name => $values) {
			$out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
		}
		$out .= '</ul>';
		$out .= '<b>' . $langs->trans('SireneResponseBody') . ': </b>';
		$body = json_decode($response->getBody(), true);
		if (is_array($body)) {
			$out .= '<ul>';
			foreach ($body as $name => $values) {
				$out .= '<li><b>' . $name . ': </b>' . (is_array($values) || is_object($values) ? json_encode($values, JSON_UNESCAPED_UNICODE) : $values) . '</li>';
			}
			$out .= '</ul>';
		} else {
			$out .= '<br><em>' . strip_tags($response->getBody()) . '</em><br>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Suggest a solution for the error.
	 * Mainly for codes 401 and 500.
	 *
	 * @param	int		$err_code	Error code
	 * @return	string				Translated string for a solution
	 */
	protected function suggestSolutionToHTTPError(int $err_code)
	{
		return '';
	}

	/**
	 * Method to output saved errors
	 *
	 * @param	string      $separator      Separator between each error
	 * @return	string		                String with errors
	 */
	public function errorsToString($separator = ', ')
	{
		return $this->error . (is_array($this->errors) ? (!empty($this->error) ? $separator : '') . join($separator, $this->errors) : '');
	}
}
