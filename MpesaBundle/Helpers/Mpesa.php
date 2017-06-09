<?php
/*********************************************************************************
 * Developed by Maxx Ng'ang'a
 * (C) 2017 Crysoft Dynamics Ltd
 * Karbon V 2.1
 * User: Maxx
 * Date: 6/7/2017
 * Time: 12:29 PM
 ********************************************************************************/

namespace Crysoft\MpesaBundle\Helpers;


use Crysoft\MpesaBundle\Exceptions\InvalidRequestException;
use Crysoft\MpesaBundle\Exceptions\TransactionException;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Mpesa
{
    private $container;

    /**
     * The Mpesa Endpoint
     * @var string
     */
    protected $endPoint;
    /**
     * The Callback URL to be queried on completion
     * @var string
     */
    protected $callbackUrl;
    /**
     * The Callback method to be used
     * @var string
     */
    protected $callbackMethod;
    /**
     * The Merchant's Paybill Number
     *
     * @var int
     */
    protected $paybillNumber;

    /**
     * The SAG passkey given on registration
     * @var string
     */
    protected $passKey;

    /**
     * The Hashed Password
     * @var string
     */
    protected $password;
    /*
     * The Transaction Timestamp
     * @var int
     */
    protected $timestamp;
    /**
     * The transaction reference id
     * @var int
     */
    protected $referenceId;
    /**
     * The amount to be deducted
     * @var int
     */
    protected $amount;
    /**
     * The Mpesa number to be billed
     * Must be in the format 2547XXXXXXXX
     * @var string
     */
    protected $number;
    /**
     * The Keys and data to be fill in the request body
     *
     * @var array
     */
    protected $keys;
    /**
     * The request to be sent to the endpoint
     *
     * @var string
     */
    protected $request;
    /**
     * Generated Unique Transaction Number
     *
     * @var string
     */
    protected $transactionNumber;

    /**
     * The Guzzle Client used to make the request to the endpoint
     *
     * @var Client
     */
    private $client;

    /**
     * Required keys.
     *
     * @var array
     */
    protected $rules = [
        'CM_PAYBILL',
        'CM_PASSWORD',
        'CM_TIMESTAMP',
        'CM_TRANS_ID',
        'CM_REF_ID',
        'CM_AMOUNT',
        'CM_NUMBER',
        'CM_CALL_URL',
        'CM_CALL_METHOD'
    ];

    /**
     * Transactor constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container= $container;

        $this->setUpAPI();

        $this->client = new Client([
           'verify'             => false,
            'timeout'           => 60,
            'allow_redirects'   => false,
            'expect'            => false
        ]);

    }

    public function request($amount){
        if (!is_numeric($amount)){
            throw new \InvalidArgumentException('The Amount must be numeric');
        }
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the Mpesa Number to deduct funds from
     * Must be in the format 2547XXXXXXXX
     *
     * @param $number
     * @return $this
     */
    public function from($number){

        if (substr($number,0,4)!='2547'){
            throw new \InvalidArgumentException("The subscriber number must start with 2547");
        }
        $this->number = $number;

        return $this;
    }

    /**
     * Set the Reference number to bill the account
     * @param int $referenceId
     * @return $this
     */
    public function usingReferenceId($referenceId)
    {
        $this->referenceId = $referenceId;

        return $this;
    }
    /**
     * Set the Transaction number to bill the account
     * @param string $transactionId
     * @return $this
     */
    public function usingTransactionId($transactionId)
    {
        $this->transactionNumber = $transactionId;

        return $this;
    }
    /**
     * Initiate the transaction
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function transact(){
        return $this->process($this->amount,$this->number,$this->referenceId);
    }
    /**
     * Initiate the request
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function requestStatus(){
        return $this->status($this->referenceId);
    }

    /**
     * setup the API options
     */
    protected function setUpAPI()
    {
        $config = $this->container->getParameter('crysoft_mpesa.config');

        $this->endPoint         = $config['mpesa']['endpoint'];
        $this->callbackUrl      = $config['mpesa']['callback_url'];
        $this->callbackMethod   = $config['mpesa']['callback_method'];
        $this->paybillNumber    = $config['mpesa']['paybill_number'];
        $this->passKey          = $config['mpesa']['pass_key'];
    }

    /**
     * Process the transaction request
     *
     * @param $amount
     * @param $number
     * @param $referenceId
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function process($amount, $number, $referenceId){
        $this->number = $number;
        $this->amount = $amount;
        $this->referenceId = $referenceId;
        $this->initialize();

        return $this->handle();
    }

    /**
     * Request for the transaction status
     *
     * @param $transactionId
     * @return mixed | \Psr\Http\Message\ResponseInterface
     */
    protected function status($transactionId){
        $this->referenceId = $transactionId;
        $this->initialize();

        return $this->handleStatusRequest();

    }
    /**
     * Initialize the transaction
     */
    protected function initialize()
    {
        $this->setTimestamp();
        $this->generatePassword();
        $this->setupKeys();

    }

    /**
     * Validate and Handle the transaction
     * @return mixed | \Psr\Http\Message\ResponseInterface
     */
    protected function handle()
    {
        $this->validateKeys();
        $this->generateRequest('request.xml');
        $this->send();
        $this->generateRequest('process.xml');

        return $this->send();
    }
    /**
     * Validate and Handle the transaction Status Request
     * @return mixed | \Psr\Http\Message\ResponseInterface
     */
    protected function handleStatusRequest()
    {
        //$this->validateKeys();
        $this->generateRequest('status.xml');

        return $this->execute();
    }

    /**
     * Set the Transaction timestamp
     * @return string
     */
    protected function setTimestamp()
    {
        $dateTime = new \DateTime();
        $timezone=$dateTime->format('YmdHis');
        //var_dump($timezone);exit;
        $this->timestamp = $timezone;
        return $this->timestamp;
    }

    protected function generatePassword()
    {
        $passwordSource = $this->paybillNumber.$this->passKey.$this->timestamp;
        $this->password = base64_encode(hash("sha256",$passwordSource));

        return $this->password;
    }

    protected function setupKeys()
    {

        $this->keys = [
            'CM_PAYBILL'          => $this->paybillNumber,
            'CM_PASSWORD'         => $this->password,
            'CM_TIMESTAMP'        => $this->timestamp,
            'CM_TRANS_ID'         => $this->transactionNumber,
            'CM_TRANSACTION_ID'   => $this->transactionNumber,
            'CM_REF_ID'           => $this->referenceId,
            'CM_AMOUNT'           => $this->amount,
            'CM_NUMBER'           => $this->number,
            'CM_CALLBACK_URL'     => $this->callbackUrl,
            'CM_CALLBACK_METHOD'  => $this->callbackMethod,
        ];
       // var_dump($this->keys['CM_TRANS_ID']);exit;
    }

    protected function validateKeys()
    {
        $this->validate($this->keys);
    }

    /**
     * Fetch the XML document and include the transaction data
     *
     * @param $document
     */
    protected function generateRequest($document)
    {
        $this->request = file_get_contents(__DIR__ . '/Soap/' . $document);

        foreach ($this->keys as $key => $value) {
            $this->request = str_replace($key, $value, $this->request);
        }
    }

    /**
     * Execute the Request
     *
     * @return mixed | \Psr\Http\Message\ResponseInterface
     */

    protected function send()
    {
        $response = $this->client->request('POST',$this->endPoint,[
            'body'=>  $this->request
        ]);
        $this->validateResponse($response);
        return $response;
    }
    /**
     * Execute the Status Request
     *
     * @return mixed | \Psr\Http\Message\ResponseInterface
     */

    protected function execute()
    {
       /* $response = $this->client->request('GET',$this->endPoint,[
            'body'=>  $this->request
        ]);*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endPoint);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, '0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, '0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, '0');
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Validate the response to verify success, throw error if not
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @throws TransactionException
     */
    protected function validateResponse($response)
    {
        $message = $response->getBody()->getContents();
        $response->getBody()->rewind();
        $doc = new \DOMDocument();
        $doc->loadXML($message);

        $responseCode = $doc->getElementsByTagName('RETURN_CODE')->item(0)->nodeValue;
        if ($responseCode != '00'){
            $responseDescription = $doc
                ->getElementsByTagName('DESCRIPTION')
                ->item(0)
                ->nodeValue;
            throw new TransactionException('Failure - '. $responseDescription);
        }
    }

    /**
     * Generate a random transaction number
     *
     * @return string
     */
    public function generateTransactionNumber()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 17; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    /**
     * Check if key exists else throw exception.
     *
     * @param array $data
     *
     * @throws InvalidRequestException
     */
    protected function validate($data = [])
    {
       /* foreach ($this->rules as $value) {
            if (! array_key_exists($value, $data)) {
                throw new InvalidRequestException(InvalidRequestException::ERRORS[$value]);
            }
        }*/
    }



}