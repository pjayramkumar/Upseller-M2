<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Checkoutsubmitallafter implements \Magento\Framework\Event\ObserverInterface{
	
	protected $_databaseObject;
	
	protected $_synchronization;
	
	protected $_sessionHelper;
	
	protected $_helper;
	
	protected $_registry;
	
	protected $_checkoutSession;
	
	protected $_cookieManager;
	
	public function __construct(
		Database $databaseObject,
		Synchronization $synchronization,
		DataHelper $helper,
		Session $sessionHelper,
		Registry $registry,
		CheckoutSession $checkoutSession,
		CookieManagerInterface $cookieManager
	){
		$this->_databaseObject = $databaseObject;
		$this->_synchronization = $synchronization;
		$this->_sessionHelper = $sessionHelper;
		$this->_helper = $helper;
		$this->_registry = $registry;
		$this->_checkoutSession = $checkoutSession;
		$this->_cookieManager = $cookieManager;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer){
		
		$upsellerCloudsearchItems = $this->_registry->registry('upseller_cloudsearch_items');
		
		if(is_array($upsellerCloudsearchItems)){

			$items = $this->_checkoutSession->getQuote()->getAllVisibleItems();

			$currentItem=array();

			foreach($items as $item){
				if($upsellerCloudsearchItems['method']=="add"){
					if($upsellerCloudsearchItems['item_id']==$item->getProductId()){
						$currentItem=$item;
					}
				}elseif($upsellerCloudsearchItems['method']=="update"){
					if($upsellerCloudsearchItems['item_id']==$item->getItemId()){
						$currentItem=$item;
					}
				}
			}
                
			if(count($currentItem)!=0){
				$trackObject=[];

				$csaKeywordId=$this->_cookieManager->getCookie('csa_keyword_id');
				$csaSessionId=$this->_cookieManager->getCookie('csa_session_id');

				if($upsellerCloudsearchItems['method']=="add"){

					$trackObject['item_id']=$currentItem->getItemId();
					$trackObject['session_id']=$csaSessionId;
					$trackObject['keyword_id']=$csaKeywordId;
					$trackObject['name']=$currentItem->getName();
					$trackObject['sku']=$currentItem->getSku();
					$trackObject['unique_id']=$currentItem->getProductId();
					$trackObject['qty']=$currentItem->getQty();
					$trackObject['amount']=$currentItem->getRowTotalInclTax();
					$trackObject['is_removed']=false;
					$trackObject['upseller_search']=$upsellerCloudsearchItems['upseller_search'];


				}elseif($upsellerCloudsearchItems['method']=="update"){

					$trackObject['item_id']=$currentItem->getItemId();
					$trackObject['session_id']=$csaSessionId;
					$trackObject['keyword_id']=$csaKeywordId;
					$trackObject['name']=$currentItem->getName();
					$trackObject['sku']=$currentItem->getSku();
					$trackObject['unique_id']=$currentItem->getProductId();
					$trackObject['qty']=$currentItem->getQty();
					$trackObject['amount']=$currentItem->getRowTotalInclTax();
					$trackObject['is_removed']=false;
					$trackObject['upseller_search']=false;

				}
				$this->_synchronization->trackeventToCloud("addtocart",$trackObject,"items");
			}
		}
        
		return $this;
	}
}