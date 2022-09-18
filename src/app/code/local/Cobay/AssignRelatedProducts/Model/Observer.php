<?php
class Cobay_AssignRelatedProducts_Model_Observer {

	public function assign(){
		Mage::log('______START Cobay_AssignRelatedProducts_Model_Observer________________');		
		foreach (Mage::app()->getWebsites() as $website){
			foreach ($website->getGroups() as $group) {
				$stores = $group->getStores();
				foreach ($stores as $store){
					$enable = (int)Mage::getStoreConfig('catalog/assignrelatedproducts/enable', $store->getId());
					if (!$enable) break;
					
					$ids = Mage::getModel("catalog/product")->getCollection()
					->addStoreFilter($store->getId())
					->addAttributeToFilter('status', 1)		//enabled
					->addAttributeToFilter('visibility', 4)	//catalog, search
					->getAllIds();

					foreach ($ids as $id){
						$product = Mage::getModel('catalog/product')->load($id);
						$category_ids = $product->getCategoryIds();
						$_category = Mage::getSingleton('catalog/category')->load(end($category_ids));
						
						/*** Related ***/
						$number = Mage::getStoreConfig('catalog/assignrelatedproducts/max_related', $store->getId());
						$collection = Mage::getModel('catalog/product')->getCollection()
						->addStoreFilter($store->getId())
						->addCategoryFilter($_category)
						->addAttributeToFilter('status', 1)			//enabled
						->addAttributeToFilter('visibility', 4)	//catalog, search
						->addAttributeToFilter('entity_id', array("neq"=>$id));
						$collection->getSelect()->order(new Zend_Db_Expr('RAND()'))->limit((int)$number);
						$assoc_array = array(); $order = 1;
						foreach($collection as $_product){
							$assoc_array[$_product->getId()] = array('position' => $order);
							$order++; 
						}
						$product->setRelatedLinkData($assoc_array);
						Mage::log('Related: ' . $product->getName() . ': ' . implode(', ', array_keys($assoc_array)));

						/*** Upsell ***/
						$number = Mage::getStoreConfig('catalog/assignrelatedproducts/max_upsell', $store->getId());
						$collection = Mage::getModel('catalog/product')->getCollection()
						->addStoreFilter($store->getId())
						->addCategoryFilter($_category)
						//->addAttributeToFilter('price', array('gteq' => $product->getPrice()))
						->addAttributeToFilter('status', 1)			//enabled
						->addAttributeToFilter('visibility', 4)	//catalog, search
						->addAttributeToFilter('entity_id', array("neq"=>$id));
						$collection->getSelect()->order(new Zend_Db_Expr('RAND()'))->limit((int)$number);
						$assoc_array = array(); $order = 1;
						foreach($collection as $_product){
							$assoc_array[$_product->getId()] = array('position' => $order);
							$order++;
						}
						$product->setUpSellLinkData($assoc_array);
						Mage::log('Up Sell: ' . $product->getName() . ': ' . implode(', ', array_keys($assoc_array)));
						
						/*** Crossell ***/
						$number = Mage::getStoreConfig('catalog/assignrelatedproducts/max_cross_sell', $store->getId());
						$collection = Mage::getModel('catalog/product')->getCollection()
						->addStoreFilter($store->getId())
						//->addCategoryFilter($_category)
						->addAttributeToFilter('status', 1)			//enabled
						->addAttributeToFilter('visibility', 4)	//catalog, search
						->addAttributeToFilter('entity_id', array("neq"=>$id));
						$collection->getSelect()->order(new Zend_Db_Expr('RAND()'))->limit((int)$number);
						$assoc_array = array(); $order = 1;
						foreach($collection as $_product){
							$assoc_array[$_product->getId()] = array('position' => $order);
							$order++;
						}
						$product->setCrossSellLinkData($assoc_array);
						Mage::log('Cross Sell: ' . $product->getName() . ': ' . implode(', ', array_keys($assoc_array)));

						$product->save();
						unset($product);
					}
					break;
				}
			}
		}
		Mage::log('______END Cobay_AssignRelatedProducts_Model_Observer________________');
	}

}