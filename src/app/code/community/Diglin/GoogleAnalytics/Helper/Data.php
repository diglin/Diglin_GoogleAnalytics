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
 * Took partially the code from Magento 1.9 but keep it for compatibility with previous Magento versions
 *
 * Class Diglin_GoogleAnalytics_Helper_Data
 */
class Diglin_GoogleAnalytics_Helper_Data extends Mage_Core_Helper_Data
{
    const CONFIG_UNIVERSAL_ANALYTICS = 'google/analytics/universal_analytics';
    const CONFIG_ENHANCED_ECOMMERCE  = 'google/analytics/enhanced_ecommerce';

    /**
     * Config paths for using throughout the code - Mage_GoogleAnalytics standard paths
     */
    const XML_PATH_ACTIVE               = 'google/analytics/active';
    const XML_PATH_TYPE                 = 'google/analytics/type';
    const XML_PATH_ACCOUNT              = 'google/analytics/account';
    const XML_PATH_ANONYMIZATION        = 'google/analytics/anonymization';
    const XML_PATH_ORDER_STATUS         = 'google/analytics/order_status';
    const XML_PATH_SOCIAL_ENABLED       = 'google/social/enabled';
    const XML_PATH_GA_POST_REQUEST      = 'google/analytics/ga_post_request';

    /**
     * Google analytics tracking code
     */
    const TYPE_ANALYTICS = 'analytics';

    /**
     * Google analytics universal tracking code
     */
    const TYPE_UNIVERSAL = 'universal';

    /**
     * Whether GA is ready to use
     *
     * @param mixed $store
     * @return bool
     */
    public function isGoogleAnalyticsAvailable($store = null)
    {
        $accountId = Mage::getStoreConfig(self::XML_PATH_ACCOUNT, $store);
        return $accountId && Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE, $store);
    }

    /**
     * Whether GA IP Anonymization is enabled
     *
     * @param null $store
     * @return bool
     */
    public function isIpAnonymizationEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ANONYMIZATION, $store);
    }

    /**
     * Get GA account id
     *
     * @param string $store
     * @return string
     */
    public function getAccountId($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ACCOUNT, $store);
    }

    /**
     * Returns true if should use Google Universal Analytics
     *
     * @param string $store
     * @return string
     */
    public function isUseUniversalAnalytics($store = null)
    {
        return (Mage::getStoreConfig(self::XML_PATH_TYPE, $store) == self::TYPE_UNIVERSAL
            || Mage::getStoreConfig(self::XML_PATH_TYPE, $store) == '' && Mage::getStoreConfigFlag(self::CONFIG_UNIVERSAL_ANALYTICS)); // for Magento compatibility version < 1.9.1
    }

    /**
     * Returns true if Enhanced Ecommerce Reporting is enabled
     *
     * @param string $store
     * @return string
     */
    public function isEnabledEnhancedEcommerceReporting($store = null)
    {
        return Mage::getStoreConfigFlag(self::CONFIG_ENHANCED_ECOMMERCE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getStatusesToTrack($store = null)
    {
        return unserialize(Mage::getStoreConfig(self::XML_PATH_ORDER_STATUS, $store));
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isSocialInteractionsEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SOCIAL_ENABLED, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function allowHttpPostTracking($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_GA_POST_REQUEST, $store);
    }
}