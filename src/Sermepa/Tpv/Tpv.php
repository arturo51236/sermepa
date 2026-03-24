<?php

namespace Sermepa\Tpv;

use Exception;

/**
 * Class Sermepa
 */
class Tpv
{
    CONST TIMEOUT = 10;
    CONST READ_TIMEOUT = 120;
    CONST SSLVERSION_TLSv1_2 = 6;

    protected $environment;
    protected $nameForm;
    protected $idForm;
    protected $parameters;
    protected $version;
    protected $nameSubmit;
    protected $idSubmit;
    protected $valueSubmit;
    protected $styleSubmit;
    protected $classSubmit;
    protected $signature;
    
    // InSite properties
    protected $inSiteMode = false;
    protected $inSiteJsUrl = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setEnvironment();

        $this->parameters = array();
        $this->version = 'HMAC_SHA512_V2';
        $this->nameForm = 'redsys_form';
        $this->idForm = 'redsys_form';
        $this->nameSubmit = 'btn_submit';
        $this->idSubmit = 'btn_submit';
        $this->valueSubmit = 'Send';
        $this->styleSubmit = '';
        $this->classSubmit = '';

    }

    /************* NEW METHODS ************* */

    /**
     * Set identifier required
     *
     * @param string $value Este parámetro se utilizará para manejar la referencia asociada a los datos de tarjeta. Es
     *                      un campo alfanumérico de un máximo de 40 posiciones cuyo valor es generado por el TPV
     *                      Virtual.
     *
     * @return $this
     * @throws TpvException
     */
    public function setIdentifier($value = 'REQUIRED')
    {
        if ($this->isEmpty($value)) {
            throw new TpvException('Please add value');
        }

        $this->parameters['DS_MERCHANT_IDENTIFIER'] = $value;

        return $this;
    }

    /**
     * @param bool $flat
     *
     * @return $this
     * @throws TpvException
     */
    public function setMerchantDirectPayment($flat = false)
    {
        if (!is_bool($flat)) {
            throw new TpvException('Please set true or false');
        }

        $this->parameters['DS_MERCHANT_DIRECTPAYMENT'] = $flat;

        return $this;
    }

    /**
     * Set amount (required)
     *
     * @param $amount
     *
     * @return $this
     * @throws TpvException
     */
    public function setAmount($amount)
    {
        if ($amount < 0) {
            throw new TpvException('Amount must be greater than or equal to 0.');
        }

        $amount = $this->convertNumber($amount);
        $amount = intval(strval($amount * 100));

        $this->parameters['DS_MERCHANT_AMOUNT'] = $amount;

        return $this;
    }

    /**
     * Set Order number - [The first 4 digits must be numeric.] (required)
     *
     * @param $order
     *
     * @return $this
     * @throws TpvException
     */
    public function setOrder($order='')
    {
        $order = trim($order);
        if (strlen($order) <= 3 || strlen($order) > 12 || !preg_match('/^[\w\.]+$/', substr($order, 0, 4))) {
            throw new TpvException('Order id must be a 4 digit string at least, maximum 12 characters.');
        }

        $this->parameters['DS_MERCHANT_ORDER'] = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->parameters['DS_MERCHANT_ORDER'];
    }

    /**
     * Get Ds_Order of Notification
     *
     * @param array $parameters Array with parameters
     *
     * @return string
     */
    public function getOrderNotification($parameters)
    {
        $order = '';
        foreach ($parameters as $key => $value) {
            if (strtolower($key) === 'ds_order') {
                $order = $value;
            }
        }

        return $order;
    }

    /**
     * Set code Fuc of trade (required)
     *
     * @param string $fuc Fuc
     *
     * @return $this
     * @throws TpvException
     */
    public function setMerchantcode($fuc='')
    {
        if ($this->isEmpty($fuc)) {
            throw new TpvException('Please add Fuc');
        }

        $this->parameters['DS_MERCHANT_MERCHANTCODE'] = $fuc;

        return $this;
    }

    /**
     * Set currency
     *
     * @param int $currency Algunos ejemplos: 978 para Euros, 840 para Dólares, 826 para libras esterlinas y 392 para Yenes.
     *
     * @return $this
     * @throws TpvException
     */
    public function setCurrency($currency = 978)
    {
        if (!preg_match('/^[0-9]{3}$/', $currency)) {
            throw new TpvException('Currency is not valid');
        }

        $this->parameters['DS_MERCHANT_CURRENCY'] = $currency;

        return $this;
    }

    /**
     * Set Transaction type
     *
     * @param int $transaction
     *
     * @return $this
     * @throws TpvException
     */
    public function setTransactiontype($transaction = 0)
    {
        if ($this->isEmpty($transaction)) {
            throw new TpvException('Please add transaction type');
        }

        $this->parameters['DS_MERCHANT_TRANSACTIONTYPE'] = $transaction;

        return $this;
    }

    /**
     * Set terminal by default is 1 to  Sadabell(required)
     *
     * @param int $terminal
     *
     * @return $this
     * @throws TpvException
     */
    public function setTerminal($terminal = 1)
    {
        if (intval($terminal) === 0) {
            throw new TpvException('Terminal is not valid.');
        }

        $this->parameters['DS_MERCHANT_TERMINAL'] = $terminal;

        return $this;
    }

    /**
     * Set url notification
     *
     * @param string $url
     * @return $this
     */
    public function setNotification($url = '')
    {
        $this->parameters['DS_MERCHANT_MERCHANTURL'] = $url;

        return $this;
    }

    /**
     * Set url Ok
     *
     * @param string $url
     * @return $this
     */
    public function setUrlOk($url = '')
    {
        $this->parameters['DS_MERCHANT_URLOK'] = $url;

        return $this;
    }

    /**
     * Set url Ko
     *
     * @param string $url
     * @return $this
     */
    public function setUrlKo($url = '')
    {
        $this->parameters['DS_MERCHANT_URLKO'] = $url;

        return $this;
    }

    /**
     * @param string $version
     * @return $this
     * @throws TpvException
     */
    public function setVersion($version = '')
    {
        if ($this->isEmpty($version)) {
            throw new TpvException('Please add version.');
        }
        $this->version = $version;

        return $this;
    }

    /**
     * Generate Merchant Parameters
     *
     * @return string
     */
    public function generateMerchantParameters()
    {
        //Convert Array to Json
        $json = $this->arrayToJson($this->parameters);

        //Return Json to Base64
        return $this->base64_url_encode_safe($json);
    }

    /**
     * Generate Merchant Signature
     *
     * @param string $key
     *
     * @return string
     */
    public function generateMerchantSignature($key)
    {
        //Generate Merchant Parameters
        $merchant_parameter = $this->generateMerchantParameters();

        // Get key with Order and key based on version
        switch ($this->version) {
            case 'HMAC_SHA256_V1':
                $key = $this->encrypt_3DES($this->getOrder(), $key);
                // Generated Hmac256 of Merchant Parameter
                $result = $this->hmac256($merchant_parameter, $key);
                return $this->base64_url_encode($result);
            case 'HMAC_SHA512_V2':
            default:
                $key = $this->encrypt_AES($this->getOrder(), $key);
                // Generated Hmac512 of Merchant Parameter
                $result = $this->hmac512($merchant_parameter, $key);
                return $this->base64_url_encode_safe($result);
        }
    }

    /**
     * Generate Merchant Signature Notification
     *
     * @param string $key
     * @param string $data
     *
     * @return string
     */
    public function generateMerchantSignatureNotification($key, $data)
    {
        // Decode data base64
        $decode = $this->base64_url_decode($data);
        // Los datos decodificados se pasan al array de datos
        $parameters = $this->JsonToArray($decode);
        $order = $this->getOrderNotification($parameters);

        // Get key with Order and key based on version
        switch ($this->version) {
            case 'HMAC_SHA256_V1':
                $key = $this->encrypt_3DES($order, $key);
                // Generated Hmac256 of Merchant Parameter
                $result = $this->hmac256($data, $key);
                return $this->base64_url_encode($result);
            case 'HMAC_SHA512_V2':
            default:
                $key = $this->encrypt_AES($order, $key);
                // Generated Hmac512 of Merchant Parameter
                $result = $this->hmac512($data, $key);
                return $this->base64_url_encode_safe($result);
        }
    }

    /**
     * Set Merchant Signature
     *
     * @param string $signature
     * @return $this
     */
    public function setMerchantSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Set environment
     *
     * @param string $environment test or live
     *
     * @return $this
     * @throws TpvException
     */
    public function setEnvironment($environment = 'test')
    {
        $environment = trim($environment);
        if ($environment === 'live') {
            //Live
            $this->environment = 'https://sis.redsys.es/sis/realizarPago';
        } elseif ($environment === 'test') {
            //Test
            $this->environment = 'https://sis-t.redsys.es:25443/sis/realizarPago';
        } elseif ($environment === 'restLive' || $environment === 'manageRequestRestLive') {
            //Rest Live
            $this->environment = 'https://sis.redsys.es/sis/rest/trataPeticionREST';
        } elseif ($environment === 'restTest' || $environment === 'manageRequestRestTest' ) {
            //Rest Test
            $this->environment = 'https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST';
        } elseif ($environment === 'startRequestRestLive') {
            //Start request
            $this->environment = 'https://sis.redsys.es/sis/rest/iniciaPeticionREST';
        } elseif ($environment === 'startRequestRestTest') {
            //Start request test
            $this->environment = 'https://sis-t.redsys.es:25443/sis/rest/iniciaPeticionREST';
        } elseif ($environment === 'insiteSandbox') {
            //InSite Sandbox - JS URL
            $this->inSiteJsUrl = 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js';
            $this->environment = 'https://sis-t.redsys.es:25443/sis/rest/iniciaPeticionREST';
        } elseif ($environment === 'insiteLive') {
            //InSite Live - JS URL
            $this->inSiteJsUrl = 'https://sis.redsys.es/sis/NC/redsysV3.js';
            $this->environment = 'https://sis.redsys.es/sis/rest/iniciaPeticionREST';
        } elseif ($environment === 'insiteRestSandbox') {
            //InSite REST Sandbox (for sendInSite)
            $this->inSiteJsUrl = 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js';
            $this->environment = 'https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST';
        } elseif ($environment === 'insiteRestLive') {
            //InSite REST Live (for sendInSite)
            $this->inSiteJsUrl = 'https://sis.redsys.es/sis/NC/redsysV3.js';
            $this->environment = 'https://sis.redsys.es/sis/rest/trataPeticionREST';
        } else {
            throw new TpvException('Add test or live');
        }

        return $this;
    }

    /**
     * Set language code by default 001 = Spanish
     *
     * @param string $languageCode Language code [Castellano-001, Inglés-002, Catalán-003, Francés-004, Alemán-005,
     *                             Holandés-006, Italiano-007, Sueco-008, Portugués-009, Valenciano-010, Polaco-011,
     *                             Gallego-012 y Euskera-013.]
     *
     * @return $this
     * @throws Exception
     */
    public function setLanguage($languageCode = '001')
    {
        if ($this->isEmpty($languageCode)) {
            throw new TpvException('Add language code');
        }

        $this->parameters['DS_MERCHANT_CONSUMERLANGUAGE'] = trim($languageCode);

        return $this;
    }

    /**
     * Return environment
     *
     * @return string Url of environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Returns the path to the Redsys JavaScript file for the specified environment and version.
     *
     * @param string $environment Environment: test or live.
     * @param string $version JavaScript file version: 2 or 3.
     * @return string JavaScript file path.
     */
    public static function getJsPath($environment = 'test', $version = '2'){

        // Stores the array of JavaScript file paths.
        static $jsPaths = [
            'test' => [
                '2' => 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV2.js',
                '3' => 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js',
            ],
            'live' => [
                '2' => 'https://sis.redsys.es/sis/NC/redsysV2.js',
                '3' => 'https://sis.redsys.es/sis/NC/redsysV3.js',
            ],
        ];

        // Checks if the environment and version are valid.
        if (!isset($jsPaths[$environment][$version])) {
            throw new TpvException('Invalid environment or version');
        }

        // Returns the JavaScript file path.
        return $jsPaths[$environment][$version];
    }

    /**
     * Optional field for the trade to be included in the data sent by the "on-line" response to trade if this option
     * has been chosen.
     *
     * @param string $merchantdata
     *
     * @return $this
     * @throws Exception
     */
    public function setMerchantData($merchantdata='')
    {
        if ($this->isEmpty($merchantdata)) {
            throw new TpvException('Add merchant data');
        }

        $this->parameters['DS_MERCHANT_MERCHANTDATA'] = trim($merchantdata);

        return $this;
    }

    /**
     * Set product description (optional)
     *
     * @param string $description
     *
     * @return $this
     * @throws Exception
     */
    public function setProductDescription($description = '')
    {
        if ($this->isEmpty($description)) {
            throw new TpvException('Add product description');
        }

        $this->parameters['DS_MERCHANT_PRODUCTDESCRIPTION'] = trim($description);

        return $this;
    }

    /**
     * Set name of the user making the purchase (required)
     *
     * @param string $titular name of the user (for example Alonso Cotos)
     *
     * @return $this
     * @throws Exception
     */
    public function setTitular($titular = '')
    {
        if ($this->isEmpty($titular)) {
            throw new TpvException('Add name for the user');
        }

        $this->parameters['DS_MERCHANT_TITULAR'] = trim($titular);

        return $this;
    }

    /**
     * Set Trade name Trade name will be reflected in the ticket trade (Optional)
     *
     * @param string $tradename trade name
     *
     * @return $this
     * @throws Exception
     */
    public function setTradeName($tradename = '')
    {
        if ($this->isEmpty($tradename)) {
            throw new TpvException('Add name for Trade name');
        }

        $this->parameters['DS_MERCHANT_MERCHANTNAME'] = trim($tradename);

        return $this;
    }

    /**
     * Payment type
     *
     * @param string $method
     * [
     *      T o C = Sólo Tarjeta (mostrará sólo el formulario para datos de tarjeta)
     *      R = Pago por Transferencia,
     *      D = Domiciliación
     *      z = Bizum
     *      p = PayPal
     *      N = Masterpass
     *      xpay = GooglePay y ApplePay
     * ]
     *
     * @return $this
     * @throws Exception
     */
    public function setMethod($method = 'C')
    {
        if ($this->isEmpty($method)) {
            throw new TpvException('Add pay method');
        }

        if (!in_array($method, ['T', 'C', 'R', 'D', 'z', 'p', 'N', 'xpay'])) {
            throw new TpvException('Pay method is not valid');
        }

        $this->parameters['DS_MERCHANT_PAYMETHODS'] = trim($method);

        return $this;
    }

    /**
     * Card number
     *
     * @param string $pan Tarjeta. Su longitud depende del tipo de tarjeta.
     *
     * @return $this
     * @throws TpvException
     */
    public function setPan($pan=0)
    {
        if (intval($pan) === 0) {
            throw new TpvException('Pan not valid');
        }

        $this->parameters['DS_MERCHANT_PAN'] = $pan;

        return $this;
    }

    /**
     * Expire date
     *
     * @param $expirydate . Caducidad de la tarjeta. Su formato es AAMM, siendo AA los dos últimos dígitos del año y MM
     *                    los dos dígitos del mes.
     *
     * @return $this
     * @throws TpvException
     */
    public function setExpiryDate($expirydate='')
    {
        if ( !$this->isExpiryDate($expirydate) ) {
            throw new TpvException('Expire date is not valid');
        }
        $this->parameters['DS_MERCHANT_EXPIRYDATE'] = $expirydate;
        return $this;

    }

    /**
     * Set parameters
     *
     * @param array $parameters
     * @return $this
     * @throws TpvException
     */

    public function setParameters($parameters=[])
    {
        if(!is_array($parameters)) {
            throw new TpvException('Parameters is not an array');
        }

        $keys = array_keys($parameters);

        if(array_keys($keys) === $keys ) {
            throw new TpvException('Parameters is not an array associative');
        }

        $parameters = array_change_key_case($parameters, CASE_UPPER);
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    /**
     * CVV2 card
     *
     * @param string $cvv2 Código CVV2 de la tarjeta
     *
     * @return $this
     * @throws TpvException
     */
    public function setCVV2($cvv2=0)
    {
        if (intval($cvv2) === 0) {
            throw new TpvException('CVV2 is not valid');
        }

        $this->parameters['DS_MERCHANT_CVV2'] = $cvv2;

        return $this;
    }

    /**
     * Set name to form
     *
     * @param string $name Name for form.
     * @return $this
     */
    public function setNameForm($name = 'servired_form')
    {
        $this->nameForm = $name;

        return $this;
    }

    /**
     * Get name form
     *
     * @return string
     */
    public function getNameForm()
    {
        return $this->nameForm;
    }

    /**
     * Set Id to form
     *
     * @param string $id Name for Id
     * @return $this
     */
    public function setIdForm($id = 'servired_form')
    {
        $this->idForm = $id;

        return $this;
    }

    /**
     * Set Attributes to submit
     *
     * @param string $name Name submit
     * @param string $id Id submit
     * @param string $value Value submit
     * @param string $style Set Style
     * @param string $cssClass CSS class
     * @return $this
     */
    public function setAttributesSubmit(
        $name = 'btn_submit',
        $id = 'btn_submit',
        $value = 'Send',
        $style = '',
        $cssClass = ''
    ) {
        $this->nameSubmit = $name;
        $this->idSubmit = $id;
        $this->valueSubmit = $value;
        $this->styleSubmit = $style;
        $this->classSubmit = $cssClass;

        return $this;
    }

    /**
     * Execute redirection to TPV
     *
     * @return string|null
     */
    public function executeRedirection($return = false)
    {
        $html = $this->createForm();
        $html .= '<script>document.forms["'.$this->nameForm.'"].submit();</script>';

        if (!$return) {
            echo $html;

            return null;
        }

        return $html;
    }

    /**
     * Generate form html
     *
     * @return string
     */
    public function createForm()
    {
        $form = '
            <form action="'.$this->environment.'" method="post" id="'.$this->idForm.'" name="'.$this->nameForm.'" >
                <input type="hidden" name="Ds_MerchantParameters" value="'.$this->generateMerchantParameters().'"/>
                <input type="hidden" name="Ds_Signature" value="'.$this->signature.'"/>
                <input type="hidden" name="Ds_SignatureVersion" value="'.$this->version.'"/>
                <input type="submit" name="'.$this->nameSubmit.'" id="'.$this->idSubmit.'" value="'.$this->valueSubmit.'" '.($this->styleSubmit != '' ? ' style="'.$this->styleSubmit.'"' : '').' '.($this->classSubmit != '' ? ' class="'.$this->classSubmit.'"' : '').'>
            </form>
        ';

        return $form;
    }

    /**
     * Send data
     */
    public function send()
    {
        $data['Ds_MerchantParameters'] = $this->generateMerchantParameters();
        $data['Ds_Signature'] = $this->signature;
        $data['Ds_SignatureVersion'] = $this->version;

        $jsonCode = json_encode($data);

        $rest = curl_init();
        curl_setopt_array($rest, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_URL => $this->environment,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_TIMEOUT => self::READ_TIMEOUT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSLVERSION => self::SSLVERSION_TLSv1_2,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonCode
        ]);

        $tmp = curl_exec($rest);
        $httpCode = curl_getinfo($rest, CURLINFO_HTTP_CODE);

        if ($tmp !== false && $httpCode == 200) {
            $result = $tmp;
        } else {
            $strError = "Request failure " . (($httpCode != 200) ? "[HttpCode: '" . $httpCode . "']" : "") . ((curl_error($rest)) ? " [Error: '" . curl_error($rest) . "']" : "");
            exit($strError);
        }

        curl_close($rest);

        return $result;
    }

    /**
     * Check if properly made ​​the purchase.
     *
     * @param string $key      Key
     * @param array  $postData Data received by the bank
     *
     * @return bool
     * @throws TpvException
     */
    public function check($key, $postData)
    {
        if (!isset($postData)) {
            throw new TpvException("Add data return of bank");
        }

        $parameters = $postData["Ds_MerchantParameters"];
        $signatureReceived = $postData["Ds_Signature"];
        $signature = $this->generateMerchantSignatureNotification($key, $parameters);

        return hash_equals($signature, $signatureReceived);
    }

    /**
     *  Decode Ds_MerchantParameters, return array with the parameters
     *
     * @param $parameters
     *
     * @return array with parameters of bank
     */
    public function getMerchantParameters($parameters)
    {
        $decoded = $this->decodeParameters($parameters);

        return $this->JsonToArray($decoded);
    }

    /**
     * Return array with all parameters assigned.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Return MerchantSignature
     *
     * @return string
     */
    public function getMerchantSignature()
    {
        return $this->signature;
    }

    /**
     * COF Transition Indicator.
     * Mandatory for COF Visa and MC operations.
     * Possible values:
     * “S”: It is first COF transaction (store credentials)
     * “N”: It is not the first COF transaction
     *
     * @param $value
     *
     * @return $this
     * @throws Exception
     */
    public function setMerchantCofIni($value)
    {
        $validOptions = ['S', 'N'];
        $value = strtoupper($value);
        if (!in_array($value, $validOptions, true)) {
            throw new TpvException('Set Merchant COF INI valid options');
        }
        $this->parameters['DS_MERCHANT_COF_INI'] = $value;

        return $this;
    }

    /**
     * COF transaction type. Optional for COF Visa and MC.
     * Possible values:
     * “I”: Installments “R”: Recurring
     * “H”: Reauthorization “E”: Resubmission “D”: Delayed
     * “M”: Incremental “N”: No Show
     * “C”: Otras
     *
     * @return $this
     * @throws TpvException
     */
    public function setMerchantCofType($value)
    {
        $validOptions = ['I', 'R', 'H', 'E', 'D', 'M', 'N', 'C'];
        $value = strtoupper($value);
        if (!in_array($value, $validOptions, true)) {
            throw new TpvException('Set Merchant COF type');
        }
        $this->parameters['DS_MERCHANT_COF_TYPE'] = $value;

        return $this;
    }

    /**
     * Para startRequestRestLive, startRequestRestTest, insiteLive, insiteSandbox: 
     * "Y" o "N" para comprobar las exenciones compatibles con la tarjeta.
     * 
     * Para restLive, restTest, insiteRestLive, insiteRestSandbox: 
     * "MIT", "LWV", "TRA", "COR" y "ATD".
     * 
     * Requiere activación por parte de la entidad.
     *
     * @return $this
     * @throws TpvException
     */
    public function setMerchantExcepSca($value)
    {
        $validOptions = ['Y', 'N', 'MIT', 'LWV', 'TRA', 'COR', 'ATD'];
        $value = strtoupper($value);

        if (!in_array($value, $validOptions, true)) {
            throw new TpvException('Set any of the Merchant Excep SCA valid options');
        }
        $this->parameters['DS_MERCHANT_EXCEP_SCA'] = $value;

        return $this;
    }

    /**
     * COF identifier identifier. Optional. This
     * identifier is returned in the answer of the first
     * COF (store credentials) operation and
     * we must send in successive transactions made
     * with the credentials that generated this same Id_txn
     *
     * @return $this
     */
    public function setMerchantCofTxnid($txid)
    {
        if($txid) {
        $this->parameters['DS_MERCHANT_COF_TXNID'] = $txid;
        }
        return $this;
    }

    /**
     * Este campo contiene toda la información necesaria para autenticación EMV3DS (V1 o V2)
     * Con el fin de mejorar la experiencia del usuario en el proceso de autenticación,
     * se recomienda añadir en la petición de pago ciertos parámetros para obtener información de la tarjeta.
     * 
     * Doc: https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/parametros-de-entrada-y-salida/ (véase tabla EMV3DS en la petición)
     * 
     * @param array $value
     * @return $this
     * @throws TpvException
     */
    public function setMerchantEmv3ds($value)
    {
        // Validar que por parámetro llega un array
        if (!is_array($value)) {
            throw new TpvException('Merchant EMV3DS must be an array');
        }

        $this->parameters['DS_MERCHANT_EMV3DS'] = $value;
        return $this;
    }

    /**
     * Generates a Redsys order number following the recommended format.
     * The first 4 characters must be numeric, and the remaining characters must be alphanumeric.
     *
     * @param int $length The total length of the order number (must be at least 4)
     * @return string The generated order number
     * @throws InvalidArgumentException If the length is less than 4
     */
    function createOrderNumber($length = 12)
    {
        // Verify that the length is an integer
        if (!is_int($length)) {
            throw new TpvException("The order number length must be an integer.");
        }
        
        // Verify that the length is between 4 and 12
        if ($length < 4 || $length > 12) {
            throw new TpvException("The order number length must be between 4 and 12.");
        }

        // Generate the first 4 numeric digits
        $numericPart = sprintf("%04d", rand(1000, 9999));

        // Define the alphanumeric characters
        $alphanumericCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Generate the remaining alphanumeric part
        $alphanumericPart = substr(
            str_shuffle(
                str_repeat(
                    $alphanumericCharacters,
                    ceil($length / strlen($alphanumericCharacters))
                )
            ),
            0,
            $length - 4
        );

        // Combine the numeric and alphanumeric parts to form the order number
        return $numericPart . $alphanumericPart;
    }

    // ******** UTILS ********

    /**
     * Convert Array to json
     *
     * @param array $data Array
     *
     * @return string Json
     */
    protected function arrayToJson($data)
    {
        return json_encode($data);
    }

    /**
     * Convert Json to array
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function JsonToArray($data)
    {
        return json_decode($data, true);
    }

    /**
     * Generate sha256
     *
     * @param string $data
     * @param string $key
     *
     * @return string
     */
    protected function hmac256($data, $key)
    {
        return hash_hmac('sha256', $data, $key, true);
    }

    /**
     * Generate sha512
     *
     * @param string $data
     * @param string $key
     *
     * @return string
     */
    protected function hmac512($data, $key)
    {
        return hash_hmac('sha512', $data, $key, true);
    }

    /**
     * Encrypt to 3DES
     *
     * @param string $data Data for encrypt
     * @param string $key  Key
     *
     * @return string
     */
    protected function encrypt_3DES($data, $key)
    {
        $iv = "\0\0\0\0\0\0\0\0";
        $data_padded = $data;

        if (strlen($data_padded) % 8) {
            $data_padded = str_pad($data_padded, strlen($data_padded) + 8 - strlen($data_padded) % 8, "\0");
        }

        return openssl_encrypt($data_padded, "DES-EDE3-CBC", $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
    }

    /**
     * Encrypt to AES
     *
     * @param string $data Data for encrypt
     * @param string $key  Key
     *
     * @return string
     */
    protected function encrypt_AES($data, $key)
    {
        $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
        $fixed_key = str_pad(substr($key, 0, 16), 16, "0");

        return base64_encode(openssl_encrypt($data, "AES-128-CBC", $fixed_key, OPENSSL_RAW_DATA, $iv));
    }

    /**
     * @param string $data
     *
     * @return bool|string
     */
    protected function decodeParameters($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    protected function isEmpty($value)
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && '' === trim($value);
    }

    /**
     * Check if expiry date is valid
     *
     * @param string $expirydate
     * @return boolean
     */
    protected function isExpiryDate($expirydate='')
    {
        return (strlen(trim($expirydate)) === 4 && is_numeric($expirydate));
    }

    /**
     * Check is order is valid
     *
     * @param string $order
     * @return boolean
     */
    protected function isValidOrder($order='')
    {
        return ( strlen($order) >= 4 && strlen($order) <= 12 && preg_match('/^[\w\.]+$/', substr($order, 0, 4)) )?true:false;

    }

    /**
     * @param mixed $price
     *
     * @return string
     */
    protected function convertNumber($price)
    {
        return number_format(str_replace(',', '.', $price), 2, '.', '');
    }

    protected function isValidDate($date)
    {
        return preg_match("/^(\d{4})-(\d{1,2})-(\d{1,2})$/", $date, $m)
            ? checkdate(intval($m[2]), intval($m[3]), intval($m[1]))
            : false;
    }

    /******  Base64 Functions  *****
     *
     * @param string $input
     *
     * @return string
     */
    protected function base64_url_encode($input)
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    /**
     * @param string $input
     *
     * @return string
     */
    protected function base64_url_encode_safe($input)
    {
		return str_replace("=", "", strtr(base64_encode($input), '+/', '-_'));
	}

    /**
     * @param string $input
     *
     * @return string
     */
    protected function base64_url_decode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * @param string $input
     *
     * @return string
     */
    protected function base64_url_decode_safe($input)
    {
		$str = str_pad($input, strlen($input) + (4 - strlen($input) % 4) % 4, '=', STR_PAD_RIGHT);
		return base64_decode(strtr($str, '-_', '+/'));
	}

    // ******** END UTILS ********

    /************* INSITE METHODS ************* */

    /**
     * Enable or disable InSite mode
     *
     * @param bool $enabled
     *
     * @return $this
     */
    public function setInSite(bool $enabled = true)
    {
        $this->inSiteMode = $enabled;

        return $this;
    }

    /**
     * Get InSite mode status
     *
     * @return bool
     */
    public function getInSiteMode(): bool
    {
        return $this->inSiteMode;
    }

    /**
     * Get InSite JavaScript URL
     *
     * @return string
     */
    public function getInSiteJsUrl(): string
    {
        return $this->inSiteJsUrl;
    }

    /**
     * Generate InSite form HTML with embedded iframe (unified mode)
     *
     * According to Redsys InSite documentation, this generates an iframe with all payment fields.
     *
     * @param string $containerId    ID for the container div (default: 'card-form')
     * @param string $buttonStyle    CSS style for the submit button
     * @param string $bodyStyle      CSS style for the form body
     * @param string $boxStyle       CSS style for the data input box
     * @param string $inputStyle     CSS style for the input fields
     * @param string $buttonText     Text for the pay button (HTML encoded, e.g., 'Pagar' or 'Bot&#243;n')
     * @param string $language       Language code (1=ES, 2=EN, 3=CA, etc.) or ISO 639-1 (ES, EN, CA)
     * @param bool   $showLogo       Show entity logo (default: true)
     * @param bool   $reducedStyle   Use reduced width style (default: false)
     * @param string $insiteStyle    InSite style: 'inline' or 'twoRows' (default: 'inline')
     *
     * @return string HTML with script and container
     * @throws TpvException
     */
    public function createInSiteForm(
        string $containerId = 'card-form',
        string $buttonStyle = '',
        string $bodyStyle = '',
        string $boxStyle = '',
        string $inputStyle = '',
        string $buttonText = 'Pagar',
        string $language = 'ES',
        bool   $showLogo = true,
        bool   $reducedStyle = false,
        string $insiteStyle = 'inline'
    ): string {
        // Validate required parameters
        if (!isset($this->parameters['DS_MERCHANT_MERCHANTCODE'])) {
            throw new TpvException('Merchant code (FUC) is required for InSite');
        }
        if (!isset($this->parameters['DS_MERCHANT_TERMINAL'])) {
            throw new TpvException('Terminal is required for InSite');
        }
        if (!isset($this->parameters['DS_MERCHANT_ORDER'])) {
            throw new TpvException('Order is required for InSite');
        }

        $jsUrl = $this->inSiteJsUrl;
        if (empty($jsUrl)) {
            $jsUrl = 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js';
        }

        $fuc = $this->parameters['DS_MERCHANT_MERCHANTCODE'];
        $terminal = $this->parameters['DS_MERCHANT_TERMINAL'];
        $order = $this->parameters['DS_MERCHANT_ORDER'];

        // Build the getInSiteForm JavaScript call
        $showLogoJs = $showLogo ? 'true' : 'false';
        $reducedStyleJs = $reducedStyle ? 'true' : 'false';
        $insiteStyleJs = in_array($insiteStyle, ['inline', 'twoRows']) ? $insiteStyle : 'inline';

        $form = '<script src="' . $jsUrl . '"></script>
<div id="' . $containerId . '"></div>
<form name="datos" id="datos" method="post" target="_self">
    <input type="hidden" id="token" name="token" value="">
    <input type="hidden" id="errorCode" name="errorCode" value="">
</form>
<script>
function merchantValidation(){
    // Add custom validations here
    return true;
}
function loadRedsysForm(){
    getInSiteForm(
        \'' . $containerId . '\',
        \'' . $buttonStyle . '\',
        \'' . $bodyStyle . '\',
        \'' . $boxStyle . '\',
        \'' . $inputStyle . '\',
        \'' . $buttonText . '\',
        \'' . $fuc . '\',
        \'' . $terminal . '\',
        \'' . $order . '\',
        \'' . $language . '\',
        ' . $showLogoJs . ',
        ' . $reducedStyleJs . ',
        \'' . $insiteStyleJs . '\'
    );
}
window.addEventListener("message", function receiveMessage(event) {
    storeIdOper(event, "token", "errorCode", merchantValidation);
});
window.onload = loadRedsysForm;
</script>';

        return $form;
    }

    /**
     * Generate InSite form HTML using JSON configuration (recommended)
     *
     * This method provides more flexibility by accepting a configuration array.
     *
     * @param array $options Configuration options:
     *   - 'id' (required): Container ID
     *   - 'fuc' (required): Merchant code
     *   - 'terminal' (required): Terminal number
     *   - 'order' (required): Order number
     *   - 'styleButton': CSS for button
     *   - 'styleBody': CSS for body
     *   - 'styleBox': CSS for input box
     *   - 'styleBoxText': CSS for input text
     *   - 'buttonValue': Button text
     *   - 'idiomaInsite': Language code
     *   - 'mostrarLogoInsite': Show logo (true/false)
     *   - 'estiloReducidoInsite': Reduced style (true/false)
     *   - 'estiloInsite': 'inline' or 'twoRows'
     *
     * @return string HTML with script and container
     * @throws TpvException
     */
    public function createInSiteFormJSON(array $options): string
    {
        // Validate required options
        if (empty($options['id'])) {
            throw new TpvException('Container ID is required for InSite');
        }
        if (empty($options['fuc'])) {
            throw new TpvException('Merchant code (FUC) is required for InSite');
        }
        if (empty($options['terminal'])) {
            throw new TpvException('Terminal is required for InSite');
        }
        if (empty($options['order'])) {
            throw new TpvException('Order is required for InSite');
        }

        $jsUrl = $this->inSiteJsUrl;
        if (empty($jsUrl)) {
            $jsUrl = 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js';
        }

        // Build JSON config
        $config = [
            'id' => $options['id'],
            'fuc' => $options['fuc'],
            'terminal' => $options['terminal'],
            'order' => $options['order']
        ];

        // Optional parameters
        if (isset($options['styleButton'])) {
            $config['styleButton'] = $options['styleButton'];
        }
        if (isset($options['styleBody'])) {
            $config['styleBody'] = $options['styleBody'];
        }
        if (isset($options['styleBox'])) {
            $config['styleBox'] = $options['styleBox'];
        }
        if (isset($options['styleBoxText'])) {
            $config['styleBoxText'] = $options['styleBoxText'];
        }
        if (isset($options['buttonValue'])) {
            $config['buttonValue'] = $options['buttonValue'];
        }
        if (isset($options['idiomaInsite'])) {
            $config['idiomaInsite'] = $options['idiomaInsite'];
        }
        if (isset($options['mostrarLogoInsite'])) {
            $config['mostrarLogoInsite'] = $options['mostrarLogoInsite'];
        }
        if (isset($options['estiloReducidoInsite'])) {
            $config['estiloReducidoInsite'] = $options['estiloReducidoInsite'];
        }
        if (isset($options['estiloInsite'])) {
            $config['estiloInsite'] = $options['estiloInsite'];
        }

        $jsonConfig = json_encode($config);

        $form = '<script src="' . $jsUrl . '"></script>
<div id="' . $options['id'] . '"></div>
<form name="datos" id="datos" method="post" target="_self">
    <input type="hidden" id="token" name="token" value="">
    <input type="hidden" id="errorCode" name="errorCode" value="">
</form>
<script>
function merchantValidation(){
    return true;
}
function loadRedsysForm(){
    var insiteJSON = ' . $jsonConfig . ';
    getInSiteFormJSON(insiteJSON);
}
window.addEventListener("message", function receiveMessage(event) {
    storeIdOper(event, "token", "errorCode", merchantValidation);
});
window.onload = loadRedsysForm;
</script>';

        return $form;
    }

    /**
     * Execute InSite payment with operation ID
     *
     * @param string $idOper  Operation ID obtained from InSite form
     * @param string $key     Merchant key
     *
     * @return string
     * @throws TpvException
     */
    public function sendInSite(string $idOper, string $key): string
    {
        if (empty($idOper)) {
            throw new TpvException('Operation ID is required for InSite payment');
        }

        // Set the operation ID instead of card data
        $this->parameters['DS_MERCHANT_IDOPER'] = $idOper;

        // Generate signature for the request
        $this->generateMerchantSignature($key);

        return $this->send();
    }
}
