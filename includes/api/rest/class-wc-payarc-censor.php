<?php

class WC_PayArc_API_Rest_Censor
{
    const SENSITIVE_DATA_KEYS = ['card_number', 'cvv'];

    public function censor(&$data)
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $k => &$v) {
            if (in_array($k, self::SENSITIVE_DATA_KEYS)) {
                $v = '*MASKED*';
            }

            if (is_array($v)) {
                $this->censor($v);
            }
        }
    }
}
