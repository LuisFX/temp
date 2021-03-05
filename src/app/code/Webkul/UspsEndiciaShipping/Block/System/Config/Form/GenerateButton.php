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
namespace Webkul\UspsEndiciaShipping\Block\System\Config\Form;

class GenerateButton extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = 'system/config/generate_button.phtml';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Webkul\UspsEndiciaShipping\Model\ChatDataConfigProvider
     */
    protected $configProvider;

    /**
     * Agreement constructor
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Artera\Privacy\Model\Agreement                  $agreement
     * @param \Artera\Privacy\Model\Page                       $page
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * Set template to itself.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }

        return $this;
    }
    /**
     * Render button.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Return ajax url for button.
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('endicia/account/newpassphrase');
    }
    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(
            [
                'id' => 'generate_button',
                'onclick' => 'javascript:check(); return false;',
            ]
        );

        return $this->_toHtml();
    }

    public function getButtonLabel()
    {
        return __('Generate New Passphrase');
    }
}
