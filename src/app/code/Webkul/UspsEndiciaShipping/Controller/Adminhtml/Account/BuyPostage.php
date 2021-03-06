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
namespace Webkul\UspsEndiciaShipping\Controller\Adminhtml\Account;

use Webkul\UspsEndiciaShipping\Api\EndiciaManagementInterfaceFactory;

class BuyPostage extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected $endiciaManagementFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        EndiciaManagementInterfaceFactory $endiciaManagementFactory
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_endiciaManagementFactory = $endiciaManagementFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        $params = $this->getRequest()->getParams();
        if (!isset($params['amount'])) {
            return $resultJson->setData(
                [
                    'error' => true,
                    'msg' => __('Enter Postage Amount to Buy'),
                    'response' => ''
                ]
            );
        }

        try {
            $response = $this->_endiciaManagementFactory->create()->buyPostage($params['amount']);
            return $resultJson->setData($response);
        } catch (\Exception $e) {
            return $resultJson->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
    }
}
