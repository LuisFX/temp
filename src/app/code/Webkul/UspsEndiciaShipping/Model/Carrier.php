<?php
/**
 * Webkul Software
 *
 * @category Webkul
 * @package Webkul_UspsEndiciaShipping
 * @author Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */
namespace Webkul\UspsEndiciaShipping\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Helper\Carrier as CarrierHelper;
use Magento\Shipping\Model\Rate\Result;
use Magento\Framework\Xml\Security;
use Magento\Framework\Session\SessionManager;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Sales\Model\Order\Shipment;
use Webkul\UspsEndiciaShipping\Api\EndiciaManagementInterfaceFactory;
use Webkul\MarketplaceBaseShipping\Model\ShippingSettingRepository;
use Magento\Usps\Model\Carrier as UspsCarrier;
use Magento\Dhl\Model\Carrier as DhlCarrier;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;

/**
 * USPS shipping.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Carrier extends AbstractCarrierOnline implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * Code of the carrier.
     *
     * @var string
     */
    const CODE = 'endicia';

    /**
     * Code of the carrier.
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var [type]
     */
    protected $_uspsCarrier;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * [$_coreSession description]
     * @var [type]
     */
    protected $_coreSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var [type]
     */
    protected $_region;
    
    /**
     * @var LabelGenerator
     */
    protected $_labelGenerator;

    protected $_serviceCodeToActualNameMap = [];

    protected $_costArr = [];

    protected $storeManager;

    protected $_result;

    protected $_request;

    /**
     * Weight precision
     *
     * @var int
     */
    protected static $weightPrecision = 10;

    protected $containers = [
      'FLAT RATE BOX',
      'FLAT RATE ENVELOPE',
      'LEGAL FLAT RATE ENVELOPE',
      'PADDED FLAT RATE ENVELOPE',
      'SM FLAT RATE BOX',
      'MD FLAT RATE BOX',
      'LG FLAT RATE BOX'
    ];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param CarrierHelper $carrierHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Magento\Framework\DataObjectFactory $magentoFrameworkDataObject
     * @param \Magento\Usps\Model\Carrier $uspsCarrier
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Request\Http $requestParam
     * @param \Magento\Quote\Model\Quote\Item\OptionFactory $quoteOptionFactory
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Webkul\UspsEndiciaShipping\Logger\Logger $endiciaLogger
     * @param SessionManager $coreSession
     * @param LabelGenerator $labelGenerator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param EndiciaManagementInterfaceFactory $endiciaManagementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        CarrierHelper $carrierHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\DataObjectFactory $magentoFrameworkDataObject,
        \Magento\Usps\Model\Carrier $uspsCarrier,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $requestParam,
        \Magento\Quote\Model\Quote\Item\OptionFactory $quoteOptionFactory,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Webkul\UspsEndiciaShipping\Logger\Logger $endiciaLogger,
        SessionManager $coreSession,
        LabelGenerator $labelGenerator,
        \Magento\Customer\Model\Session $customerSession,
        EndiciaManagementInterfaceFactory $endiciaManagementFactory,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );

        $this->_magentoFrameworkDataObject = $magentoFrameworkDataObject;
        $this->_encryptor = $encryptor;
        $this->_uspsCarrier = $uspsCarrier;
        $this->_objectManager = $objectManager;
        $this->_endiciaLogger = $endiciaLogger;
        $this->_endiciaManagement = $endiciaManagementFactory->create();
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_carrierHelper = $carrierHelper;
        $this->_labelGenerator = $labelGenerator;
    }

    /**
     * Collect and get rates.
     *
     * @param RateRequest $request
     *
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Error|bool|Result
     */
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->canCollectRates()) {
            return false;
        }
        $this->setRequest($request);
        return $this->calculateRate();
    }

    /**
     * Prepare and set request to this instance
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return $this
     */
    public function setRequest(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $this->_request = $request;

        $r = new \Magento\Framework\DataObject();

        if ($request->getLimitMethod()) {
            $r->setService($request->getLimitMethod());
        } else {
            $r->setService('ALL');
        }

        $r->setEndiciaAccountId($this->getConfigData('accountid'));
        $r->setEndiciaPassphrase($this->getConfigData('passphrase'));

        if ($request->getHeight()) {
            $height = $request->getHeight();
        } else {
            $height = $this->getConfigData('height');
        }
        $r->setHeight($height);

        if ($request->getLength()) {
            $length = $request->getLength();
        } else {
            $length = $this->getConfigData('length');
        }
        $r->setLength($length);

        if ($request->getWidth()) {
            $width = $request->getWidth();
        } else {
            $width = $this->getConfigData('width');
        }
        $r->setWidth($width);

        $machinable = $this->getConfigData('machinable');
        $r->setMachinable($machinable);

        if ($request->getOrigPostcode()) {
            $r->setOrigPostal($request->getOrigPostcode());
        } else {
            $r->setOrigPostal(
                $this->_scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ZIP,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $request->getStoreId()
                )
            );
        }

        if ($request->getOrigCountryId()) {
            $r->setOrigCountryId($request->getOrigCountryId());
        } else {
            $r->setOrigCountryId(
                $this->_scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $request->getStoreId()
                )
            );
        }

        if ($request->getDestCountryId()) {
            $destCountry = $request->getDestCountryId();
        } else {
            $destCountry = UspsCarrier::USA_COUNTRY_ID;
        }

        $r->setDestCountryId($destCountry);

        if (!$this->_isUSCountry($destCountry)) {
            $r->setDestCountryName($this->_getCountryName($destCountry));
        }

        if ($request->getDestPostcode()) {
            $r->setDestPostal($request->getDestPostcode());
        }

        $weight = $this->getTotalNumOfBoxes($request->getPackageWeight());
        $r->setWeightPounds(floor($weight));
        
        $ounces = $weight < 1 ? ($weight - floor($weight)) * UspsCarrier::OUNCES_POUND:
            floor($weight)* UspsCarrier::OUNCES_POUND;
            
        $r->setWeightOunces(sprintf('%.' . self::$weightPrecision . 'f', $ounces));
        if ($request->getFreeMethodWeight() != $request->getPackageWeight()) {
            $r->setFreeMethodWeight($request->getFreeMethodWeight());
        }

        $r->setValue($request->getPackageValue());
        $r->setValueWithDiscount($request->getPackageValueWithDiscount());

        $r->setBaseSubtotalInclTax($request->getBaseSubtotalInclTax());

        $this->setRawRequest($r);

        return $this;
    }

    /**
     * set Defaul Usps Login Details
     * @param  \Magento\Framework\DataObject $request
     */
    protected function configUserInfo(\Magento\Framework\DataObject $request)
    {
        $request->setEndiciaAccountId($this->getConfigData('accountid'));
        $request->setEndiciaPassphrase($this->getConfigData('passphrase'));
    }

    /**
     * Set free method request
     *
     * @param string $freeMethod
     * @return void
     */
    protected function _setFreeMethodRequest($freeMethod)
    {
        $r = $this->_rawRequest;

        $weight = $this->getTotalNumOfBoxes($r->getFreeMethodWeight());
        $r->setWeightPounds(floor($weight));
        $ounces = ($weight - floor($weight)) * self::OUNCES_POUND;
        $r->setWeightOunces(sprintf('%.' . self::$weightPrecision . 'f', $ounces));
        $r->setService($freeMethod);
    }
    
    /**
     * {@inheritdoc}
     */
    public function calculateRate()
    {
        $request = $this->_rawRequest;
        
        $result = $this->_magentoFrameworkDataObject->create();

        $checkCountry = $this->checkCountry($request->getOrigCountryId());

        if (isset($checkcountry['hasError'])) {
            $result->setData(
                [
                    'error' => true,
                    'msg' => __('ORIGIN NOT US COUNTRY - Requested origin is not US country'),
                    'response' => $response
                ]
            );
            return $this->_parseEncidiaResponse($result);
        }
        $client = $this->_endiciaManagement->_createSoapClient(true);
        list($toZip5, $toZip4) = $this->_parseZip($request->getDestPostal());
        list($fromZip5, $fromZip4) = $this->_parseZip($request->getOrigPostal());
        $ounces = $request->getWeightOunces();
        
        $param = [
            'PostageRatesRequest' => [
                'ResponseVersion' => '1',
                'RequesterID'  => $this->_endiciaManagement->getRequesterId(),
                'CertifiedIntermediary' => [
                    'AccountID' => $request->getEndiciaAccountId(),
                    'PassPhrase' => $request->getEndiciaPassphrase()
                ],
                'MailClass'      => ($request->getDestCountryId()<>'US') ? 'International' : 'Domestic',
                'WeightOz'       => sprintf('%.' . self::$weightPrecision . 'f', $ounces),
                'MailpieceShape' => $this->getConfigData('mailpiece_shape'),
                'RegisteredMailValue' => $request->getValue(),
                'Value' => $request->getValue(),
                'Services' => [
                    '@attributes' => [
                        'SignatureConfirmation'   => 'OFF',
                        'COD'                     => 'OFF',
                        'DeliveryConfirmation'    => 'ON', // set default to on
                        'CertifiedMail'           => 'OFF',
                        'ElectronicReturnReceipt' => 'OFF',
                        'InsuredMail'             => $this->getInsuranceType($this->getConfigData('mailpiece_shape')),
                        'RestrictedDelivery'      => 'OFF',
                        'ReturnReceipt'           => 'OFF',
                        'AdultSignature'          => 'OFF',
                        'AdultSignatureRestrictedDelivery' => 'OFF',
                    ],
                ],
                'FromPostalCode' => $fromZip5,
                'ToPostalCode'   => $toZip5,
                'ToCountryCode'  => $request->getDestCountryId(),
                'Machinable'     => $this->getConfigData('machinable'),
                'CODAmount'      => '0',
                'InsuredValue'   => '0',
                'EstimatedDeliveryDate' => 'TRUE',
                'DeliveryTimeDays' => 'TRUE'
            ],
        ];
        
        try {
            $response = $client->CalculatePostageRates($param);
            
            if ($response->PostageRatesResponse->Status == 0) {
                $result->setData(['error' => false, 'msg' => '', 'response' => $response->PostageRatesResponse]);
            } else {
                $result->setData(
                    [
                        'error' => true,
                        'msg' => $response->PostageRatesResponse->ErrorMessage,
                        'response' => $response
                    ]
                );
            }
        } catch (\Exception $e) {
            $result->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
        return $this->_parseEncidiaResponse($result);
    }

    /**
     * Parse Endicia Response to get rates
     *
     * @param object $response
     * @return void
     */
    protected function _parseEncidiaResponse($response)
    {
        if ($response->getError()) {
            $this->_endiciaLogger->info('Rate Request Error - '.$response->getMsg());
            $debugData['result'] = ['error' => 1];
            return $this->_parseResponse($debugData);
        }
        $response = $response->getResponse();
        
        $allowedMethods = explode(',', $this->getAllowedMethods());
        
        $priceArr = [];
        if (is_object($response->PostagePrice) && is_object($response->PostagePrice->Postage)) {
            $postage = $response->PostagePrice->Postage;
            $serviceName = $postage->MailService;
            if ($postagePrice->DeliveryTimeDays != 0 && $this->getConfigData('show_delivery_day')) {
                $serviceName.= ' ('.__('Estimated Delivery-%1 days', $postagePrice->DeliveryTimeDays).')';
            }
            $serviceCode = $response->PostagePrice->MailClass;
            $this->_serviceCodeToActualNameMap[$serviceCode] = $serviceName;
            if (in_array($serviceCode, $allowedMethods)) {
                $this->_costArr[$serviceCode] = (string) $postage->TotalAmount;
                $priceArr[$serviceCode] = $this->getMethodPrice(
                    $postage->TotalAmount,
                    $serviceCode
                );
            }
            asort($priceArr);
        } else {
            foreach ($response->PostagePrice as $postagePrice) {
                if (is_object($postagePrice->Postage)) {
                    $postage = $postagePrice->Postage;
                    $serviceName = $postage->MailService;
                    if ($postagePrice->DeliveryTimeDays != 0 && $this->getConfigData('show_delivery_day')) {
                        $serviceName.= ' ('.__('Estimated Delivery-%1 days', $postagePrice->DeliveryTimeDays).')';
                    }
                    $serviceCode = $postagePrice->MailClass;
                    $this->_serviceCodeToActualNameMap[$serviceCode] = $serviceName;
                    if (in_array($serviceCode, $allowedMethods)) {
                        $this->_costArr[$serviceCode] = (string) $postage->TotalAmount;
                        $priceArr[$serviceCode] = $this->getMethodPrice(
                            $postage->TotalAmount,
                            $serviceCode
                        );
                    }
                }
            }
            asort($priceArr);
        }
        
        return $this->_parseResponse($priceArr);
    }
    
    /**
     * Parse calculated rates.
     *
     * @param string $response
     *
     * @return Result
     *
     * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
     */
    protected function _parseResponse($response)
    {
        $result = $this->_rateFactory->create();

        if (isset($response['result']['error'])) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
        } else {
            foreach ($response as $method => $price) {
                $rate = $this->_rateMethodFactory->create();
                $rate->setCarrier($this->_code);
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($method);
                $rate->setMethodTitle($this->_serviceCodeToActualNameMap[$method]);
                $rate->setCost($price);
                $rate->setPrice($price);
                $result->append($rate);
            }
        }

        return $result;
    }

    /**
     * Get allowed shipping methods.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->getConfigData('allowed_methods');
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return Result|null
     */
    public function getTracking($trackings)
    {
        $this->setTrackingRequest();

        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }

        $this->_getTracking($trackings);

        return $this->_result;
    }

    /**
     * Set tracking request
     *
     * @return void
     */
    protected function setTrackingRequest()
    {
        $request = $this->_magentoFrameworkDataObject->create();

        $this->configUserInfo($request);
        $this->_rawTrackRequest = $request;
    }

    /**
     * Get tracking request
     *
     * @param $trackings
     */
    protected function _getTracking($trackings)
    {
        $r = $this->_rawTrackRequest;
        $requesterId = $this->_endiciaManagement->getRequesterId();

        foreach ($trackings as $tracking) {
            $xml = $this->_xmlElFactory->create(
                ['data' => '<?xml version = "1.0" encoding = "UTF-8"?><PackageStatusRequest/>']
            );
            
            $xml->addChild('RequesterID', $requesterId);
            $xml->addChild('RequestID', substr(uniqid(rand(), true), 0, 10));
            $certified = $xml->addChild('CertifiedIntermediary');
            $certified->addChild('AccountID', $r->getEndiciaAccountId());
            $certified->addChild('PassPhrase', $r->getEndiciaPassphrase());
            $requestOptions = $xml->addChild('RequestOptions');
            $requestOptions->addAttribute('PackageStatus', 'COMPLETE');
            $picNumbers = $xml->addChild('PicNumbers');
            $picNumbers->addChild('PicNumber', $tracking);
            $requestXml = $xml->asXML();
        
            $debugData = ['request' => $requestXml];
            try {
                $client = $this->_endiciaManagement->_createSoapClient(true);
                $response = $client->StatusRequestXML(['PackageStatusRequestXML' => $requestXml]);
                $debugData['result'] = $response;
            } catch (\Exception $e) {
                $debugData['result'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
                $response = '';
            }
            $this->_debug($debugData);
            $this->_parseTrackingResponse($tracking, $response);
        }
    }

    /**
     * Parse tracking response
     *
     * @param string $trackingvalue
     * @param string $response
     * @return void
     */
    protected function _parseTrackingResponse($trackingvalue, $response)
    {
        $errorTitle = __('For some reason we can\'t retrieve tracking info right now.');
        $resultArr = [];
        if ($response->PackageStatusResponse->Status == 0) {
            if (isset($response->PackageStatusResponse->PackageStatus->StatusResponse)) {
                $resultArr = $response->PackageStatusResponse->PackageStatus->StatusResponse->PackageStatusEventList;
            } else {
                $errorTitle = __('Can\'t retrieve tracking info right now.');
            }
        }
        if (!$this->_result) {
            $this->_result = $this->_trackFactory->create();
        }

        $defaults = $this->getDefaults();

        if ($resultArr) {
            foreach ($resultArr as $key => $value) {
                $tracking = $this->_trackStatusFactory->create();
                $tracking->setCarrier('endicia');
                $tracking->setCarrierTitle($this->getConfigData('title'));
                $tracking->setTracking($trackingvalue);
                $tracking->setTrackSummary($value->TrackingSummary);
                $this->_result->append($tracking);
            }
        } else {
            $error = $this->_trackErrorFactory->create();
            $error->setCarrier('endicia');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setTracking($trackingvalue);
            $error->setErrorMessage($errorTitle);
            $this->_result->append($error);
        }
    }

    /**
     * Do shipment request to carrier web service,.
     *
     * @param \Magento\Framework\DataObject $request
     *
     * @return \Magento\Framework\DataObject
     */
    public function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $this->_prepareShipmentRequest($request);
        $this->_mapRequestToShipment($request);
        $this->configUserInfo($request);
        return $this->generateLabel($request);
    }

    /**
     * Generate Shipping Label
     *
     * @param \Magento\Framework\DataObject $request
     * @return object
     */
    public function generateLabel(\Magento\Framework\DataObject $request)
    {
        $result = $this->_magentoFrameworkDataObject->create();
        $service = $request->getShippingMethod();
        $recipientUSCountry = $this->_isUSCountry($request->getRecipientAddressCountryCode());
        $client = $this->_endiciaManagement->_createSoapClient(true);
        if ($recipientUSCountry && $service == 'Priority Express') {
            $requestXml = $this->_formUsSignatureConfirmationShipmentRequestData($request, $service);
        } else {
            if ($recipientUSCountry) {
                $requestXml = $this->_formUsSignatureConfirmationShipmentRequestData($request, $service);
            } else {
                $requestXml = $this->_formIntlShipmentRequestData($request);
            }
        }
        
        try {
            $response = $client->GetPostageLabelXML(['LabelRequestXML' => $requestXml]);
            if ($response->LabelRequestResponse->Status == 0) {
                $trackingNumber  = $response->LabelRequestResponse->TrackingNumber;
                if (isset($response->LabelRequestResponse->Label)) {
                    $labels = [];
                    foreach ($response->LabelRequestResponse->Label->Image as $labelImage) {
                         // @codingStandardsIgnoreStart
                         $labels[] = base64_decode((string) $labelImage->_);
                         // @codingStandardsIgnoreEnd
                    }
                    $labelContent = $this->_labelGenerator->combineLabelsPdf($labels)->render();
                } else {
                    // @codingStandardsIgnoreStart
                    $labelContent = base64_decode((string) $response->LabelRequestResponse->Base64LabelImage);
                    // @codingStandardsIgnoreEnd
                }
                $result->setShippingLabelContent($labelContent);
                $result->setTrackingNumber($trackingNumber);
            } else {
                $result->setErrors($response->LabelRequestResponse->ErrorMessage);
            }
        } catch (\Exception $e) {
            $result->setErrors($e->getMessage());
        }
        
        return $result;
    }

    /**
     * Map request to shipment
     *
     * @param \Magento\Framework\DataObject $request
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _mapRequestToShipment(\Magento\Framework\DataObject $rowRequest)
    {
        $rowRequest->setOrigCountryId($rowRequest->getShipperAddressCountryCode());
        $this->setRawRequest($rowRequest);
        $customsValue = 0;
        $packageWeight = 0;
        $totalValue = 0;
        $packages = $rowRequest->getPackages();
        
        foreach ($packages as &$piece) {
            $params = $piece['params'];
            if ($params['width'] || $params['length'] || $params['height']) {
                $minValue = $params['dimension_units'] == "CENTIMETER" ?
                DhlCarrier::DIMENSION_MIN_CM :DhlCarrier::DIMENSION_MIN_IN;
                if ($params['width'] < $minValue
                || $params['length'] < $minValue
                || $params['height'] < $minValue) {
                    $message = __('Height, width and length should be equal or greater than %1', $minValue);
                    throw new \Magento\Framework\Exception\LocalizedException($message);
                }
            }

            $weightUnit = $piece['params']['weight_units'];
            $customsValue += $piece['params']['customs_value'];
            $packageWeight += $piece['params']['weight'];
            foreach ($piece['items'] as $value) {
                $totalValue += $value['price'];
            }
        }

        $rowRequest->setPackages($packages)
            ->setPackageValue($totalValue)
            ->setTotalValue($totalValue)
            ->setValueWithDiscount($customsValue)
            ->setPackageCustomsValue($customsValue)
            ->setFreeMethodWeight(0);
    }

    /**
     * Package insurance type
     *
     * @param \Magento\Framework\DataObject $request
     * @return void
     */
    protected function getInsuranceType($mailPieceShape)
    {
        $insuredMail = 'OFF';
        if ($this->getConfigData('enable_insuredmail')) {
            $mailPieceShapes = explode(',', $this->getConfigData('ensured_mailpiece_shape'));
            if (in_array($mailPieceShape, $mailPieceShapes)) {
                $insuredMail = $this->getConfigData('insuredmail_type');
            }
        }
        return $insuredMail;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     * @param string $service
     * @return string
     */
    protected function _formUsSignatureConfirmationShipmentRequestData(\Magento\Framework\DataObject $request, $service)
    {
        if (!$service) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Service type does not match'));
        }

        $r = $request;
        $packageParams = $r->getPackageParams();
        $packageWeight = $r->getPackageWeight();
        $height = $packageParams->getHeight();
        $width = $packageParams->getWidth();
        $length = $packageParams->getLength();

        if ($packageParams->getWeightUnits() != \Zend_Measure_Weight::OUNCE) {
            $packageWeight = round(
                $this->_carrierHelper->convertMeasureWeight(
                    (float)$request->getPackageWeight(),
                    $packageParams->getWeightUnits(),
                    \Zend_Measure_Weight::OUNCE
                )
            );
        }

        if ($packageWeight == 0) {
            $packageWeight = 0.1;
        }
        list($fromZip5, $fromZip4) = $this->_parseZip($r->getShipperAddressPostalCode());
        list($toZip5, $toZip4) = $this->_parseZip($r->getRecipientAddressPostalCode(), true);
        $rootNode = 'LabelRequest';
        
       // the wrap node needs for remove xml declaration above
        $xmlWrap = $this->_xmlElFactory->create(['data' => '<?xml version = "1.0" encoding = "UTF-8"?><wrap/>']);
        $xml = $xmlWrap->addChild($rootNode);
        $xml->addAttribute('Test', 'No');
        $xml->addAttribute('LabelType', 'Default');
        $xml->addAttribute('LabelSize', 'DocTab');
        $xml->addAttribute('ImageFormat', 'PDF');
        $xml->addChild('RequesterID', $this->_endiciaManagement->getRequesterId());
        $xml->addChild('AccountID', $request->getEndiciaAccountId());
        $xml->addChild('PassPhrase', $request->getEndiciaPassphrase());
        $xml->addChild('MailClass', $service);
        $xml->addChild('DateAdvance', '0');
        $xml->addChild('WeightOz', $packageWeight);
        if ($service !== 'First') {
            $xml->addChild('MailpieceShape', $packageParams->getContainer());
        }
        if ($packageParams->getContainer() == 'LargeParcel') {
            $dimentionNode = $xml->addChild('MailpieceDimensions');
            $dimentionNode->addChild('Length', $length);
            $dimentionNode->addChild('Width', $width);
            $dimentionNode->addChild('Height', $height);
        }
        $xml->addChild('Stealth', 'FALSE');
        $serviceNode = $xml->addChild('Services');
        $serviceNode->addAttribute('InsuredMail', $this->getInsuranceType($packageParams->getContainer()));
        $serviceNode->addAttribute('COD', 'OFF');
        $serviceNode->addAttribute(
            'AdultSignature',
            $packageParams->getDeliveryConfirmation() == 'AdultSignature'?'ON':'OFF'
        );
        $serviceNode->addAttribute(
            'DeliveryConfirmation',
            $packageParams->getDeliveryConfirmation() == 'DeliveryConfirmation'?'ON':'OFF'
        );
        $serviceNode->addAttribute(
            'AdultSignatureRestrictedDelivery',
            $packageParams->getDeliveryConfirmation() == 'AdultSignatureRestrictedDelivery'?'ON':'OFF'
        );

        $getSignatureConfirmationFromPackage = $packageParams->getDeliveryConfirmation()=='False'?'ON':'OFF';
        $serviceNode->addAttribute(
            'SignatureConfirmation',
            $packageParams->getDeliveryConfirmation() == 'SignatureConfirmation'?'ON':'OFF'
        );
        $xml->addChild('Value', $request->getPackageValue());
        $xml->addChild('PartnerCustomerID', $request->getEndiciaAccountId());
        $xml->addChild('PartnerTransactionID', substr(uniqid(rand(), true), 0, 10));
        $xml->addChild('ToName', $request->getRecipientContactPersonName());
        $xml->addChild('ToCompany', $request->getRecipientContactCompanyName());
        $xml->addChild('ToAddress1', $request->getRecipientAddressStreet1());
        $xml->addChild('ToAddress2', $request->getRecipientAddressStreet1());
        $xml->addChild('ToCity', $request->getRecipientAddressCity());
        $xml->addChild('ToState', $request->getRecipientAddressStateOrProvinceCode());
        $xml->addChild('ToPostalCode', $toZip5);
        // $xml->addChild('ToZIP4', $toZip4);
        $xml->addChild('ToPhone', $r->getRecipientContactPhoneNumber());
        $xml->addChild('FromName', $r->getShipperContactPersonName());
        $xml->addChild('FromFirm', $r->getShipperContactCompanyName());
        $xml->addChild('ReturnAddress1', $r->getShipperAddressStreet1());
        $xml->addChild('ReturnAddress2', $r->getShipperAddressStreet2());
        $xml->addChild('FromCity', $r->getShipperAddressCity());
        $xml->addChild('FromState', $r->getShipperAddressStateOrProvinceCode());
        $xml->addChild('FromPostalCode', $fromZip5);
        $xml->addChild('FromZIP4', $fromZip4);
        $xml->addChild('FromPhone', $request->getShipperContactPhoneNumber());
        $responseOptions = $xml->addChild('ResponseOptions');
        $responseOptions->addAttribute('PostagePrice', "TRUE");
        $xml = $xmlWrap->{$rootNode}->asXML();
        return $xml;
    }

    /**
     * Form XML for international shipment request
     * As integration guide it is important to follow appropriate sequence for tags e.g.: <FromLastName /> must be
     * after <FromFirstName />.
     *
     * @param \Magento\Framework\DataObject $request
     *
     * @return string
     */
    protected function _formIntlShipmentRequestData(\Magento\Framework\DataObject $request)
    {
        $r = $request;
        $packageParams = $r->getPackageParams();
        $height = $packageParams->getHeight();
        $width = $packageParams->getWidth();
        $length = $packageParams->getLength();
        $girth = $packageParams->getGirth();
        $packageWeight = $r->getPackageWeight();
        list($pounds, $ounces) = $this->getPoundsAndOunces($packageWeight, $packageParams, $r);

        if ($packageParams->getDimensionUnits() != \Zend_Measure_Length::INCH) {
            $length = round(
                $this->_carrierHelper->convertMeasureDimension(
                    $packageParams->getLength(),
                    $packageParams->getDimensionUnits(),
                    \Zend_Measure_Length::INCH
                )
            );
            $width = round(
                $this->_carrierHelper->convertMeasureDimension(
                    $packageParams->getWidth(),
                    $packageParams->getDimensionUnits(),
                    \Zend_Measure_Length::INCH
                )
            );
            $height = round(
                $this->_carrierHelper->convertMeasureDimension(
                    $packageParams->getHeight(),
                    $packageParams->getDimensionUnits(),
                    \Zend_Measure_Length::INCH
                )
            );
        }
        if ($packageParams->getGirthDimensionUnits() != \Zend_Measure_Length::INCH) {
            $girth = round(
                $this->_carrierHelper->convertMeasureDimension(
                    $packageParams->getGirth(),
                    $packageParams->getGirthDimensionUnits(),
                    \Zend_Measure_Length::INCH
                )
            );
        }

        $method = '';
        $shippingMethod = $request->getShippingMethod();
        $service = $this->getServiceCode('service_to_code', $shippingMethod);
        $method = $this->getServiceMethod($service);
        
        list($fromZip5, $fromZip4) = $this->_parseZip($r->getShipperAddressPostalCode());
        list($toZip5, $toZip4) = $this->_parseZip($r->getRecipientAddressPostalCode(), true);

        $rootNode = 'LabelRequest';
        
       // the wrap node needs for remove xml declaration above
        $xmlWrap = $this->_xmlElFactory->create(['data' => '<?xml version = "1.0" encoding = "UTF-8"?><wrap/>']);
        $xml = $xmlWrap->addChild($rootNode);
        $xml = $this->checkTestMode($xml);
        
        $xml->addAttribute('LabelType', 'International');
        $xml->addAttribute('LabelSubtype', 'Integrated');
        $xml->addAttribute('LabelSize', '6x4');
        $xml->addAttribute('ImageFormat', 'PDF');
        $xml->addChild('RequesterID', $this->_endiciaManagement->getRequesterId());
        $xml->addChild('AccountID', $request->getEndiciaAccountId());
        $xml->addChild('PassPhrase', $request->getEndiciaPassphrase());
        $xml->addChild('MailClass', $method);
        $xml->addChild('DateAdvance', '0');
        $xml->addChild('WeightOz', $packageWeight);
        $xml->addChild('MailpieceShape', $packageParams->getContainer());
        $serviceNode = $xml->addChild('Services');
        $serviceNode->addAttribute('InsuredMail', $this->getInsuranceType($packageParams->getContainer()));
        $serviceNode->addAttribute('COD', 'OFF');
        $serviceNode->addAttribute('CertifiedMail', 'OFF');
        $serviceNode->addAttribute('DeliveryConfirmation', 'OFF');
        $serviceNode->addAttribute('ElectronicReturnReceipt', 'OFF');
        $xml->addChild('Value', $request->getPackageValue());
        $xml->addChild('PartnerCustomerID', $request->getEndiciaAccountId());
        $xml->addChild('PartnerTransactionID', substr(uniqid(rand(), true), 0, 10));
        $xml->addChild('CustomsCertify', 'TRUE');
        $xml->addChild('CustomsSigner', $r->getShipperContactPersonName());
        $shippingContents = $xml->addChild('CustomsInfo');
        if ($packageParams->getContentType() == 'OTHER' && $packageParams->getContentTypeOther() != null) {
            $shippingContents->addChild('ContentsType', $packageParams->getContentType());
            $shippingContents->addChild('ContentsExplanation ', $packageParams->getContentTypeOther());
        } else {
            $shippingContents->addChild('ContentsType', $packageParams->getContentType());
        }

        $packageItems = $r->getPackageItems();
        // get countries of manufacture
        $countriesOfManufacture = [];
        $productIds = [];
        foreach ($packageItems as $itemShipment) {
            $item = $this->_magentoFrameworkDataObject->create();
            $item->setData($itemShipment);

            $productIds[] = $item->getProductId();
        }
        $productCollection = $this->_productCollectionFactory->create()->addStoreFilter(
            $r->getStoreId()
        )->addFieldToFilter(
            'entity_id',
            ['in' => $productIds]
        )->addAttributeToSelect(
            'country_of_manufacture'
        );
        foreach ($productCollection as $product) {
            $countriesOfManufacture[$product->getId()] = $product->getCountryOfManufacture();
        }

        $packagePoundsWeight = $packageOuncesWeight = 0;
        $customItems = $shippingContents->addChild('CustomsItems');
        // for ItemDetail
        foreach ($packageItems as $itemShipment) {
            $item = $this->_magentoFrameworkDataObject->create();
            $item->setData($itemShipment);

            $itemWeight = $item->getWeight() * $item->getQty();
            if ($packageParams->getWeightUnits() != \Zend_Measure_Weight::POUND) {
                $itemWeight = $this->_carrierHelper->convertMeasureWeight(
                    $itemWeight,
                    $packageParams->getWeightUnits(),
                    \Zend_Measure_Weight::POUND
                );
            }
            $countryOfManufacture = $this->getCountryOfManufacture($item->getProductId(), $countriesOfManufacture);
            
            $itemDetail = $customItems->addChild('CustomsItem');
            $itemDetail->addChild('Description', $item->getName());
            $ceiledQty = ceil($item->getQty());
            if ($ceiledQty < 1) {
                $ceiledQty = 1;
            }
            $individualItemWeight = $itemWeight / $ceiledQty;
            $itemDetail->addChild('Quantity', $ceiledQty);
            $itemDetail->addChild('Value', sprintf('%.2F', $item->getCustomsValue() * $item->getQty()));
            $listOfWeightInPounds = $this->_mpconvertPoundOunces($individualItemWeight);
            list($individualPoundsWeight, $individualOuncesWeight) = $listOfWeightInPounds;
            $itemDetail->addChild('Weight', $packageWeight);
            $itemDetail->addChild('CountryOfOrigin', 'US');
        }

        $responseOptions = $xml->addChild('ResponseOptions');
        $responseOptions->addAttribute('PostagePrice', "TRUE");

        $xml->addChild('FromName', $r->getShipperContactPersonName());
        $xml->addChild('FromCompany', $r->getShipperContactCompanyName());
        $xml->addChild('ReturnAddress1', $r->getShipperAddressStreet1());
        $xml->addChild('ReturnAddress2', $r->getShipperAddressStreet1());
        $xml->addChild('FromCity', $r->getShipperAddressCity());
        $xml->addChild('FromState', $r->getShipperAddressStateOrProvinceCode());
        $xml->addChild('FromPostalCode', $fromZip5);
        $xml->addChild('FromPhone', $request->getShipperContactPhoneNumber());
        $xml->addChild('ToName', $request->getRecipientContactPersonName());
        if ($r->getRecipientContactCompanyName()) {
            $temp = $r->getRecipientContactCompanyName();
        } else {
            $temp = $r->getRecipientContactPersonName();
        }
        $xml->addChild('ToCompany', $temp);
        $xml->addChild('ToAddress1', $request->getRecipientAddressStreet1());
        $xml->addChild('ToAddress2', $request->getRecipientAddressStreet2());
        $xml->addChild('ToCity', $request->getRecipientAddressCity());
        $xml->addChild('ToState', $request->getRecipientAddressStateOrProvinceCode());
        $xml->addChild('ToPostalCode', $toZip5);
        $xml->addChild('ToCountryCode', $r->getRecipientAddressCountryCode());
        $xml->addChild('ToCountry', $this->_getCountryName($r->getRecipientAddressCountryCode()));
        $xml->addChild('ToPhone', $r->getRecipientContactPhoneNumber());
        $xml = $xmlWrap->{$rootNode}->asXML();
        
        return $xml;
    }

    /**
     * Get service value
     *
     * @param string $service
     * @return string $service
     */
    public function getServiceValue($service)
    {
        switch ($service) {
            case 'Priority Mail':
            case 'Priority':
                $service = 'Priority';
                break;
            case 'Priority Mail Express':
                $service = 'PriorityExpress';
                break;
            case 'FIRST CLASS':
            case 'First Class':
                $service = 'First';
                break;
            case 'MEDIA':
            case 'Media':
                $service = 'MediaMail';
                break;
            case 'Parcel Select':
                $service = 'ParcelSelect';
                break;
            case 'Library':
                $service = 'LibraryMail';
                break;
            default:
                return false;
        }
        return $service;
    }

    /**
     * Get Service code
     *
     * @param $type
     * @param string $code
     * @return bool|$codes
     */
    public function getServiceCode($type, $code = '')
    {
        $codes = [
            'service_to_code' => [
                'First' => 'First Class Package Service',
                'PriorityExpress' => 'Priority Mail Express',
                'Priority' => 'Priority Mail',
                'MediaMail' => 'Media',
                'LibraryMail' => 'Library',
                'FirstClassPackageInternationalService' => 'First Class Package International Service',
                'PriorityMailInternational' => 'Priority',
                'GXG' => 'Global Express Guaranteed',
                'ExpressMailInternational' => 'Priority Mail Express International',
                'ParcelSelect' => 'Parcel Select',
                'EndiciaGlobalService' => 'Endicia Global Service'
            ],
            'container' => [
                'IrregularParcel' => __('IrregularParcel'),
                'Letter' => __('Letter'),
                'Parcel' => __('Parcel'),
                'LargeParcel' => __('Large Parcel'),
                'Card' => __('Card'),
                'Flat' => __('Flat'),
                'FlatRateCardboardEnvelope' => __('FlatRateCardboardEnvelope'),
                'FlatRateWindowEnvelope' => __('FlatRateWindowEnvelope'),
                'FlatRateEnvelope' => __('Flat Rate Envelope'),
                'SmallFlatRateEnvelope' => __('Small Flat-Rate Envelope'),
                'SmallFlatRateBox' => __('Small Flat-Rate Box'),
                'MediumFlatRateBox' => __('Medium Flat-RateBox'),
                'LargeFlatRateBox' => __('Large Flat-Rate Box'),
                'FlatRateLegalEnvelope' => __('Legal Flat-Rate Envelope'),
                'FlatRatePaddedEnvelope' => __('Padded Flat-Rate Envelope'),
                'FlatRateGiftCardEnvelope' => __('Gift Card Flat-Rate Envelope'),
                'RegionalRateBoxA' => __('Regional Rate BoxA'),
                'RegionalRateBoxB' => __('Regional Rate BoxB'),
                'Documents' => __('Documents'),
            ],
            'containers_filter' => [
                [
                    'containers' => ['Card'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'First'
                            ],
                        ],
                        'from_us' => [
                            'method' => [],
                        ],
                    ],
                ],
                [
                    'containers' => ['Flat'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'First'
                            ],
                        ],
                        'from_us' => [
                            'method' => [],
                        ],
                    ],
                ],
                [
                    'containers' => ['Letter'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'First','PriorityExpress'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'FirstClassPackageInternationalService',
                                'ExpressMailInternational',
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['Parcel'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'First', 'PriorityExpress', 'Priority', 'LibraryMail', 'ParcelSelect', 'MediaMail'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'FirstClassPackageInternationalService',
                                'PriorityMailInternational',
                                'ExpressMailInternational',
                                'Endicia Global Service'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['FlatRateEnvelope'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityMail','PriorityExpress'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational',
                                'FirstClassPackageInternationalService'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['LargeParcel'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority', 'ParcelSelect','PriorityExpress'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'FirstClassPackageInternationalService',
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['SmallFlatRateEnvelope'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress',
                                'FirstClassPackageInternationalService'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['MediumFlatRateBox'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress','FirstClassPackageInternationalService'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['LargeFlatRateBox'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress','FirstClassPackageInternationalService'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['FlatRateLegalEnvelope'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress','FirstClassPackageInternationalService'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['FlatRatePaddedEnvelope'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress','FirstClassPackageInternationalService'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['FlatRateGiftCardEnvelope'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress','FirstClassPackageInternationalService'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                'PriorityMailInternational',
                                'ExpressMailInternational'
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['RegionalRateBoxA'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                ''
                            ],
                        ],
                    ],
                ],
                [
                    'containers' => ['RegionalRateBoxB'],
                    'filters' => [
                        'within_us' => [
                            'method' => [
                                'Priority','PriorityExpress'
                            ],
                        ],
                        'from_us' => [
                            'method' => [
                                ''
                            ],
                        ],
                    ],
                ],
            ],
            'us_services_types' => [
                'False' => __('Not Required'),
                'AdultSignature' => __('Adult Signature'),
                'AdultSignatureRestrictedDelivery' => __('Adult Signature Restricted Delivery'),
                'SignatureConfirmation' => __('Signature Confirmation'),
                'DeliveryConfirmation' => __('Delivery Confirmation')
            ],
        ];
        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }
        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    /**
     * Convert decimal weight into pound-ounces format
     *
     * @param float $weightInPounds
     * @return float[]
     */
    protected function _mpconvertPoundOunces($weightInPounds)
    {
        $weightInOunces = ceil($weightInPounds * UspsCarrier::OUNCES_POUND);
        $pounds = floor($weightInOunces / UspsCarrier::OUNCES_POUND);
        $ounces = $weightInOunces % UspsCarrier::OUNCES_POUND;
        return [$pounds, $ounces];
    }

    /**
     * Return content types of package
     *
     * @param \Magento\Framework\DataObject $params
     * @return array
     */
    public function getContentTypes(\Magento\Framework\DataObject $params)
    {
        return $this->_uspsCarrier->getContentTypes($params);
    }

    /**
     * Return container types of carrier
     *
     * @param \Magento\Framework\DataObject|null $params
     * @return array|bool
     */
    public function getContainerTypes(\Magento\Framework\DataObject $params = null)
    {
        if ($params === null) {
            return $this->_getAllowedContainers();
        }

        return $this->_getAllowedContainers($params);
    }

    /**
     * Return all container types of carrier
     *
     * @return array|bool
     */
    public function getContainerTypesAll()
    {
        return $this->getServiceCode('container');
    }

    /**
     * Return structured data of containers witch related with shipping methods
     *
     * @return array|bool
     */
    public function getContainerTypesFilter()
    {
        return $this->getServiceCode('containers_filter');
    }

    /**
     * Return delivery confirmation types of carrier.
     *
     * @param \Magento\Framework\DataObject|null $params
     *
     * @return array
     */
    public function getDeliveryConfirmationTypes(\Magento\Framework\DataObject $params = null)
    {
        if ($params == null) {
            return [];
        }
        $countryRecipient = $params->getCountryRecipient();
        if ($this->_isUSCountry($countryRecipient)) {
            return $this->getServiceCode('us_services_types');
        } else {
            return [];
        }
    }

    /**
     * Validate
     *
     * @param \Magento\Framework\DataObject $request
     * @return bool
     */
    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return true;
    }

    /**
     * Get converted weight
     *
     * @param int $packageWeight
     * @param object $packageParams
     * @param \Magento\Framework\DataObject $r
     */
    public function getPoundsAndOunces($packageWeight, $packageParams, $r)
    {
        list($pounds, $ounces) = $this->_mpconvertPoundOunces($packageWeight);

        if ($packageParams->getWeightUnits() != \Zend_Measure_Weight::POUND) {
            $packageWeight = $this->_carrierHelper->convertMeasureWeight(
                $r->getPackageWeight(),
                $packageParams->getWeightUnits(),
                \Zend_Measure_Weight::POUND
            );
            list($pounds, $ounces) = $this->_mpconvertPoundOunces($packageWeight);
        }
    }

    /**
     * Get service method
     *
     * @param string $service
     * @return string
     */
    public function getServiceMethod($service)
    {
        if ($service == 'Priority') {
            return $method = 'PriorityMailInternational';
        } else {
            if ($service == 'First Class') {
                return $method = 'FirstClassPackageInternationalService';
            } else {
                return $method = 'ExpressMailInternational';
            }
        }
    }

    /**
     * Check test/sandbox mode
     *
     * @param array $xml
     * @return array
     */
    public function checkTestMode($xml)
    {
        if (!$this->getConfigData('mode')) {
            $xml->addAttribute('Test', 'Yes');
        }
        return $xml;
    }

    /**
     * Get Country Of Manufacture
     *
     * @param int $id
     * @param array $countriesOfManufacture
     */
    public function getCountryOfManufacture($id, $countriesOfManufacture)
    {
        if (!empty($countriesOfManufacture[$id])) {
            $countryOfManufacture = $this->_getCountryName($countriesOfManufacture[$id]);
        } else {
            $countryOfManufacture = '';
        }
    }

    /**
     * Orgin country check, it must be US country
     *
     * @param string $countryId
     * @return bool
     */
    public function checkCountry($countryId)
    {
        if (!$this->_isUSCountry($countryId)) {
            return  [
                'hasError' => true
            ];
        }
        return true;
    }

    /**
     * Return Country Name
     *
     * @param string $countryCode
     * @return string
     */
    public function _getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * Parse zip from string to zip5-zip4
     *
     * @param string $zipString
     * @param bool $returnFull
     * @return string[]
     */
    protected function _parseZip($zipString, $returnFull = false)
    {
        $zip4 = '';
        $zip5 = '';
        $zip = [$zipString];
        if (preg_match('/[\\d\\w]{5}\\-[\\d\\w]{4}/', $zipString) != 0) {
            $zip = explode('-', $zipString);
        }
        $zipCount = count($zip);
        for ($i = 0; $i < $zipCount; ++$i) {
            if (strlen($zip[$i]) == 5) {
                $zip5 = $zip[$i];
            } elseif (strlen($zip[$i]) == 4) {
                $zip4 = $zip[$i];
            }
        }
        if (empty($zip5) && empty($zip4) && $returnFull) {
            $zip5 = $zipString;
        }

        return [$zip5, $zip4];
    }
}
