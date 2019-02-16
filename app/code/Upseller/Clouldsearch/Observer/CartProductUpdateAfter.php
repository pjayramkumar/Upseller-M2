<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CartProductUpdateAfter implements \Magento\Framework\Event\ObserverInterface{
	
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
		$info = $observer->getEvent()->getInfo();

		$registryData=['method'=>"update","item_id"=>key($info)];

		$this->_registry->register('upseller_cloudsearch_items', $registryData);
		return $this;
	}
}