<?php

namespace PPP\Stripe\Util;

use PPP\Stripe\Object;

abstract class Util
{
    /**
     * Whether the provided array (or other) is a list rather than a dictionary.
     *
     * @param array|mixed $array
     * @return boolean True if the given object is a list.
     */
    public static function isList($array)
    {
        if (!is_array($array)) {
            return false;
        }

      // TODO: generally incorrect, but it's correct given Stripe's response
        foreach (array_keys($array) as $k) {
            if (!is_numeric($k)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Recursively converts the PHP Stripe object to an array.
     *
     * @param array $values The PHP Stripe object to convert.
     * @return array
     */
    public static function convertStripeObjectToArray($values)
    {
        $results = array();
        foreach ($values as $k => $v) {
            // FIXME: this is an encapsulation violation
            if ($k[0] == '_') {
                continue;
            }
            if ($v instanceof Object) {
                $results[$k] = $v->__toArray(true);
            } elseif (is_array($v)) {
                $results[$k] = self::convertStripeObjectToArray($v);
            } else {
                $results[$k] = $v;
            }
        }
        return $results;
    }

    /**
     * Converts a response from the Stripe API to the corresponding PHP object.
     *
     * @param array $resp The response from the Stripe API.
     * @param array $opts
     * @return Object|array
     */
    public static function convertToStripeObject($resp, $opts)
    {
        $types = array(
            'account' => 'PPP\\Stripe\\Account',
            'card' => 'PPP\\Stripe\\Card',
            'charge' => 'PPP\\Stripe\\Charge',
            'country_spec' => 'PPP\\Stripe\\CountrySpec',
            'coupon' => 'PPP\\Stripe\\Coupon',
            'customer' => 'PPP\\Stripe\\Customer',
            'list' => 'PPP\\Stripe\\Collection',
            'invoice' => 'PPP\\Stripe\\Invoice',
            'invoiceitem' => 'PPP\\Stripe\\InvoiceItem',
            'event' => 'PPP\\Stripe\\Event',
            'file' => 'PPP\\Stripe\\FileUpload',
            'token' => 'PPP\\Stripe\\Token',
            'transfer' => 'PPP\\Stripe\\Transfer',
            'plan' => 'PPP\\Stripe\\Plan',
            'recipient' => 'PPP\\Stripe\\Recipient',
            'refund' => 'PPP\\Stripe\\Refund',
            'subscription' => 'PPP\\Stripe\\Subscription',
            'fee_refund' => 'PPP\\Stripe\\ApplicationFeeRefund',
            'bitcoin_receiver' => 'PPP\\Stripe\\BitcoinReceiver',
            'bitcoin_transaction' => 'PPP\\Stripe\\BitcoinTransaction',
        );
        if (self::isList($resp)) {
            $mapped = array();
            foreach ($resp as $i) {
                array_push($mapped, self::convertToStripeObject($i, $opts));
            }
            return $mapped;
        } elseif (is_array($resp)) {
            if (isset($resp['object']) && is_string($resp['object']) && isset($types[$resp['object']])) {
                $class = $types[$resp['object']];
            } else {
                $class = 'PPP\\Stripe\\Object';
            }
            return $class::constructFrom($resp, $opts);
        } else {
            return $resp;
        }
    }
}
