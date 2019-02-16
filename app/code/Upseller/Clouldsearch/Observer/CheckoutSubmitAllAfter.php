<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CheckoutSubmitAllAfter implements \Magento\Framework\Event\ObserverInterface{
	
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
		
		$order = $observer->getEvent()->getOrder();
            
		$items=[];

		foreach($order->getAllVisibleItems() as $item){
			$itemId=$item->getQuoteItemId();
			if($item->getParentItemId()){
				$itemId=$item->getParentItemId();
			}

			$items[]=$itemId;
		}

		//print_r($items);

		$trackObject=[];

		$csaSessionId=$this->_cookieManager->getCookie('csa_session_id');

		$trackObject['order_id']=$order->getId();
		$trackObject['session_id']=$csaSessionId;
		$trackObject['order_num']=$order->getIncrementId();
		$trackObject['total_amount']=$order->getGrandTotal();
		$trackObject['customer']=[
			"name"  => $order->getBillingAddress()->getName(),
			"email" => $order->getBillingAddress()->getEmail(),
		];
		$trackObject['items']['data']=$items;
		$trackObject['status']=$order->getStatus();

		$this->_synchronization->trackeventToCloud("placeorder",$trackObject,"orders");
	}
}