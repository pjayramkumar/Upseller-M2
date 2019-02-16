<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;

class CatalogControllerCategoryDelete implements \Magento\Framework\Event\ObserverInterface{
	
	protected $_databaseObject;
	
	protected $_synchronization;
	
	protected $_sessionHelper;
	
	protected $_helper;
	
	public function __construct(
		Database $databaseObject,
		Synchronization $synchronization,
		DataHelper $helper,
		Session $sessionHelper
	){
		$this->_databaseObject = $databaseObject;
		$this->_synchronization = $synchronization;
		$this->_sessionHelper = $sessionHelper;
		$this->_helper = $helper;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer){
		
		$storeIds = $this->_databaseObject->getStores();

        foreach($storeIds as $storeId){

            if($this->_helper->IsActive($storeId['store_id'])){
                $category = $observer->getEvent()->getCategory();
                $categoryId = $category->getId();
               	$objectType = "categories";
               	
                $object = $this->_databaseObject->getCategoryDataById($categoryId,$storeId['store_id']);
                $this->_synchronization->syncronizationToCloud($object,$objectType,$storeId['store_id'],"delete");
            }
        }
        
		return $this;
	}
}