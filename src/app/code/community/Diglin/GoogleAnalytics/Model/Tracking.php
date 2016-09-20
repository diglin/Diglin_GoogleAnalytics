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

/**
 * Based on original Source Code from Interactiv4
 * https://github.com/magento-hackathon/UniversalGaConversionTracking/blob/master/app/code/community/Interactiv4/GAConversionTrack/Model/Tracking.php
 * https://developers.google.com/analytics/resources/concepts/gaConceptsTrackingOverview
 *
 * Class Diglin_GoogleAnalytics_Model_Tracking
 */
class Diglin_GoogleAnalytics_Model_Tracking extends Varien_Object
{
    const GA_URL = 'http://www.google-analytics.com/collect';
    const GA_DEBUG_URL = 'http://www.google-analytics.com/debug/collect';
    const GA_SSL_URL = 'https://ssl.google-analytics.com/collect';

    const REQUEST_TYPE_PAGE = 'page';
    const REQUEST_TYPE_EVENT = 'event';
    const REQUEST_TYPE_TRANSACTION = 'transaction';
    const REQUEST_TYPE_ITEM = 'item';
    const REQUEST_TYPE_SOCIAL = 'social';
    const REQUEST_TYPE_TIMING = 'timing';

    const SCOPE_VISITOR = 1;
    const SCOPE_SESSION = 2;
    const SCOPE_PAGE = 3;

    /**
     * @var Varien_Data_Collection
     */
    protected $_custom_vars;

    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * Create Google Analytics Tracking
     */
    public function __construct($ga_account, $domain, $agent = 'GA Agent', $ip = null)
    {
        $init_data = array(
            'v'   => 1,
            'tid' => $ga_account,
            'cid' => $this->guid(),
            'de'  => 'UTF-8',
            'dh'  => $domain,
            'ua'  => $agent,
            'uip' => $ip,

        );

        parent::__construct($init_data);
    }

    /**
     * @return array
     */
    public function getDefaultsParameters()
    {
        return [
            // required
            'v'      => $this->getData('v'),
            'tid'    => $this->getData('tid'),
            'cid'    => $this->getData('cid'),
            // optional
            'uip'    => $this->getData('ui'),
            'dr'     => $this->getData('dr'), // Full referral URL, previously utmr
            'cn'     => $this->getData('cn'), // Campaign name
            'cs'     => $this->getData('cs'), // Campaign Source
            'cm'     => $this->getData('cm'), // Campaign Medium
            'ck'     => $this->getData('cm'), // Campaign Keyword
            'cc'     => $this->getData('cc') ? $this->getData('cc') : $this->getCookieParams(), // Campaign Content
            'ci'     => $this->getData('ci'), // Campaign ID
            'gclid'  => $this->getData('gclid'), // Google Adword ID
            'dclid'  => $this->getData('dclid'), // Google Display Ads ID
            'sr'     => $this->getData('sr'), // Screen Resolution
            'vp'     => $this->getData('vp'), // Viewport
            'de'     => $this->getData('de'), // Document Encoding
            'je'     => $this->getData('je'), // Java enabled
            'sd'     => $this->getData('sc'), // Screen Color Depth (e.g. 24 bit), previously utmsc
            'ul'     => $this->getData('ul'), // User language
            'fl'     => $this->getData('fl'), // Flash Version
            'ni'     => $this->getData('ni'), // Non Interactive - Specifies that a hit be considered non-interactive
            'dl'     => $this->getData('dl'), // Document location URL
            'dh'     => $this->getData('dh'), // Document Host Name
            'dp'     => $this->getData('dp'), // Document Path
            'dt'     => $this->getData('dt'), // Document Title
            'cd'     => $this->getData('cd'), // Screen Name (required on mobile application but not on web app)
            'linkid' => $this->getData('linkid'), // Link Id
        ];
    }

    /**
     * @return Varien_Data_Collection
     */
    public function getCustomVars()
    {
        if (is_null($this->_custom_vars)) {
            $this->_custom_vars = new Varien_Data_Collection();
        }

        return $this->_custom_vars;
    }

    public function pageView($title, $page)
    {
        $params = $this->getDefaultsParameters() + array(
                't'   => 'pageview',
                'dt'  => $title, // Page Title
                'dp'   => $page, // Page Url
                'cc'  => $this->getData('cc') ? $this->getData('cc') : $this->getCookieParams(), // Analytics Cookie string, replaced by Campaign content, previously utmcc
                'uip' => $this->getData('uip') // IP address, previously utmip
            );

        return $this->request($params);
    }

    /**
     * Add Transaction
     * @return String
     */
    public function addTransaction($order_id, $total, $store_name = null, $tax = null, $shipping = null, $city = null, $region = null, $country = null, $currency = null)
    {
        $params = $this->getDefaultsParameters() + array(
                't'   => self::REQUEST_TYPE_TRANSACTION,
                'ti'  => $order_id, // Transaction ID
                'ta'  => $store_name, // Transaction Affiliation
                'tr'  => $total, // Transaction Revenue
                'ts'  => $shipping, // Transaction Shipping
                'tt'  => $tax, // Transaction Tax
                'tci' => $city,
                'trg' => $region,
                'tco' => $country,
                'cu'  => ($currency) ? $currency : $this->getData('cu'), // currency
            );

        return $this->request($params);
    }

    /**
     * Add Item to Transaction
     * @return String
     */
    public function addItem($order_id, $sku, $price, $quantity, $name = null, $category = null, $currency = null)
    {
        $params = $this->getDefaultsParameters() + array(
                't'  => self::REQUEST_TYPE_ITEM,
                'ti' => $order_id, // Transaction ID
                'in' => $name, // Item name
                'ip' => $price, // Item price
                'iq' => $quantity, // Item quantity
                'ic' => $sku, // Item code
                'iv' => $category, // Item category
                'cu' => ($currency) ? $currency : $this->getData('cu'), // Currency
            );

        return $this->request($params);
    }

    /**
     * Add Event to Transaction
     * @return String
     */
    public function addEvent()
    {
        $params = $this->getDefaultsParameters() + array(
                't'  => self::REQUEST_TYPE_EVENT,
                'ea' => ($this->getData('ea')) ? $this->getData('ea') : 'Action', // Action
                'el' => $this->getData('el'), // Label
                'ev' => $this->getData('ev'), // Value
            );

        return $this->request($params);
    }

    /**
     * Add Custom Var
     * @return Varien_Object
     */
    public function addCustomVar($name, $value, $scope)
    {
        $item = new Varien_Object();
        $item->setName($name);
        $item->setValue($value);
        $item->setScope($scope);

        return $this->_custom_vars->addItem($item);
    }

    /**
     * Add Custom Visitor Var
     * @return Varien_Object
     */
    public function addVisitorVar($name, $value)
    {
        return $this->addCustomVar($name, $value, self::SCOPE_VISITOR);
    }

    /**
     * Add Custom Visitor Var
     * @return Varien_Object
     */
    public function addSessionVar($name, $value)
    {
        return $this->addCustomVar($name, $value, self::SCOPE_SESSION);
    }

    /**
     * Add Custom Visitor Var
     * @return Varien_Object
     */
    public function addPageVar($name, $value)
    {
        return $this->addCustomVar($name, $value, self::SCOPE_PAGE);
    }

    /**
     * Random ID
     * @return int
     */
    public function getRandomId()
    {
        return rand(10000000, 99999999);
    }

    /**
     * Request
     * @param Array $params
     * @return String
     */
    public function request($params)
    {
        $this->addData($params);

        if (Mage::getIsDeveloperMode() || $this->isDebug()) {
            $url = self::GA_DEBUG_URL;
            Mage::log($this->__toString(), Zend_Log::DEBUG);
        } else {
            $url = self::GA_SSL_URL;
        }

        $client = new Zend_Http_Client($url);
        $client->setParameterPost($params);
        $response = $client->request(Zend_Http_Client::POST);

        if (Mage::getIsDeveloperMode() || $this->isDebug()) {
            Mage::log($response->getBody(), Zend_Log::DEBUG);
        }

        return $response;
    }

    /**
     * Get Cookie Params
     * @return String
     */
    public function getCookieParams($utma1 = null, $utma2 = null, $today = null)
    {
        if (!$this->getData('utma')) {
            $utma1 = !is_null($utma1) ? $utma1 : $this->getRandomId();
            $utma2 = !is_null($utma2) ? $utma2 : rand(0, 1147483647) + 1000000000;
            $this->setData('utma', "1." . $utma1 . "00145214523." . $utma2 . "." . $today . "." . $today . ".15");
        }
        if (!$this->getData('utmz')) {
            $today = !is_null($today) ? $today : time();
            $this->setData('utmz', "1." . $today . ".1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)");
        }

        return "__utma=" . $this->getData('utma') . ";+__utmz=" . $this->getData('utmz') . ";";
    }

    public function __toString()
    {
        return ((Mage::getIsDeveloperMode() || $this->isDebug()) ? self::GA_DEBUG_URL : self::GA_SSL_URL) . '?' . http_build_query($this->getData());
    }

    /**
     * @return string
     */
    public static function guid()
    {
        if (function_exists('com_create_guid') === true) {
            return strtolower(trim(com_create_guid(), '{}'));
        }

        return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->_debug;
    }

    /**
     * @param boolean $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;

        return $this;
    }
}
