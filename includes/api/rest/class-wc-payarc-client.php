<?php

class WC_PayArc_API_Rest_Client
{
    const GATEWAY_URL = 'api.payarc.net';
    const TEST_GATEWAY_URL = 'testapi.payarc.net';

    const REQUEST_METHOD_POST = 'POST';
	const REQUEST_METHOD_GET = 'GET';
	const REQUEST_METHOD_PUT = 'PUT';
	const REQUEST_METHOD_PATCH = 'PATCH';
	const REQUEST_METHOD_DELETE = 'DELETE';
	const REQUEST_METHOD_OPTIONS = 'OPTIONS';
    const REQUEST_TIMEOUT = 90;

    /**
     * @var WP_Http
     */
    private $http;

    /**
     * @var WC_PayArc_API_Rest_Response_Validator
     */
    private $validator;

    /**
     * @var WC_PayArc_API_Rest_Censor
     */
    private $censor;

    /**
     * @var string
     */
    private $access_token;

    /**
     * @var bool
     */
    private $is_test_mode;

    /**
     * @var string
     */
    private $request_path;

    /**
     * @var string
     */
    private $request_method;

    /**
     * @var string
     */
    private $accept_type = 'application/json';

    /**
     * @var string
     */
    private $content_type = 'application/x-www-form-urlencoded';

    /**
     * @var bool
     */
    private $prevent_log_flag = false;

    /**
     * @var string
     */
    private $context = self::class;

    public function __construct()
    {
        $this->http = _wp_http_get_object();
        $this->validator = new WC_PayArc_API_Rest_Response_Validator();
        $this->censor = new WC_PayArc_API_Rest_Censor();
    }

    /**
     * @param array $request
     * @return array
     * @throws Exception
     */
    public function execute($request)
    {
        $response = [];

        $request_method = $this->request_method ?: $request['method'];
        if (!$this->is_acceptable_method($request_method)) {
            throw new InvalidArgumentException('Unrecognized method.');
        }

        if (!$this->access_token) {
            throw new Exception('Access token should be provided.');
        }

        $request_path = $this->request_path ?: $request['path'];
        $endpoint_url = 'https://' . $this->get_api_host() . $request_path;

        $payload = $request['payload'] ?? [];

        try {
            $response = $this->http->request(
                $endpoint_url,
                [
                    'method' => $request_method,
                    'body' => $payload,
                    'headers' => [
                        'Accept' => $this->accept_type,
                        'Content-Type' => $this->content_type,
                        'Authorization' => 'Bearer ' . $this->access_token
                    ],
                    'timeout' => self::REQUEST_TIMEOUT
                ]
            );

            // mask sensitive data before logging
            $this->censor->censor($payload);

            $log = [
                'context' => $this->context,
                'endpoint' => $endpoint_url,
                'method' => $request_method,
                'request' => $payload
            ];

            $response = array_merge(
                [
                    'http_code' => $response['response']['code'] ?? 'N/A',
                    'http_message' => $response['response']['message'] ?? 'N/A'
                ],
                json_decode($response['body'], true) ?: []
            );

            $this->validator->set_is_test_mode($this->is_test_mode);
            $this->validator->validate($response);
        } catch (Exception $e) {
            $this->log($e->getMessage(), WC_Log_Levels::ERROR);
            throw $e;
        } finally {
            $log['response'] = $response ?? 'no response.';
            $this->log($log);
        }

        return $response;
    }

    /**
     * @param string $access_token
     */
    public function set_access_token(string $access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * @param bool $is_test_mode
     */
    public function set_is_test_mode(bool $is_test_mode)
    {
        $this->is_test_mode = $is_test_mode;
    }

    /**
     * @param string $request_path
     * @return $this
     */
    public function set_request_path($request_path)
    {
        $this->request_path = $request_path;
        return $this;
    }

    /**
     * @param string $request_method
     * @return $this
     */
    public function set_request_method($request_method)
    {
        $this->request_method = $request_method;
        return $this;
    }

    /**
     * @param string $accept_type
     * @return $this
     */
    public function set_accept_type($accept_type)
    {
        $this->accept_type = $accept_type;
        return $this;
    }

    /**
     * @param string $content_type
     * @return $this
     */
    public function set_content_type($content_type)
    {
        $this->content_type = $content_type;
        return $this;
    }

    /**
     * @param bool $prevent_log
     * @return $this
     */
    public function set_prevent_log_flag($prevent_log = true)
    {
        $this->prevent_log_flag = $prevent_log;
        return $this;
    }

    /**
     * @return string
     */
    private function get_api_host()
    {
        return $this->is_test_mode
            ? self::TEST_GATEWAY_URL : self::GATEWAY_URL;
    }

    /**
     * @param string $method
     * @return bool
     */
    private function is_acceptable_method($method)
    {
        return in_array(
            $method,
            [
                self::REQUEST_METHOD_GET,
                self::REQUEST_METHOD_POST,
                self::REQUEST_METHOD_PUT,
                self::REQUEST_METHOD_PATCH,
                self::REQUEST_METHOD_DELETE,
                self::REQUEST_METHOD_OPTIONS
            ]
        );
    }

    /**
     * @param string|array|object $message
     * @param string $level
     */
    private function log($message, $level = WC_Log_Levels::DEBUG)
    {
        if ($this->prevent_log_flag) {
            return;
        }
        WC_PayArc::instance()->log($message, null, $level);
    }
}
