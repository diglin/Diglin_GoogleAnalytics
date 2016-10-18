<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_GoogleAnalytics
 * @copyright   Copyright (c) 2011-2016 Diglin (http://www.diglin.com)
 */

/**
 * Class Diglin_GoogleAnalytics_Block_Remarketing
 */
class Diglin_GoogleAnalytics_Block_Remarketing extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Catalog_Model_Product|mixed
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * @return null|string
     */
    public function getCategory()
    {
        $category = Mage::registry('current_category');

        if ($category instanceof Mage_Catalog_Model_Category) {
            return $category->getName();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isCartPage()
    {
        $handles = $this->getLayout()->getUpdate()->getHandles();
        if ($this->_searchArrayValue($handles, Mage::helper('diglin_googleanalytics')->getCartPageTypeList())) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed|null
     */
    public function getProductId()
    {
        if ($this->isCartPage()) {
            /* @var $cart Mage_Checkout_Model_Cart */
            $cart = Mage::getSingleton('checkout/cart');

            $productIds = array();
            foreach ($cart->getItems() as $item) {
                if ($item->hasParentItemId()) {
                    continue;
                }
                $productIds[] = $item->getProductId();
            }

            return "['" . implode("','", $productIds) . "']";
        } else {
            $product = $this->getProduct();

            if ($product instanceof Mage_Catalog_Model_Product) {
                return "'" . $product->getId() . "'";
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getPageType()
    {
        $helper = Mage::helper('diglin_googleanalytics');
        $handles = $this->getLayout()->getUpdate()->getHandles();

        if ($this->_searchArrayValue($handles, $helper->getHomePageTypeList())) {
            return 'home';
        }

        if ($this->_searchArrayValue($handles, $helper->getCategoryPageTypeList())) {
            return 'category';
        }

        if ($this->_searchArrayValue($handles, $helper->getProductPageTypeList())) {
            return 'product';
        }

        if ($this->_searchArrayValue($handles, $helper->getSearchResultsPageTypeList())) {
            return 'searchresults';
        }

        if ($this->_searchArrayValue($handles, $helper->getCartPageTypeList())) {
            return 'cart';
        }

        if ($this->_searchArrayValue($handles, $helper->getPurchasePageTypeList())) {
            return 'purchase';
        }

        if ($this->_searchArrayValue($handles, $helper->getOtherPageTypeList())) {
            return 'other';
        }

        return null;
    }

    /**
     * @param array $handles
     * @param array $values
     * @return bool
     */
    protected function _searchArrayValue(array $handles, array $values)
    {
        foreach ($handles as $handle) {
            if (in_array($handle, $values)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return float|null
     */
    public function getTotalValue()
    {
        if ($this->isCartPage()) {
            /* @var $cart Mage_Checkout_Model_Cart */
            $cart = Mage::getSingleton('checkout/cart');

            $price = 0;
            /* @var $item Mage_Sales_Model_Quote_Item */
            foreach ($cart->getItems() as $item) {
                if ($item->hasParentItemId()) {
                    continue;
                }
                $price += $item->getRowTotalInclTax();
            }

            return $price;
        } else {
            $product = $this->getProduct();

            if ($product instanceof Mage_Catalog_Model_Product) {
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    return $product->getPriceModel()->getTotalPrices($product, 'min');
                } else {
                    return $product->getFinalPrice();
                }
            }
        }

        return null;
    }
}