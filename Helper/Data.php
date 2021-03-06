<?php
/**
 *                       ######
 *                       ######
 * ############    ####( ######  #####. ######  ############   ############
 * #############  #####( ######  #####. ######  #############  #############
 *        ######  #####( ######  #####. ######  #####  ######  #####  ######
 * ###### ######  #####( ######  #####. ######  #####  #####   #####  ######
 * ###### ######  #####( ######  #####. ######  #####          #####  ######
 * #############  #############  #############  #############  #####  ######
 *  ############   ############  #############   ############  #####  ######
 *                                      ######
 *                               #############
 *                               ############
 *
 * Adyen Payment module (https://www.adyen.com/)
 *
 * Copyright (c) 2015 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */

namespace Adyen\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\Config\DataInterface
     */
    protected $_dataStorage;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_country;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Config\DataInterface $dataStorage
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Config\DataInterface $dataStorage,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
        $this->_dataStorage = $dataStorage;
        $this->_country = $country;
        $this->_moduleList = $moduleList;
    }

    /**
     * @desc return recurring types for configuration setting
     * @return array
     */
    public function getRecurringTypes()
    {
        return [
            \Adyen\Payment\Model\RecurringType::ONECLICK => 'ONECLICK',
            \Adyen\Payment\Model\RecurringType::ONECLICK_RECURRING => 'ONECLICK,RECURRING',
            \Adyen\Payment\Model\RecurringType::RECURRING => 'RECURRING'
        ];
    }

    /**
     * @desc return recurring types for configuration setting
     * @return array
     */
    public function getModes()
    {
        return [
            '1' => 'Test Mode',
            '0' => 'Production Mode'
        ];
    }

    /**
     * @desc return recurring types for configuration setting
     * @return array
     */
    public function getCaptureModes() {
        return [
            'auto' => 'immediate',
            'manual' => 'manual'
        ];
    }

    /**
     * @desc return recurring types for configuration setting
     * @return array
     */
    public function getPaymentRoutines() {
        return [
            'single' => 'Single Page Payment Routine',
            'multi' => 'Multi-page Payment Routine'
        ];
    }


    /**
     * Return the formatted currency. Adyen accepts the currency in multiple formats.
     * @param $amount
     * @param $currency
     * @return string
     */
    public function formatAmount($amount, $currency)
    {
        switch($currency) {
            case "JPY":
            case "IDR":
            case "KRW":
            case "BYR":
            case "VND":
            case "CVE":
            case "DJF":
            case "GNF":
            case "PYG":
            case "RWF":
            case "UGX":
            case "VUV":
            case "XAF":
            case "XOF":
            case "XPF":
            case "GHC":
            case "KMF":
                $format = 0;
                break;
            case "MRO":
                $format = 1;
                break;
            case "BHD":
            case "JOD":
            case "KWD":
            case "OMR":
            case "LYD":
            case "TND":
                $format = 3;
                break;
            default:
                $format = 2;
                break;
        }

        return number_format($amount, $format, '', '');
    }

    /**
     * @param $amount
     * @param $currency
     * @return float
     */
    public function originalAmount($amount, $currency)
    {
        // check the format
        switch($currency) {
            case "JPY":
            case "IDR":
            case "KRW":
            case "BYR":
            case "VND":
            case "CVE":
            case "DJF":
            case "GNF":
            case "PYG":
            case "RWF":
            case "UGX":
            case "VUV":
            case "XAF":
            case "XOF":
            case "XPF":
            case "GHC":
            case "KMF":
                $format = 1;
                break;
            case "MRO":
                $format = 10;
                break;
            case "BHD":
            case "JOD":
            case "KWD":
            case "OMR":
            case "LYD":
            case "TND":
                $format = 1000;
                break;
            default:
                $format = 100;
                break;
        }

        return ($amount / $format);
    }

    /**
     * Street format
     * @param type $address
     * @return array
     */
    public function getStreet($address)
    {
        if (empty($address)) {
            return false;
        }

        $street = self::formatStreet($address->getStreet());
        $streetName = $street['0'];
        unset($street['0']);
        $streetNr = implode(' ', $street);
        return (['name' => trim($streetName), 'house_number' => $streetNr]);
    }

    /**
     * Fix this one string street + number
     * @example street + number
     * @param type $street
     * @return type $street
     */
    static public function formatStreet($street)
    {
        if (count($street) != 1) {
            return $street;
        }
        preg_match('/((\s\d{0,10})|(\s\d{0,10}\w{1,3}))$/i', $street['0'], $houseNumber, PREG_OFFSET_CAPTURE);
        if (!empty($houseNumber['0'])) {
            $_houseNumber = trim($houseNumber['0']['0']);
            $position = $houseNumber['0']['1'];
            $streetName = trim(substr($street['0'], 0, $position));
            $street = [$streetName, $_houseNumber];
        }
        return $street;
    }


    /**
     * @desc gives back global configuration values
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenAbstractConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_abstract', $storeId);
    }

    /**
     * @desc gives back global configuration values as boolean
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenAbstractConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_abstract', $storeId, true);
    }

    /**
     * @desc Gives back adyen_cc configuration values
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenCcConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_cc', $storeId);
    }

    /**
     * @desc Gives back adyen_cc configuration values as flag
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenCcConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_cc', $storeId, true);
    }

    /**
     * @desc Gives back adyen_hpp configuration values
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenHppConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_hpp', $storeId);
    }

    /**
     * @desc Gives back adyen_hpp configuration values as flag
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenHppConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_hpp', $storeId, true);
    }

    /**
     * @desc Gives back adyen_oneclick configuration values
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenOneclickConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_oneclick', $storeId);
    }

    /**
     * @desc Gives back adyen_oneclick configuration values as flag
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenOneclickConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_oneclick', $storeId, true);
    }

    /**
     * @desc Gives back adyen_pos configuration values
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenPosConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_pos', $storeId);
    }

    /**
     * @desc Gives back adyen_pos configuration values as flag
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenPosConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_pos', $storeId, true);
    }

    /**
     * @desc Gives back adyen_pay_by_mail configuration values
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenPayByMailConfigData($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_pay_by_mail', $storeId);
    }

    /**
     * @desc Gives back adyen_pay_by_mail configuration values as flag
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdyenPayByMailConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfigData($field, 'adyen_pay_by_mail', $storeId, true);
    }

    /**
     * @desc Retrieve decrypted hmac key
     * @return string
     */
    public function getHmac()
    {
        switch ($this->isDemoMode()) {
            case true:
                $secretWord =  $this->_encryptor->decrypt(trim($this->getAdyenHppConfigData('hmac_test')));
                break;
            default:
                $secretWord = $this->_encryptor->decrypt(trim($this->getAdyenHppConfigData('hmac_live')));
                break;
        }
        return $secretWord;
    }

    public function getHmacPayByMail()
    {
        switch ($this->isDemoMode()) {
            case true:
                $secretWord =  $this->_encryptor->decrypt(trim($this->getAdyenPayByMailConfigData('hmac_test')));
                break;
            default:
                $secretWord = $this->_encryptor->decrypt(trim($this->getAdyenPayByMailConfigData('hmac_live')));
                break;
        }
        return $secretWord;
    }

    /**
     * @desc Check if configuration is set to demo mode
     * @return mixed
     */
    public function isDemoMode()
    {
        return $this->getAdyenAbstractConfigDataFlag('demo_mode');
    }

    /**
     * @desc Retrieve the decrypted notification password
     * @return string
     */
    public function getNotificationPassword()
    {
        return $this->_encryptor->decrypt(trim($this->getAdyenAbstractConfigData('notification_password')));
    }

    /**
     * @desc Retrieve the webserver username
     * @return string
     */
    public function getWsUsername()
    {
        if ($this->isDemoMode()) {
            $wsUsername =  trim($this->getAdyenAbstractConfigData('ws_username_test'));
        } else {
            $wsUsername = trim($this->getAdyenAbstractConfigData('ws_username_live'));
        }
        return $wsUsername;
    }

    /**
     * @desc Retrieve the webserver password
     * @return string
     */
    public function getWsPassword()
    {
        if ($this->isDemoMode()) {
            $wsPassword = $this->_encryptor->decrypt(trim($this->getAdyenAbstractConfigData('ws_password_test')));
        } else {
            $wsPassword = $this->_encryptor->decrypt(trim($this->getAdyenAbstractConfigData('ws_password_live')));
        }
        return $wsPassword;
    }

    /**
     * @desc Retrieve the webserver url defined in the config.xlm only
     * @return string
     */
    public function getWsUrl()
    {
        if ($this->isDemoMode()) {
            $url = $this->getAdyenAbstractConfigData('ws_url_test');
        } else {
            $url =  $this->getAdyenAbstractConfigData('ws_url_live');
        }
        return $url;
    }

    /**
     * @desc Cancels the order
     * @param $order
     */
    public function cancelOrder($order)
    {
        $orderStatus = $this->getAdyenAbstractConfigData('payment_cancelled');
        $order->setActionFlag($orderStatus, true);

        switch ($orderStatus) {
            case \Magento\Sales\Model\Order::STATE_HOLDED:
                if ($order->canHold()) {
                    $order->hold()->save();
                }
                break;
            default:
                if ($order->canCancel()) {
                    $order->cancel()->save();
                }
                break;
        }
    }

    /**
     * Creditcard type that is selected is different from creditcard type that we get back from the request this
     * function get the magento creditcard type this is needed for getting settings like installments
     * @param $ccType
     * @return mixed
     */
    public function getMagentoCreditCartType($ccType)
    {
        $ccTypesMapper = $this->getCcTypesAltData();

        if (isset($ccTypesMapper[$ccType])) {
            $ccType = $ccTypesMapper[$ccType]['code'];
        }

        return $ccType;
    }

    /**
     * @return array
     */
    public function getCcTypesAltData()
    {
        $adyenCcTypes =  $this->getAdyenCcTypes();
        $types = [];
        foreach ($adyenCcTypes as $key => $data) {
            $types[$data['code_alt']] = $data;
            $types[$data['code_alt']]['code'] = $key;
        }
        return $types;
    }

    /**
     * @return mixed
     */
    public function getAdyenCcTypes()
    {
        return $this->_dataStorage->get('adyen_credit_cards');
    }

    /**
     * @desc Retrieve information from payment configuration
     * @param $field
     * @param $paymentMethodCode
     * @param $storeId
     * @param bool|false $flag
     * @return bool|mixed
     */
    public function getConfigData($field, $paymentMethodCode, $storeId, $flag = false)
    {
        $path = 'payment/' . $paymentMethodCode . '/' . $field;

        if(!$flag) {
            return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->scopeConfig->isSetFlag($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        }
    }


    /**
     * @return array
     */
    public function getSepaCountries()
    {
        $sepaCountriesAllowed = [
            "AT", "BE", "BG", "CH", "CY", "CZ", "DE", "DK", "EE", "ES", "FI", "FR", "GB", "GF", "GI", "GP", "GR", "HR",
            "HU", "IE", "IS", "IT", "LI", "LT", "LU", "LV", "MC", "MQ", "MT", "NL", "NO", "PL", "PT", "RE", "RO", "SE",
            "SI", "SK"
        ];

        $countryList = $this->_country->toOptionArray();
        $sepaCountries = [];

        foreach ($countryList as $key => $country) {
            $value = $country['value'];
            if (in_array($value, $sepaCountriesAllowed)) {
                $sepaCountries[$value] = $country['label'];
            }
        }
        return $sepaCountries;
    }

    public function getModuleVersion()
    {
        return (string) $this->_moduleList->getOne("Adyen_Payment")['setup_version'];
    }
}