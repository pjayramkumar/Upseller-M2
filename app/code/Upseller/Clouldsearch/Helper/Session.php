<?php
namespace Upseller\Clouldsearch\Helper;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Upseller\Clouldsearch\Model\Database;

class Session extends AbstractHelper
{
	protected $_databaseObject;
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $_configScopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    
    public function __construct(
    	Context $context, 
        ScopeConfigInterface $configScopeConfigInterface, 
        StoreManagerInterface $storeManagerInterface,
        SessionManagerInterface $sessionManagerInterface,
        Database $databaseObject
    )
    {
    	$this->_coreSession = $sessionManagerInterface;
        $this->_configScopeConfigInterface = $configScopeConfigInterface;
        $this->_storeManager = $storeManagerInterface;
        $this->_databaseObject = $databaseObject;        

        parent::__construct($context);
    }

	public function getCloudSearchSession(){
		
		$this->_coreSession->start();
    	$cloudSearchSyncro = $this->_coreSession->getCloudSearchSyncro();
		return $cloudSearchSyncro;
	}

	public function setCloudSearchSession($array){
		$this->_coreSession->start();
		$this->_coreSession->setCloudSearchSyncro($array);
	}
   	
	public function unsetCloudSearchSession($array){
		$this->_coreSession->start();
		$this->_coreSession->unsCloudSearchSyncro($array);
	}
   	
   	public function isFinished(){

		$cloudseachSession=$this->getCloudSearchSession();
		
		//print_r($cloudseachSession);exit;
		//$attributes=$cloudseachSession['attributes'];
		$attributes=$cloudseachSession;
		$attributesCategoriesFinished=true;//$attributes['categories']['attributes_finished'];
		$attributesProductsFinished=true;//$attributes['products']['attributes_finished'];

		//$stores = $this->_databaseObject->getStores();
		$stores[] = array('code' => 'default', 'id' => 1);

		$categories=$cloudseachSession['categories'];
		$products=$cloudseachSession['products'];
		$categoryResult=[];
		$productResult=[];

		foreach($stores as $store){

			$categoryResult["finish"][]=$categories[$store['code']]['categories_finished'];
			$productResult["finish"][]=$products[$store['code']]['products_finished'];

		}

		$categoryIsDone=array_sum($categoryResult["finish"])/count($categoryResult["finish"]);
		$productIsDone=array_sum($productResult["finish"])/count($productResult["finish"]);

		if($categoryIsDone==1 && $productIsDone==1 && $attributesCategoriesFinished==true && $attributesProductsFinished==true){
			$this->setCloudSearchSession(array());
			return true;
		}else{
			return false;
		}


	}
}