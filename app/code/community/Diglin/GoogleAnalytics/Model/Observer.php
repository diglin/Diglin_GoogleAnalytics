<?php
/**
 * Diglin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Diglin
 * @package     Diglin_GoogleAnalytics
 * @copyright   Copyright (c) 2011-2016 Diglin (http://www.diglin.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * GAConversionTrack
 *
 * @category    Interactiv4
 * @package     Interactiv4_GAConversionTrack
 * @copyright   Copyright (c) 2012 Interactiv4 SL. (http://www.interactiv4.com)
 */
class Diglin_GoogleAnalytics_Model_Observer
{
    /**
     * Event:
     * - sales_order_save_after
     *
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Exception
     * @throws Zend_Currency_Exception
     */
    public function track(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();
        $store = Mage::app()->getStore($order->getStoreId());
        $coreHelper = Mage::helper('core');

        if (Mage::helper('diglin_googleanalytics')->isGoogleAnalyticsAvailable($store)
            && !$order->getData('i4gaconversiontrack_tracked')
        ) {
            $statusesToTrack = Mage::helper('diglin_googleanalytics')->getStatusesToTrack($store);
            $pass = false;
            foreach ($statusesToTrack as $statusToTrack) {
                if ($order->getState() == $statusToTrack['state'] && $order->getStatus() == $statusToTrack['status']) {
                    $pass = true; // if any of the state/status combination matches, pass it through
                    break;
                }
            }

            if (!$pass) {
                return;
            }

            $googleAnalyticsAccountId = Mage::helper('diglin_googleanalytics')->getAccountId($store);
            $domain = parse_url($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), PHP_URL_HOST);
            $trackData = $this->_addTrackingDataToOrder($order);

            $ga_tracking = new Diglin_GoogleAnalytics_Model_Tracking(
                $googleAnalyticsAccountId,
                $domain,
                $trackData->getData('i4gaconversiontrack_user_agent')
            );

            $ga_tracking->setData('uip', $order->getRemoteIp());
            $ga_tracking->setData('cu', $order->getOrderCurrencyCode());
            $ga_tracking->setData('sr', $trackData->getData('i4gaconversiontrack_screen_resolution'));
            $ga_tracking->setData('sd', $trackData->getData('i4gaconversiontrack_screen_color_depth'));
            $ga_tracking->setData('ul', $trackData->getData('i4gaconversiontrack_browser_language'));
            $ga_tracking->setData('je', $trackData->getData('i4gaconversiontrack_browser_java_enabled'));

            // PageView for Successful Checkout Page
            $ga_tracking->pageView(
                Mage::getStoreConfig('google/analytics/page_title', $store),
                Mage::getStoreConfig('google/analytics/page_url', $store));

            $address = $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();

            $ga_tracking->addTransaction(
                $order->getIncrementId(),
                $order->getBaseGrandTotal(),
                $coreHelper->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
                $order->getBaseTaxAmount(),
                $order->getBaseShippingAmount(),
                $coreHelper->jsQuoteEscape($coreHelper->escapeHtml($address->getCity())),
                $coreHelper->jsQuoteEscape($coreHelper->escapeHtml($address->getRegion())),
                $coreHelper->jsQuoteEscape($coreHelper->escapeHtml($address->getCountry()))
            );

            /* @var $item Mage_Sales_Model_Order_Item */
            foreach ($order->getAllVisibleItems() as $item) {
                $ga_tracking->addItem(
                    $order->getIncrementId(),
                    $coreHelper->jsQuoteEscape($item->getSku()),
                    $item->getBasePrice(),
                    $item->getQtyOrdered(),
                    $coreHelper->jsQuoteEscape($item->getName())
                );
            }

            $order->setData('i4gaconversiontrack_tracked', 1);

            $comment = "GA Conversion Track OK"
                . "<br />GA Code: " . $googleAnalyticsAccountId
                . "<br />Domain: " . $domain
                . "<br />Order #: " . $order->getIncrementId()
                . "<br />Amount: " . Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->toCurrency($order->getBaseGrandTotal());

            $order->addStatusHistoryComment($comment);
            $order->save();
        }
    }

    /**
     * Event:
     * - sales_order_place_after
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function saveFields(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->_addTrackingDataToOrder($order);

        return $this;
    }

    /**
     * Get tracking data from order or request
     * @param Mage_Sales_Model_Order $order
     * @return Varien_Object
     */
    protected function _addTrackingDataToOrder($order)
    {
        $trackedData = unserialize($order->getData('i4gaconversiontrack_track_data'));
        if ($trackedData === false) {
            $trackedData = $this->_extractTrackDataFromRequest();
            $order->setData('i4gaconversiontrack_track_data', serialize($trackedData));
        }

        return $trackedData;
    }

    /**
     * @return Varien_Object
     */
    protected function _extractTrackDataFromRequest()
    {
        $request = Mage::app()->getRequest();
        /* Set data in serialized object in the order to allow easy adding of new attributes */
        $trackData = new Varien_Object();
        $trackData->setData('i4gaconversiontrack_user_agent', $request->getParam('i4gaconversiontrack_user_agent'));
        $trackData->setData('i4gaconversiontrack_screen_resolution', $request->getParam('i4gaconversiontrack_screen_resolution'));
        $trackData->setData('i4gaconversiontrack_screen_color_depth', $request->getParam('i4gaconversiontrack_screen_color_depth'));
        $trackData->setData('i4gaconversiontrack_browser_language', $request->getParam('i4gaconversiontrack_browser_language'));
        $trackData->setData('i4gaconversiontrack_browser_java_enabled', $request->getParam('i4gaconversiontrack_browser_java_enabled'));

        return $trackData;
    }

    /**
     * Event:
     * - checkout_onepage_controller_success_action
     * - checkout_multishipping_controller_success_action
     *
     * Add order information into GA block to render on checkout success pages
     *
     * @param Varien_Event_Observer $observer
     */
//    public function setGoogleAnalyticsOnOrderSuccessPageView(Varien_Event_Observer $observer)
//    {
//        $orderIds = $observer->getEvent()->getOrderIds();
//        if (empty($orderIds) || !is_array($orderIds)) {
//            return;
//        }
//        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('google_analytics');
//        if ($block) {
//            $block->setOrderIds($orderIds);
//        }
//    }
}
