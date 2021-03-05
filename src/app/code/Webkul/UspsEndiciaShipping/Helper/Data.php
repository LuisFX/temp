<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_UspsEndiciaShipping
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\UspsEndiciaShipping\Helper;

use Magento\Customer\Model\Session;

/**
 * Endicia Helper Email.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $_template;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param Magento\Framework\App\Helper\Context              $context
     * @param Magento\Framework\ObjectManagerInterface          $objectManager
     * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param Session                                           $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $ordersModel,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollection,
        Session $customerSession
    ) {
        $this->backendUrl = $backendUrl;
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_customerSession = $customerSession;
        $this->_objectManager = $objectManager;
        $this->_ordersModel = $ordersModel;
        $this->_storeManager = $storeManager;
        $this->_shipmentCollection = $shipmentCollection;
    }

    /**
     * Return store configuration value.
     *
     * @param string $path
     * @param int    $storeId
     *
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve information from carrier configuration.
     *
     * @param string $field
     *
     * @return void|false|string
     */
    public function getConfigData($field)
    {
        $path = 'carriers/endicia/'.$field;

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()
        );
    }

    /**
     * Return store.
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    public function getShipmentsByOrderId($order_id)
    {
        $shipments = $this->_shipmentCollection->create()
            ->addFieldToFilter('order_id', $order_id)
            ->setOrder('entity_id', 'DESC');
    }
}
