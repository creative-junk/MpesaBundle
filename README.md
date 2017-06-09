# SYMFONY M-PESA API Bundle


This is a Symfony Bundle for the integration Safaricom's M-Pesa Online API. 
The API allows a merchant to initiate Online C2B (Paybill via web) transactions.
The merchant submits authentication details, transaction details, callback url and callback method. 

Normally after request submission, the merchant receives instant feedback with validity status of their requests but 
this bundle implements a custom request for that status. 
The C2B API handles customer validation and authentication via USSD push. 
The customer then confirms the transaction.  

## Requirements
 - PHP 5.6 or above
 - Symfony 2.6 or above
 
## Installation
To install this bundle, run the command below and you will get the latest version by [Packagist][4].

``` bash
composer require crysoft/mpesa-bundle
```
 

## Load the Bundle

Load bundle in AppKernel.php:

``` php
new Crysoft\MpesaBundle\CrysoftMpesaBundle(),
```



## Configuration of the Bundle

Configuration in config.yml:

``` yaml
crysoft_mpesa:
    mpesa:
        
        endpoint:           https://safaricom.co.ke/mpesa_online/lnmo_checkout_server.php?wsdl                   
        callback_url:       http://yourcallbackurl.com/successurl                                                
        callback_method:    POST                                                                                
        paybill_number:     123456                                                                               
        pass_key:           verysecretlongkey                                                                    

        ...
```
 
### Config Options

- endpoint:           https://safaricom.co.ke/mpesa_online/lnmo_checkout_server.php?wsdl

The M-Pesa API Endpoint. Confirm this as it might change at some point

- callback_url:       http://yourcallbackurl.com/successurl 

The fully qualified callback URL to be queried by Safaricom on transaction completion.

- callback_method:    POST

The callback method to be used. Can also be GET
 
- paybill_number:     123456 

The merchant's Paybill number.

- pass_key:           verysecretlongkey 

The SAG Passkey given by Safaricom on registration. You probably have to ask for it.

## Usage


Usage of the Bundle is simple. Use it in your controller you have access to the Service Container 
using _"$this->container"_ which in turn accesses the config variables.

In your controller do:

```php
public function checkoutAction()
{
     //Instantiate a new Mpesa Request passing it the Service Container(Don't worry if you dont know what that is, 
     //Symfony does :-) just do as i do below and you'll be fine)
     $mpesa = new Mpesa($this->container);
     
     //Generate a New Transaction Id unique to thsi transaction. You can generate it however you want or simply use an 
     //Order ID or User ID. it's completely up to you as long as it's unique
     // We just provided this method as an easy way out for those of us too lazy to think of a different way of doing it.   
     $transactionId = $mpesa->generateTransactionNumber();
    
     $response = $mpesa->request(2500)->from(0722000000)->usingReferenceId(456876)->usingTransactionId($transactionId)->transact();

}

```

You can just chain the method calls into one single call as shown above. 
This Bundle comes with a handy method named "generateTransactionNumber()" that generates a random transaction number for you to use.
Take note of the generated transaction id/number as you will use it to query the status of the transaction isntead of waiting for Safaricom
to make the callback.

Requesting for the Status is trivial as this bundle also provides a simple way of doing that:


```php
public function checkstatusAction()
{
    //Use the same Mpesa Class as before
    $mpesa = new Mpesa($this->container);
    
    //Chain the requests. Note this Transaction ID has to be the EXACT same one you used for the Mpesa Transaction request above. 
    $response = $mpesa->usingTransactionId($transactionId)->requestStatus();
    
    //And just to make your life easier we created another class in the Bundle to run through the response from Mpesa and give you the Status
    
    //Use the response above and pass it to the Bundle's MpesaStatus Class
    $mpesaStatus = new MpesaStatus($response);
    
    //Use the Classes Getter methods to get the Bits in the Response. Here is an example of how to do it
    
        $customerNumber             =       $mpesaStatus->getCustomerNumber();
        $transactionAmount          =       $mpesaStatus->getTransactionAmount();
        $transactionStatus          =       $mpesaStatus->getTransactionStatus();
        $transactionDate            =       $mpesaStatus->getTransactionDate();
        $mPesaTransactionId         =       $mpesaStatus->getMpesaTransactionId();
        $merchantTransactionId      =       $mpesaStatus->getMerchantTransactionId();
        $transactionDescription     =       $mpesaStatus->getTransactionDescription();
}

```

You dont have to name your variables as we have named them, you can name them anything. And that's it. You are good to go

##Testing

Be careful when testing this, Paybill will deduct the amount from Mpesa. You can use kes 10 which is the minimum allowed.

##License

The M-Pesa Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
