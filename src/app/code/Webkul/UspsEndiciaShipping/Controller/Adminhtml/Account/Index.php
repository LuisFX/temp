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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
     /**
      * @var \Magento\Framework\View\Result\PageFactory
      */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {

        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_UspsEndiciaShipping::manager');
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Webkul_UspsEndiciaShipping::postage');
        $resultPage->getConfig()->getTitle()->prepend(
            __('Manage Endicia Account')
        );
        $resultPage->addBreadcrumb(
            __('Manage Endicia Account'),
            __('Manage Endicia Account')
        );
        return $resultPage;
    }
}
