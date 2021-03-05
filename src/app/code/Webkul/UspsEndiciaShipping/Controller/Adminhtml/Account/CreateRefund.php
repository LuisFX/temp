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
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory;

class CreateRefund extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var EndiciaManagementInterfaceFactory
     */
    protected $endiciaManagementFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        EndiciaManagementInterfaceFactory $endiciaManagementFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_endiciaManagementFactory = $endiciaManagementFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        $params = $this->getRequest()->getParams();
        if (!isset($params['tracking'])) {
            return $resultJson->setData(
                [
                    'error' => true,
                    'msg' => __('Please select tracking number(s)'),
                    'response' => ''
                ]
            );
        }
    
        try {
            $response = $this->_endiciaManagementFactory->create()->getRefundPostage($params['tracking']);
            $updateTrackings = [];
            
            if (isset($response['response']) && is_object($response['response'])) {
                $responseData = $response['response'];
                if ($responseData->RefundStatus === 'Approved') {
                    $updateTrackings[] = $responseData->PicNumber;
                }
            } elseif (isset($response['response']) && is_array($response['response'])) {
                foreach ($response['response'] as $responseData) {
                    if ($responseData->RefundStatus === 'Approved') {
                        $updateTrackings[] = $responseData->PicNumber;
                    }
                }
            }

            if (!empty($updateTrackings)) {
                $collection = $this->collectionFactory->create()
                    ->addFieldToFilter('track_number', ['in' => $updateTrackings]);

                foreach ($collection as $track) {
                    $track->setRefundRequested(1);
                    $this->updateTrack($track);
                }
            }
            
            return $resultJson->setData($response);
        } catch (\Exception $e) {
            return $resultJson->setData(['error' => true, 'msg' => $e->getMessage(), 'response' => '']);
        }
    }

    /**
     * Update tracking
     *
     * @param $track
     */
    private function updateTrack($track)
    {
        try {
            $track->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Cannot save track:\n%1", $track->getTrackNumber())
            );
        }
    }
}
