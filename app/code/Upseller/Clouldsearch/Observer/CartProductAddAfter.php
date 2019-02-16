<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CartProductAddAfter implements \Magento\Framework\Event\ObserverInterface{
	
	protected $_databaseObject;
	
	protected $_synchronization;
	
	protected $_sessionHelper;
	
	protected $_helper;
	
	protected $_registry;
	
	protected $_checkoutSession;
	
	protected $_request;
	
	public function __construct(
		Database $databaseObject,
		Synchronization $synchronization,
		DataHelper $helper,
		Session $sessionHelper,
		Registry $registry,
		CheckoutSession $checkoutSession,
		\Magento\Framework\App\RequestInterface $request
	){
		$this->_databaseObject = $databaseObject;
		$this->_synchronization = $synchronization;
		$this->_sessionHelper = $sessionHelper;
		$this->_helper = $helper;
		$this->_registry = $registry;
		$this->_checkoutSession = $checkoutSession;
		$this->_request = $request;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer){
		
		$this->_registry->unregister('upseller_cloudsearch_items');

		$currentItem = $observer->getEvent()->getQuoteItem();
		$postdata = $this->_request->getPost();
            
		$currentItemId=$currentItem->getProductId();
		if($currentItem->getProduct()->getParentProductId()){
			$currentItemId=$currentItem->getProduct()->getParentProductId();
		}

		if($currentItem->getProductId()){
			if(isset($postdata['upseller_search'])){
				$registryData=['method'=>"add","item_id"=>$currentItemId,"upseller_search"=>1];
			}else{
				$registryData=['method'=>"add","item_id"=>$currentItemId,"upseller_search"=>0];
			}

			$this->_registry->register('upseller_cloudsearch_items', $registryData);
		}
		return $this;
	}
}