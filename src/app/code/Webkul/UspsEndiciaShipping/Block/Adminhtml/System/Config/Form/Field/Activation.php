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
namespace Webkul\UspsEndiciaShipping\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Temando\Shipping\Model\Config\ModuleConfigInterface;

/**
 * UspsEndiciaShipping Config Activation Info Block
 */
class Activation extends Field
{
    /**
     * @var string
     */
    protected $_template = 'system/config/form/field/activation.phtml';

    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * Activation constructor.
     *
     * @param Context $context
     * @param ModuleConfigInterface $moduleConfig
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        ModuleConfigInterface $moduleConfig,
        array $data = []
    ) {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        if (!$this->getAuthorization()->isAllowed('Webkul_UspsEndiciaShipping::manager')) {
            return '';
        }

        $html = sprintf(
            '<td colspan="%d" id="%s">%s</td>',
            3 + (int)$this->_isInheritCheckboxRequired($element),
            $element->getHtmlId(),
            $this->_renderValue($element)
        );

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Obtain the URL to redirect the user into the Shipping Portal account.
     *
     * @return string
     */
    public function getAccountRedirectUrl(): string
    {
        return $this->_urlBuilder->getUrl('temando/configuration_portal/account');
    }
}
