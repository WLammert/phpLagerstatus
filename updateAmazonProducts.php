<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';

#initialisierung
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = null;

$categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
$productRepo = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
#$stockQuantity = $objectManager->get('Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku');
$stockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');


$categoryId = 2927; 
$category = $categoryFactory->create()->load($categoryId);
$categoryProducts = $category->getProductCollection()->addAttributeToSelect('*');

$naMenge = 33;

foreach ($categoryProducts as $product) {
    if($product->getTypeID() == 'simple'){
        $sku = $product->getSku();
        $qty = $stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
        $amazonMenge = $product->getCustomAttribute('amazon_qty')->getValue();
        $amazonLieferzeit = $product->getCustomAttribute('delivery')->getValue();
        $updatable = 0;
        if($qty > 0){
            if($amazonLieferzeit != 0){
                $product->setCustomAttribute('delivery', '0');    
                $updatable = 1;
            }
            if($amazonMenge != $qty){
                $product->setCustomAttribute('amazon_qty', $qty);
                $updatable = 1;
            }
        } else if($qty<1) {
            if($amazonLieferzeit == 0){
                $product->setCustomAttribute('delivery', $product->getCustomAttribute('delivery_wenn_na')->getValue());    
                $updatable = 1;
            }
            if($amazonMenge != $naMenge){
                $product->setCustomAttribute('amazon_qty', $naMenge);
                $updatable = 1;
            }
        }
        if($updatable==1){
            if($state == null){
                $state = $objectManager->get('Magento\Framework\App\State');
                $state->setAreaCode('frontend');
            }
            $productRepo->save($product);
            $timestamp = time();
            $datum = date("d.m.Y",$timestamp);
            $uhrzeit = date("H:i",$timestamp);

            echo $datum." ".$uhrzeit." -> SKU ".$sku." aktualisiert: Amazonmenge:".$product->getCustomAttribute('amazon_qty')->getValue()
                ." Amazonlieferzeit:".$product->getCustomAttribute('delivery')->getValue()."\r\n";
        }
    }
}
