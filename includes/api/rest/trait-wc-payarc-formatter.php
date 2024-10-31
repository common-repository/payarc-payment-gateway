<?php

defined('ABSPATH') || exit;

trait WC_PayArc_API_Rest_Formatter
{
    /**
     * @param string|float|int $amount
     * @return float|int
     * @throws \Exception
     */
    public function formatAmount($amount)
    {
        if (!is_numeric($amount)) {
            throw new Exception('Amount should be numeric.');
        }
        return (int)($amount * 100);
    }

    /**
     * @param string $phone
     * @return string
     */
    public function formatPhone($phone)
    {
        return preg_replace('/[^0-9]/','', $phone);
    }
}
