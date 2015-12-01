<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('order', 'i4gaconversiontrack_tracked', array('type' => 'int'));
$installer->addAttribute('order', 'i4gaconversiontrack_track_data', array('type' => 'text'));

$installer->run("UPDATE {$installer->getTable('sales/order')} SET i4gaconversiontrack_tracked = 1;");

$installer->endSetup();
