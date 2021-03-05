<?php
/**
 * Webkul Software.
 *
 * @category Webkul
 * @package Webkul_UspsEndiciaShipping
 * @author Webkul
 * @copyright Copyright (c) WebkulSoftware Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 *
 */
namespace Webkul\UspsEndiciaShipping\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Dir;
use Webkul\UspsEndiciaShipping\Api\EndiciaManagementInterface;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Sales\Model\Order\Shipment;

/**
 * Handle various endicia api requests
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EndiciaManagement implements EndiciaManagementInterface
{

    /**
     * Path to wsdl file of endicia service
     *
     * @var string
     */
    protected $_endiciaWsdl = null;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var string
     */
    protected $_accountId;

    /**
     * @var string
     */
    protected $_passphrase;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerFactory;

    /**
     * @var string
     */
    private $_requesterId = 'lwkt';

    /**
     * @var string
     */
    private $_productionUrl = 'https://LabelServer.Endicia.com/LabelService/EwsLabelService.asmx?WSDL';

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = 'endicia';

    public function __construct(
        \Magento\Framework\Module\Dir\Reader $configReader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\App\Request\Http $requestParam,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\DataObjectFactory $magentoFrameworkDataObject,
        LabelGenerator $labelGenerator,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->_jsonHelper = $jsonHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_magentoFrameworkDataObject = $magentoFrameworkDataObject;
        $this->_requestParam = $requestParam;
        $this->_objectManager = $objectManager;
        $this->_region = $regionFactory;
        $this->_customerFactory = $customerFactory;
        $this->_labelGenerator = $labelGenerator;
        $wsdlBasePath = $configReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Webkul_UspsEndiciaShipping') . '/wsdl/';
        $this->_endiciaWsdl = $wsdlBasePath . 'endicia.wsdl';
        $this->setupDefaultCredentials();
    }

    /**
     * Set Default Account Dtails
     *
     * @return void
     */
    public function setupDefaultCredentials()
    {
        $this->_accountId = $this->getConfigData('accountid');
        $this->_passphrase = $this->getConfigData('passphrase');
    }

    /**
     * Get Current Store
     */
    public function getStore()
    {
        return $this->_storeManagerInterface->getStore();
    }

    /**
     * Retrieve information from carrier configuration
     *
     * @param   string $field
     * @return  void|false|string
     */
    public function getConfigData($field)
    {
        if (empty($this->_code)) {
            return false;
        }
        $path = 'carriers/' . $this->_code . '/' . $field;

        return $this->_scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()
        );
    }

    /**
     * Endicia Request Id
     *
     * @return string
     */
    public function getRequesterId()
    {
        return $this->_requesterId;
    }

    /**
     * Create soap client with selected wsdl
     *
     * @param string $wsdl
     * @param bool|int $trace
     * @return \SoapClient
     */
    public function _createSoapClient($trace = false)
    {
        $url = $this->_productionUrl;
        $client = new \SoapClient($url, ['trace' => $trace]);

        return $client;
    }

    /**
     * Generate New Passpharase
     *
     * @param string $passphrase
     * @return object
     */
    public function generateNewPassphrase($passphrase)
    {
        $client = $this->_createSoapClient(true);
        $result = $this->_magentoFrameworkDataObject->create();

        $param = [
        'ChangePassPhraseRequest' => [
                'RequesterID' => $this->getRequesterId(),
                'RequestID' => rand(10000, 999999),
                    'CertifiedIntermediary' => [
                        'AccountID' => $this->_accountId,
                        'PassPhrase' => $this->_passphrase
                    ],
                'NewPassPhrase' => $passphrase
            ]
        ];
        
        try {
            $response = $client->ChangePassPhrase($param);

            if ($response->ChangePassPhraseRequestResponse->Status == 0) {
                $result->setData(['error' => false, 'msg' => '', 'passphrase' => $passphrase]);
            } else {
                $result->setData(
                    [
                        'error' => true,
                        'msg' => $response->ChangePassPhraseRequestResponse->ErrorMessage,
                        'response' => $response
                    ]
                );
            }
        } catch (\Exception $e) {
            $result->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
        return $result;
    }

    /**
     * Retrieve endicia account information
     *
     * @param string $accountInfo
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAccountStatus()
    {
        $client = $this->_createSoapClient(true);
        $result = $this->_magentoFrameworkDataObject->create();
        $param = [
          'AccountStatusRequest' => [
              'RequesterID' => $this->getRequesterId(),
              'RequestID' => rand(10000, 999999),
                  'CertifiedIntermediary' => [
                      'AccountID' => $this->_accountId,
                      'PassPhrase' => $this->_passphrase
                  ]
            ],
            'Test' => 'NO'
        ];
        
        try {
            $response = $client->GetAccountStatus($param);
            
            if ($response->AccountStatusResponse->Status == 0) {
                $result->setData(['error' => false, 'msg' => '', 'response' => $response->AccountStatusResponse]);
            } else {
                $result->setData(
                    [
                        'error' => true,
                        'msg' => $response->AccountStatusResponse->ErrorMessage,
                        'response' => $response
                    ]
                );
            }
        } catch (\Exception $e) {
            $result->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function buyPostage($amount)
    {
        $client = $this->_createSoapClient(true);
        $result = $this->_magentoFrameworkDataObject->create();
        $param = [
            'RecreditRequest' => [
                'RequesterID' => $this->getRequesterId(),
                'RequestID' => time(),
                'CertifiedIntermediary' => [
                'AccountID' => $this->_accountId,
                    'PassPhrase' => $this->_passphrase
                ],
                'RecreditAmount' => $amount,
            ]
        ];
        try {
            $response = $client->BuyPostage($param);

            if ($response->RecreditRequestResponse->Status == 0) {
                $result->setData(['error' => false, 'msg' => '', 'response' => $response->RecreditRequestResponse]);
            } else {
                $result->setData(
                    [
                        'error' => true,
                        'msg' => $response->RecreditRequestResponse->ErrorMessage,
                        'response' => $response
                    ]
                );
            }
        } catch (\Exception $e) {
            $result->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getRefundPostage($trackings)
    {
        $client = $this->_createSoapClient(true);
        $result = $this->_magentoFrameworkDataObject->create();
        $param = [
            'RefundRequest' => [
                'RequesterID' => $this->getRequesterId(),
                'RequestID' => time(),
                'CertifiedIntermediary' => [
                    'AccountID' => $this->_accountId,
                    'PassPhrase' => $this->_passphrase
                ],
                'PicNumbers' => $trackings,
            ]
        ];
        try {
            $response = $client->GetRefund($param);
            if (isset($response->RefundResponse->Refund)) {
                $result->setData(['error' => false, 'msg' => '', 'response' => $response->RefundResponse->Refund]);
            } else {
                $result->setData(
                    [
                        'error' => true,
                        'msg' => $response->RefundResponse->ErrorMessage,
                        'response' => $response
                    ]
                );
            }
        } catch (\Exception $e) {
            $result->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomAttributesMetadata($dataObjectClassName = null)
    {
        return [];
    }
}
