<?php

class WC_PayArc_API_Rest_Response_Validator
{
    const OK_HTTP_CODES = [200, 201, 204];

    /**
     * @var bool
     */
    private $is_test_mode;

    /**
     * @param array|WP_Error $response
     * @throws Exception
     */
    public function validate($response)
    {
        if ($response instanceof WP_Error) {
            throw new Exception(
                sprintf(
                    '[%s] Errors: %s.', $this->get_mode(), implode(', ', $response->get_error_messages())
                )
            );
        }

        $code = $response['status_code'] ?? $response['http_code'];
        $message = $response['message'] ?? $response['http_message'];

        if (!in_array($code, self::OK_HTTP_CODES)) {
            throw new Exception(
                sprintf('[%s] %s: %s', $this->get_mode(), $code, $message)
            );
        }

        if (!$failure_code = $response['data']['failure_code'] ?? false) {
            return;
        }

        $failure_message = $response['data']['failure_message'] ?? 'N/A';

        throw new Exception(
            sprintf('[%s] %s: %s', $this->get_mode(), $failure_code, $failure_message)
        );
    }

    /**
     * @param bool $is_test_mode
     */
    public function set_is_test_mode(bool $is_test_mode)
    {
        $this->is_test_mode = $is_test_mode;
    }

    /**
     * @return string
     */
    private function get_mode()
    {
        return $this->is_test_mode ? 'TEST' : 'PROD';
    }
}
