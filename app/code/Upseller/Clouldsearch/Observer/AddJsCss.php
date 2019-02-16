<?php
namespace Upseller\Clouldsearch\Observer;

use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Magento\Store\Model\StoreManagerInterface;

class AddJsCss implements \Magento\Framework\Event\ObserverInterface{
	
	protected $_helper;
	
	protected $_storeManager;  
	
	public function __construct(
		DataHelper $helper,
		StoreManagerInterface $storeManager
	){
		$this->_helper = $helper;
		$this->_storeManager = $storeManager;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer){
		
		$storeId = $this->_storeManager->getStore()->getId();		
		$layout = $observer->getLayout();
        
        $layout->getUpdate()->addUpdate('<head>
              	<css src="'.$this->_helper->getProtocol().'://'.$this->_helper->getCloudsearchUid($storeId).'.'.$this->_helper->getSubdomain($storeId).'.'.$this->_helper->getSearchDomain($storeId).'/csa/cloudesearchauto_v1.0.0.css" src_type="url"  media="all"/>
              	<script src="'.$this->_helper->getProtocol().'://'.$this->_helper->getCloudsearchUid($storeId).'.'.$this->_helper->getSubdomain($storeId).'.'.$this->_helper->getSearchDomain($storeId).'/csa/cloudesearchauto_v1.0.0.js" src_type="url"/>
              	<script src="'.$this->_helper->getProtocol().'://'.$this->_helper->getCloudsearchUid($storeId).'.'.$this->_helper->getSubdomain($storeId).'.'.$this->_helper->getSearchDomain($storeId).'/csa/magento_v2.0.0.js" src_type="url"/>
            </head>');
        
        $layout->generateXml();
		
		return $this;
	}
}