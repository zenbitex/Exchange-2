<?php

namespace Rave\Payment;

use Monolog\Logger;
use Unirest\Request;
use Unirest\Request\Body;
use Monolog\Handler\RotatingFileHandler;

/**
 * Flutterwave's Rave payment gateway PHP SDK
 * @author Olufemi Olanipekun <iolufemi@ymail.com>
 * @version 1.0
 **/

abstract class AbstractRavePayment
{

    protected $publicKey;
    protected $secretKey;
    protected $amount;
    protected $paymentMethod = 'both';
    protected $customDescription;
    protected $customLogo;
    protected $customTitle;
    protected $country;
    protected $currency;
    protected $customerEmail;
    protected $customerFirstname;
    protected $customerLastname;
    protected $customerPhone;
    protected $txref;
    protected $integrityHash;
    protected $payButtonText = 'Make Payment';
    protected $redirectUrl;
    protected $meta = array();
    protected $env = 'staging';
    protected $transactionPrefix;
    public $logger;
    protected $handler;
    protected $stagingUrl = 'https://rave-api-v2.herokuapp.com';
    protected $liveUrl = 'https://api.ravepay.co';
    protected $baseUrl;
    protected $transactionData;
    protected $overrideTransactionReference;
    protected $requeryCount = 0;

    /**
     * Construct
     * @param string $publicKey Your Rave publicKey. Sign up on https://rave.flutterwave.com to get one from your settings page
     * @param string $secretKey Your Rave secretKey. Sign up on https://rave.flutterwave.com to get one from your settings page
     * @param string $prefix This is added to the front of your transaction reference numbers
     * @param string $env This can either be 'staging' or 'live'
     * @param boolean $overrideRefWithPrefix Set this parameter to true to use your prefix as the transaction reference
     * @return object
     * */
	public function __construct($publicKey, $secretKey, $prefix, $env = 'staging', $overrideRefWithPrefix = false)
	{
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->env = $env;
        $this->transactionPrefix = $overrideRefWithPrefix ? $prefix : $prefix.'_';
        $this->overrideTransactionReference = $overrideRefWithPrefix;
        // create a log channel
        $log = new Logger('flutterwave/rave');
        $this->logger = $log;
        $log->pushHandler(new RotatingFileHandler('rave.log', 90, Logger::DEBUG));
        $this->createReferenceNumber();

        if($this->env === 'staging'){
            $this->baseUrl = $this->stagingUrl;
        }elseif($this->env === 'live'){
            $this->baseUrl = $this->liveUrl;
        }else{
            $this->baseUrl = $this->stagingUrl;
        }

        $this->logger->notice('Rave Class Initializes....');

        return $this;
    }

    /**
     * Generates a checksum value for the information to be sent to the payment gateway
     * @return object
     * */
	public function createCheckSum()
	{
        $this->logger->notice('Generating Checksum....');
        $options = array(
            "PBFPubKey" => $this->publicKey,
            "amount" => $this->amount,
            "customer_email" => $this->customerEmail,
            "customer_firstname" => $this->customerFirstname,
            "txref" => $this->txref,
            "payment_method" => $this->paymentMethod,
            "customer_lastname" => $this->customerLastname,
            "country" => $this->country,
            "currency" => $this->currency,
            "custom_description" => $this->customDescription,
            "custom_logo" => $this->customLogo,
            "custom_title" => $this->customTitle,
            "customer_phone" => $this->customerPhone,
            "pay_button_text" => $this->payButtonText,
            "redirect_url" => $this->redirectUrl,
            "hosted_payment" => 1
        );

        ksort($options);

        $this->transactionData = $options;

        $hashedPayload = '';

        foreach($options as $key => $value){
            $hashedPayload .= $value;
        }

        $completeHash = $hashedPayload.$this->secretKey;
        $hash = hash('sha256', $completeHash);

        $this->integrityHash = $hash;
        return $this;
    }

    /**
     * Generates a transaction reference number for the transactions
     * @return object
     * */
	public function createReferenceNumber()
	{
        $this->logger->notice('Generating Reference Number....');
        if ($this->overrideTransactionReference) {
            $this->txref = $this->transactionPrefix;
        } else {
            $this->txref = uniqid($this->transactionPrefix);
        }
        $this->logger->notice('Generated Reference Number....'.$this->txref);
        return $this;
    }

    /**
     * gets the current transaction reference number for the transaction
     * @return string
     * */
	public function getReferenceNumber()
	{
        return $this->txref;
    }

    /**
     * Sets the transaction amount
     * @param integer $amount Transaction amount
     * @return object
     * */
	public function setAmount($amount)
	{
        $this->amount = $amount;
        return $this;
    }

    /**
     * gets the transaction amount
     * @return string
     * */
	public function getAmount()
	{
        return $this;
    }

    /**
     * Sets the allowed payment methods
     * @param string $paymentMethod The allowed payment methods. Can be card, account or both
     * @return object
     * */
	public function setPaymentMethod($paymentMethod)
	{
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * gets the allowed payment methods
     * @return string
     * */
	public function getPaymentMethod()
	{
        return $this;
    }

    /**
     * Sets the transaction description
     * @param string $customDescription The description of the transaction
     * @return object
     * */
	public function setDescription($customDescription)
	{
        $this->customDescription = $customDescription;
        return $this;
    }

    /**
     * gets the transaction description
     * @return string
     * */
	public function getDescription()
	{
        return $this->customDescription;
    }

    /**
     * Sets the payment page logo
     * @param string $customLogo Your Logo
     * @return object
     * */
	public function setLogo($customLogo)
	{
        $this->customLogo = $customLogo;
        return $this;
    }

    /**
     * gets the payment page logo
     * @return string
     * */
	public function getLogo()
	{
        return $this->customLogo;
    }

    /**
     * Sets the payment page title
     * @param string $customTitle A title for the payment. It can be the product name, your business name or anything short and descriptive
     * @return object
     * */
	public function setTitle($customTitle)
	{
        $this->customTitle = $customTitle;
        return $this;
    }

    /**
     * gets the payment page title
     * @return string
     * */
	public function getTitle()
	{
        return $this->customTitle;
    }

    /**
     * Sets transaction country
     * @param string $country The transaction country. Can be NG, US, KE, GH and ZA
     * @return object
     * */
	public function setCountry($country)
	{
        $this->country = $country;
        return $this;
    }

    /**
     * gets the transaction country
     * @return string
     * */
	public function getCountry()
	{
        return $this->country;
    }

    /**
     * Sets the transaction currency
     * @param string $currency The transaction currency. Can be NGN, GHS, KES, ZAR, USD, EUR and GBP
     * @return object
     * */
	public function setCurrency($currency)
	{
        $this->currency = $currency;
        return $this;
    }

    /**
     * gets the transaction currency
     * @return string
     * */
	public function getCurrency()
	{
        return $this->currency;
    }

    /**
     * Sets the customer email
     * @param string $customerEmail This is the paying customer's email
     * @return object
     * */
	public function setEmail($customerEmail)
	{
        $this->customerEmail = $customerEmail;
        return $this;
    }

    /**
     * gets the customer email
     * @return string
     * */
	public function getEmail()
	{
        return $this->customerEmail;
    }

    /**
     * Sets the customer firstname
     * @param string $customerFirstname This is the paying customer's firstname
     * @return object
     * */
	public function setFirstname($customerFirstname)
	{
        $this->customerFirstname = $customerFirstname;
        return $this;
    }

    /**
     * gets the customer firstname
     * @return string
     * */
	public function getFirstname()
	{
        return $this->customerFirstname;
    }

    /**
     * Sets the customer lastname
     * @param string $customerLastname This is the paying customer's lastname
     * @return object
     * */
	public function setLastname($customerLastname)
	{
        $this->customerLastname = $customerLastname;
        return $this;
    }

    /**
     * gets the customer lastname
     * @return string
     * */
	public function getLastname()
	{
        return $this->customerLastname;
    }

    /**
     * Sets the customer phonenumber
     * @param string $customerPhone This is the paying customer's phonenumber
     * @return object
     * */
	public function setPhoneNumber($customerPhone)
	{
        $this->customerPhone = $customerPhone;
        return $this;
    }

    /**
     * gets the customer phonenumber
     * @return string
     * */
	public function getPhoneNumber()
	{
        return $this->customerPhone;
    }

    /**
     * Sets the payment page button text
     * @param string $payButtonText This is the text that should appear on the payment button on the Rave payment gateway.
     * @return object
     * */
	public function setPayButtonText($payButtonText)
	{
        $this->payButtonText = $payButtonText;
        return $this;
    }

    /**
     * gets payment page button text
     * @return string
     * */
	public function getPayButtonText()
	{
        return $this->payButtonText;
    }

    /**
     * Sets the transaction redirect url
     * @param string $redirectUrl This is where the Rave payment gateway will redirect to after completing a payment
     * @return object
     * */
	public function setRedirectUrl($redirectUrl)
	{
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * gets the transaction redirect url
     * @return string
     * */
	public function getRedirectUrl()
	{
        return $this->redirectUrl;
    }

    /**
     * Sets the transaction meta data. Can be called multiple time to set multiple meta data
     * @param array $meta This are the other information you will like to store with the transaction. It is a key => value array. eg. PNR for airlines, product colour or attributes. Example. array('name' => 'femi')
     * @return object
     * */
	public function setMetaData($meta)
	{
        array_push($this->meta, $meta);
        return $this;
    }

    /**
     * gets the transaction meta data
     * @return string
     * */
	public function getMetaData()
	{
        return $this->meta;
    }

    /**
     * Sets the event hooks for all available triggers
     * @param object $handler This is a class that implements the Event Handler Interface
     * @return object
     * */
	public function eventHandler($handler)
	{
        $this->handler = $handler;
        return $this;
    }

    /**
     * Requerys a previous transaction from the Rave payment gateway
     * @param string $referenceNumber This should be the reference number of the transaction you want to requery
     * @return object
     * */
	public function requeryTransaction($referenceNumber)
	{
        $this->txref = $referenceNumber;
        $this->requeryCount++;
        $this->logger->notice('Requerying Transaction....'.$this->txref);
        if(isset($this->handler)){
            $this->handler->onRequery($this->txref);
        }

        $data = array(
            'txref' => $this->txref,
            'SECKEY' => $this->secretKey,
            'last_attempt' => '1'
            // 'only_successful' => '1'
        );

        // make request to endpoint using unirest.
        $headers = array('Content-Type' => 'application/json');
        $body = Body::json($data);
        $url = $this->baseUrl.'/flwv3-pug/getpaidx/api/xrequery';

        // Make `POST` request and handle response with unirest
        $response = Request::post($url, $headers, $body);

        //check the status is success
        if ($response->body && $response->body->status === "success") {
            if($response->body && $response->body->data && $response->body->data->status === "successful"){
                $this->logger->notice('Requeryed a successful transaction....'.json_encode($response->body->data));
                // Handle successful
                if(isset($this->handler)){
                    $this->handler->onSuccessful($response->body->data);
                }
            }elseif($response->body && $response->body->data && $response->body->data->status === "failed"){
                // Handle Failure
                $this->logger->warn('Requeryed a failed transaction....'.json_encode($response->body->data));
                if(isset($this->handler)){
                    $this->handler->onFailure($response->body->data);
                }
            }else{
                // Handled an undecisive transaction. Probably timed out.
                $this->logger->warn('Requeryed an undecisive transaction....'.json_encode($response->body->data));
                // I will requery again here. Just incase we have some devs that cannot setup a queue for requery. I don't like this.
                if($this->requeryCount > 4){
                    // Now you have to setup a queue by force. We couldn't get a status in 5 requeries.
                    if(isset($this->handler)){
                        $this->handler->onTimeout($this->txref, $response->body);
                    }
                }else{
                    $this->logger->notice('delaying next requery for 3 seconds');
                    sleep(3);
                    $this->logger->notice('Now retrying requery...');
                    $this->requeryTransaction($this->txref);
                }
            }
        }else{
            $this->logger->warn('Requery call returned error for transaction reference.....'.json_encode($response->body).'Transaction Reference: '. $this->txref);
            // Handle Requery Error
            if(isset($this->handler)){
                $this->handler->onRequeryError($response->body);
            }
        }
        return $this;
    }

    /**
     * Generates the final json to be used in configuring the payment call to the rave payment gateway
     * @return string
     * */
	public function initialize()
	{
        $this->createCheckSum();
        $this->transactionData = array_merge($this->transactionData, array('integrity_hash' => $this->integrityHash), array('meta' => $this->meta));

        if(isset($this->handler)){
            $this->handler->onInit($this->transactionData);
        }

        $json = json_encode($this->transactionData);
        echo '<html>';
        echo '<body>';
        // echo '<center>Processing...<br /><img src="ajax-loader.gif" /></center>';
        echo '<center>Processing...</center>';
        echo '<script type="text/javascript" src="'.$this->baseUrl.'/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>';
        echo '<script>';
	    echo 'document.addEventListener("DOMContentLoaded", function(event) {';
        echo 'var data = JSON.parse(\''.$json.'\');';
        echo 'getpaidSetup(data);';
        echo '});';
        echo '</script>';
        echo '</body>';
        echo '</html>';

        return $json;
    }

    /**
     * Handle canceled payments with this method
     * @param string $referenceNumber This should be the reference number of the transaction that was canceled
     * @return object
     * */
	public function paymentCanceled($referenceNumber)
	{
        $this->txref = $referenceNumber;
        $this->logger->notice('Payment was canceled by user..'.$this->txref);
        if(isset($this->handler)){
            $this->handler->onCancel($this->txref);
        }
        return $this;
    }

}
