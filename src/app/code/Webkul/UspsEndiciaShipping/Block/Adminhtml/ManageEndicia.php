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

namespace Webkul\UspsEndiciaShipping\Block\Adminhtml;

class ManageEndicia extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
 
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Webkul\UspsEndiciaShipping\Helper\Data $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
 
    /**
     * Initialize cms page edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Webkul_UspsEndiciaShipping';
        $this->_controller = 'adminhtml_index';
 
        parent::_construct();
 
        $this->buttonList->remove('save');
        $this->buttonList->remove('back');
        $this->buttonList->remove('delete');
    }
 
    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return $this;
    }
 
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    public function isAccountExists()
    {
        return $this->helper->getConfigData('accountid');
    }

    /**
     * Prepare form Html. call the phtm file with form.
     *
     * @return string
     */
    public function getFormHtml()
    {
       // get the current form as html content.
        $html = parent::getFormHtml();
        //Append the phtml file after the form content.
        $html .= $this->setTemplate('Webkul_UspsEndiciaShipping::endicia_account.phtml')->toHtml();
        return $html;
    }

    public function getStatusUrl()
    {
        return $this->getUrl('endicia/account/accountstatus', ['_secure' => true]);
    }

    public function getBuyPostageUrl()
    {
        return $this->getUrl('endicia/account/buypostage', ['_secure' => true]);
    }
 
    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
 
        $this->_formScripts[] = "
            require([
                'jquery',
                'mage/mage',
                'knockout'
            ], function ($){
                 $('#edit_form').append($('.endicia-account-container'));
            });
                
        ";
        return parent::_prepareLayout();
    }
}
