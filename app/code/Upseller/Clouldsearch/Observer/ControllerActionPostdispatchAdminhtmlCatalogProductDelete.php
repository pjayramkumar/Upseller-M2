<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;

class ControllerActionPostdispatchAdminhtmlCatalogProductDelete implements \Magento\Framework\Event\ObserverInterface{
	
	protected $_request;
	
	protected $_databaseObject;
	
	protected $_synchronization;
	
	protected $_sessionHelper;
	
	protected $_helper;
	
	public function __construct(
		\Magento\Framework\App\RequestInterface $request,
		Database $databaseObject,
		Synchronization $synchronization,
		DataHelper $helper,
		Session $sessionHelper
	){
		$this->_request = $request;
		$this->_databaseObject = $databaseObject;
		$this->_synchronization = $synchronization;
		$this->_sessionHelper = $sessionHelper;
		$this->_helper = $helper;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer){
		
		$storeIds = $this->_databaseObject->getStores();

		foreach($storeIds as $storeId){

			if($this->_helper->IsActive($storeId['store_id'])){
				$request = $this->_request->getPost();
				$productId = $request['id'];
				$objectType = "products";
               	
				$object = $this->_databaseObject->getProductDataById($productId,$storeId['store_id']);
				$this->_synchronization->syncronizationToCloud($object,$objectType,$storeId['store_id'],"delete");
			}
		}
        
		return $this;
	}
}