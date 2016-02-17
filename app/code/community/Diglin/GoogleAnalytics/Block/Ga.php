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
class Diglin_GoogleAnalytics_Block_Ga extends Mage_GoogleAnalytics_Block_Ga
{
    /**
     * Render regular page tracking javascript code
     * The custom "page name" may be set from layout or somewhere else. It must start from slash.
     *
     * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/pages
     * @param string $accountId
     * @return string
     */
    protected function _getPageTrackingCode($accountId)
    {
        if (!Mage::getStoreConfigFlag(Diglin_GoogleAnalytics_Helper_Data::CONFIG_UNIVERSAL_ANALYTICS)) {
            return parent::_getPageTrackingCode($accountId);
        }

        $pageName   = trim($this->getPageName());
        $optPageURL = '';
        if ($pageName && preg_match('/^\/.*/i', $pageName)) {
            $optPageURL = ", '{$this->jsQuoteEscape($pageName)}'";
        }
        return "ga('create', '$accountId', {$this->getAccountParams()});"
        . "ga('require', 'displayfeatures');" // must be before any hits are sent to Google
        . $this->_getUserId() // must be before any hits are sent to Google
        . "ga('send', 'pageview'$optPageURL);"
        . $this->_getAnonymizationCode();
    }

    /**
     * Prevent developer to be tracked
     *
     * @return string
     */
    public function getAccountParams()
    {
        if (Mage::getIsDeveloperMode()) {
            return Mage::helper('core')->jsonEncode(['cookieDomain' => 'none']);
        }
        return "'auto'";
    }

    /**
     * Render information about specified orders and their items / GA ecommerce
     * Add also missing product category compared the Magento Google Analytics Extension
     *
     * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/ecommerce
     * @return string
     */
    protected function _getOrdersTrackingCode()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));

        if (Mage::getStoreConfigFlag(Diglin_GoogleAnalytics_Helper_Data::CONFIG_UNIVERSAL_ANALYTICS)) {
            return $this->_getUniversalAnalyticsOrdersTrackingCode($collection);
        } else {
            return $this->_getAnalyticsOrdersTrackingCode($collection);
        }
    }

    /**
     * Specific ecommerce tracking for Universal Analytics of GA
     *
     * Diglin: Add support of currency and rate
     *
     * @param $collection
     * @return string
     */
    protected function _getUniversalAnalyticsOrdersTrackingCode($collection)
    {
        $result = array("ga('require', 'ecommerce', 'ecommerce.js');");

        foreach ($collection as $order) {
            $baseToGlobalRate = $order->getBaseToGlobalRate();

            $result[] = sprintf("ga('ecommerce:addTransaction', {'id':'%s','affiliation':'%s','revenue':'%s','shipping':'%s','tax':'%s','currency':'%s'})",
                $order->getIncrementId(),
                $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
                $order->getBaseGrandTotal() * $baseToGlobalRate,
                $order->getBaseShippingAmount() * $baseToGlobalRate,
                $order->getBaseTaxAmount() * $baseToGlobalRate,
                $order->getGlobalCurrencyCode()
            );

            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf("ga('ecommerce:addItem', {'id':'%s','name':'%s','sku':'%s','category':'%s','price': '%s','quantity':'%s','currency':'%s'});",
                    $order->getIncrementId(),
                    $this->jsQuoteEscape($item->getName()),
                    $this->jsQuoteEscape($item->getSku()),
                    $this->_getProductCategoryList($item),
                    $item->getBasePrice() * $baseToGlobalRate,
                    $item->getQtyOrdered(),
                    $order->getGlobalCurrencyCode()
                );
            }
            $result[] = "ga('ecommerce:send');";
        }
        return implode("\n", $result);
    }

    /**
     * Specific ecommerce tracking for classic Analytics of GA
     *
     * Add support of currency and rate
     *
     * @param $collection
     * @return string
     */
    protected function _getAnalyticsOrdersTrackingCode($collection)
    {
        $result = array();
        foreach ($collection as $order) {
            if ($order->getIsVirtual()) {
                $address = $order->getBillingAddress();
            } else {
                $address = $order->getShippingAddress();
            }

            $baseToGlobalRate = $order->getBaseToGlobalRate();

            $result[] = sprintf("_gaq.push(['_addTrans', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);",
                $order->getIncrementId(),
                $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
                $order->getBaseGrandTotal() * $baseToGlobalRate,
                $order->getBaseTaxAmount() * $baseToGlobalRate,
                $order->getBaseShippingAmount() * $baseToGlobalRate,
                $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($address->getCity())),
                $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($address->getRegion())),
                $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($address->getCountry()))
            );
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf("_gaq.push(['_addItem', '%s', '%s', '%s', '%s', '%s', '%s']);",
                    $order->getIncrementId(),
                    $this->jsQuoteEscape($item->getSku()),
                    $this->jsQuoteEscape($item->getName()),
                    $this->_getProductCategoryList($item),
                    $item->getBasePrice() * $baseToGlobalRate,
                    $item->getQtyOrdered()
                );
            }
            $result[] = "_gaq.push(['_trackTrans']);";
        }
        return implode("\n", $result);
    }

    /**
     * Render optimized asynchronous GA tracking snippet
     *
     * @link http://mathiasbynens.be/notes/async-analytics-snippet
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('googleanalytics')->isGoogleAnalyticsAvailable()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Render IP anonymization code for page tracking javascript code
     *
     * @return string
     */
    protected function _getAnonymizationCode()
    {
        $helper = Mage::helper('googleanalytics');
        if (method_exists($helper, 'isIpAnonymizationEnabled')) { // Compatibility with Magento 1.7
            if (!$helper->isIpAnonymizationEnabled()) {
                return '';
            }

            if (!Mage::getStoreConfigFlag(Diglin_GoogleAnalytics_Helper_Data::CONFIG_UNIVERSAL_ANALYTICS)) {
                return parent::_getAnonymizationCode();
            }
        }

        return "ga('set', 'anonymizeIp', true);";
    }

    /**
     * Retrieve the category list of an ordered product
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @return string
     */
    protected function _getProductCategoryList(Mage_Sales_Model_Order_Item $item)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->getSku());

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::DEFAULT_TYPE) {
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
            if (!$parentIds) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }
            if (isset($parentIds[0])) {
                $product = Mage::getModel('catalog/product')->load($parentIds[0]);
            }
        }

        $categoryList = "";
        $categories = $product->getCategoryCollection()->exportToArray(); // get list of categories
        foreach ($categories as $category) {
            $categoryList .= Mage::getModel('catalog/category')->load($category['entity_id'])->getName() . "|";
        }

        return $this->jsQuoteEscape(rtrim($categoryList, "|"));
    }

    /**
     * @return mixed|null|string
     */
    public function getPageName()
    {
        if (!$this->hasData('page_name')) {
            $parts = parse_url(
                Mage::getSingleton('core/url')->escape(
                    Mage::app()->getRequest()->getServer('REQUEST_URI')
                )
            );
            $query = '';
            if (isset($parts['query']) && !empty($parts['query'])) {
                $query = '?' . $parts['query'];
            }

            $storeCode = '';
            if (Mage::getStoreConfigFlag('web/url/use_store')) {
                $storeCode = '/' . Mage::app()->getStore()->getCode();
            }

            $url = Mage::getSingleton('core/url')->escape(
                rtrim(
                    str_replace(
                        'index/', '',
                        Mage::app()->getRequest()->getBaseUrl() . $storeCode . Mage::app()->getRequest()->getRequestString()
                    ), '/'
                ) . $query
            );
            $this->setPageName($url);
        }
        return $this->getData('page_name');
    }

    /**
     * Multi device tracking USER ID
     *
     * @return string
     */
    protected function _getUserId()
    {
        if (Mage::getSingleton('customer/session')->getCustomerId()) {
            return "ga('set', '&uid', ". Mage::getSingleton('customer/session')->getCustomerId() .");";
        }
        return '';
    }
}
