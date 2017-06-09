<?php
/*********************************************************************************
 * Developed by Maxx Ng'ang'a
 * (C) 2017 Crysoft Dynamics Ltd
 * Karbon V 2.1
 * User: Maxx
 * Date: 6/7/2017
 * Time: 2:18 PM
 ********************************************************************************/

namespace Crysoft\MpesaBundle\Exceptions;


class InvalidRequestException extends \Exception
{
    /**
     * Error messages with their corresponding keys
     *
     * @var array
     */
    const ERRORS = [
        'CM_PAYBILL' => 'The Paybill Number is required',
        'CM_PASSWORD' => 'The Password is required',
        'CM_TIMESTAMP' => 'The Timestamp is required',
        'CM_TRANS_ID' => 'The Transaction ID is required',
        'CM_REF_ID' => 'The Reference ID is required',
        'CM_AMOUNT' => 'The Transaction Amount is required',
        'CM_NUMBER' => 'The Mobile Number is required',
        'CM_CALL_URL' => 'The Callback URL is required',
        'CM_CALL_METHOD' => 'The Callback Method is required',
    ];

}