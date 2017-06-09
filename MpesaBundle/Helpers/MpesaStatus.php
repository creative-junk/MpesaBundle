<?php
/*********************************************************************************
 * Developed by Maxx Ng'ang'a
 * (C) 2017 Crysoft Dynamics Ltd
 * Karbon V 2.1
 * User: Maxx
 * Date: 6/8/2017
 * Time: 5:01 PM
 ********************************************************************************/

namespace Crysoft\MpesaBundle\Helpers;

class MpesaStatus
{
    /**
     * @var string Customer's Mpesa Number
     */
    protected $customerNumber;
    /**
     * @var int Amount for this Transaction
     */
    protected $transactionAmount;
    /**
     * @var string Status of the Transaction;Success, Failed or Expired
     */
    protected $transactionStatus;
    /**
     * @var \DateTime When the Transaction took place
     */
    protected $transactionDate;
    /**
     * @var string Mpesa Confirmation Code
     */
    protected $mpesaTransactionId;
    /**
     * @var string Merchant Transaction Id
     */
    protected $merchantTransactionId;
    /**
     * @var string Description of the Transaction Status for example insufficient Funds or Timeout
     */
    protected $transactionDescription;

    function __construct($response)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($response);

        $this->customerNumber             =       $doc->getElementsByTagName('MSISDN')->item(0)->nodeValue;
        $this->transactionAmount          =       $doc->getElementsByTagName('AMOUNT')->item(0)->nodeValue;
        $this->transactionStatus          =       $doc->getElementsByTagName('TRX_STATUS')->item(0)->nodeValue;
        $this->transactionDate            =       $doc->getElementsByTagName('MPESA_TRX_DATE')->item(0)->nodeValue;
        $this->mpesaTransactionId         =       $doc->getElementsByTagName('MPESA_TRX_ID')->item(0)->nodeValue;
        $this->merchantTransactionId      =       $doc->getElementsByTagName('MERCHANT_TRANSACTION_ID')->item(0)->nodeValue;
        $this->transactionDescription     =       $doc->getElementsByTagName('DESCRIPTION')->item(0)->nodeValue;

    }

    /**
     * @return string
     */
    public function getCustomerNumber()
    {
        return $this->customerNumber;
    }

    /**
     * @return int
     */
    public function getTransactionAmount()
    {
        return $this->transactionAmount;
    }

    /**
     * @return string
     */
    public function getTransactionStatus()
    {
        return $this->transactionStatus;
    }

    /**
     * @return \DateTime
     */
    public function getTransactionDate()
    {
        return $this->transactionDate;
    }

    /**
     * @return string
     */
    public function getMpesaTransactionId()
    {
        return $this->mpesaTransactionId;
    }

    /**
     * @return string
     */
    public function getMerchantTransactionId()
    {
        return $this->merchantTransactionId;
    }

    /**
     * @return string
     */
    public function getTransactionDescription()
    {
        return $this->transactionDescription;
    }


}