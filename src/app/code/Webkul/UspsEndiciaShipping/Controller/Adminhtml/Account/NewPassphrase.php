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

class NewPassphrase extends \Magento\Backend\App\Action
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
        $passphrase = $this->getRequest()->getParam('passphrase');
        if ($passphrase == '') {
            $passphrase = $this->generatePassphrase();
        }
        try {
            $response = $this->_endiciaManagementFactory->create()->generateNewPassphrase($passphrase);
            return $resultJson->setData($response);
        } catch (\Exception $e) {
            return $resultJson->setData(['error' => true, 'msg' => $e->getMessage(), 'passphrase' => '']);
        }
    }

    /**
     * Generate Passphrase
     *
     * @return string
     */
    public function generatePassphrase()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pass = [];
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 15; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }
}
