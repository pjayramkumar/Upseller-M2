<?php
namespace Upseller\Clouldsearch\Block\Clouldsearch;

use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Upseller\Clouldsearch\Model\Database;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Registry;
use Magento\Framework\Data\Form\FormKey;
use Magento\Customer\Model\Session;


class Script extends \Magento\Framework\View\Element\Template{
	
	protected $_layout;

	protected $_helper;
	
	protected $_databaseObject;
	
	protected $_storeManager;
	
	protected $_configScopeConfigInterface;
	
	protected $_registry;
	
	protected $_formKey;
	
	protected $_customerSession;
	
	protected $_jsonHelper;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		DataHelper $helper,
		Database $databaseObject,
		\Magento\Framework\View\LayoutInterface $layout,
		StoreManagerInterface $storeManager,
		ScopeConfigInterface $configScopeConfigInterface,
		Registry $registry,
		FormKey $formKey,
		Session $customerSession,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		array $data = []
	){
		parent::__construct($context, $data);
		$this->_helper = $helper;
		$this->_databaseObject = $databaseObject;
		$this->_layout = $layout;
		$this->_storeManager = $storeManager;
		$this->_configScopeConfigInterface = $configScopeConfigInterface;
		$this->_registry = $registry;
		$this->_formKey = $formKey;
		$this->_customerSession = $customerSession;
		$this->_jsonHelper = $jsonHelper;
	}
    
	public function getStoreId(){
		$storeId = $this->_storeManager->getStore()->getStoreId();
		return $storeId;
	}
    
	public function getJsonConfig(){
	
		$storeId = $this->getStoreId();

		$locale=explode("_", $this->_configScopeConfigInterface->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId));

		$query = '';

		$upsellerCls=array();
		$upsellerCls['apiuid']=$this->_helper->getCloudsearchUid($storeId);
		$upsellerCls['apikey']=$this->_helper->getCloudsearchKey($storeId);
		$upsellerCls['quicksearch']=$this->_helper->IsQuickSearchActive($storeId);
		$upsellerCls['advancesearch']=$this->_helper->IsAdvanceSearchActive($storeId);
		$upsellerCls['catalogsearch']=$this->_helper->IsCatalogSearchActive($storeId);
		$upsellerCls['searchurl']=$this->_helper->getSearchUrl($storeId);
		$upsellerCls['cms']=$this->_helper->_cms;
		$upsellerCls['cmsversion']=$this->_helper->_version;
		$upsellerCls['storeid']=$storeId;
		$upsellerCls['langcode']=strtolower($locale[0]);
		$upsellerCls['currency']=$this->_helper->getStoreCurrencyCode($storeId);
		$upsellerCls['is_price_including_tax']=$this->_helper->IsPriceIncludingTax($storeId);
		$upsellerCls['request']=array(
			'q'=>html_entity_decode($query),
			'filters' => $this->getFilters(),
			'page' => 1,
		);

		return $this->_jsonHelper->jsonEncode($upsellerCls);
	
	}
	
	public function getFilters(){
		
		$storeId=$this->getStoreId();
		$filters = [];
		if($this->_registry->registry('current_category') && $this->_helper->IsAdvanceSearchActive($storeId)){

			$attributeName=$this->_databaseObject->getCategoryNameAttribute();

			$category = $this->_registry->registry('current_category');

			
			$path = '';
			$level = '';

			if($category && $category->getDisplayMode() !== 'PAGE'){
				$category->getUrlInstance()->setStore($storeId);

				$level = -1;
				$pathArray = []; 
				$_path = "";
				foreach($category->getPathIds() as $treeCategoryId){
		            
					$parentId=$this->_databaseObject->getCategoryParentId($treeCategoryId);
					//if($parentId!=0){
					$_path=$this->_databaseObject->getCategoryNameById($treeCategoryId,$attributeName,$storeId);
					$pathArray [] = $_path;

					if($_path){
						$level++;
					}
					//}    
				}

				//print_r($pathArray);
				unset($pathArray[0]);
				unset($pathArray[1]);
				//print_r($pathArray);
				//exit;
				$path = implode(" /// ",$pathArray);
			

				$filters['category_ids.level'.$level]=$path;

			}

		}

		return $filters;

	}
    
	public function getTemplateVariableJsonConfig(){

		$storeId=$this->getStoreId();

		$confg=[];
		$confg['form_key'] = $this->getFormKey();
		$confg['current_customer']=$this->getCustomerId();
		$confg['current_date']=$this->getCurrentDate();
		$confg['currency']=$this->_helper->getStoreCurrencyCode($storeId);
		$confg['is_price_including_tax']=$this->_helper->IsPriceIncludingTax($storeId);
		
		$categoryDisplayMode="PAGE";
		if($this->_registry->registry('current_category') && $this->_helper->IsAdvanceSearchActive($storeId)){
			$category = $this->_registry->registry('current_category');
			$categoryDisplayMode=$category->getDisplayMode();
		}
		
		$confg['category_display_mode']=$categoryDisplayMode;
		$confg['tax_display_type']=$this->_helper->priceDisplayType($storeId);
		return $this->_jsonHelper->jsonEncode($confg);

	}
	
	public function getCustomerId(){

		if($this->_customerSession->isLoggedIn()){
			$customer =$this->_customerSession;
			$customerId=$customer->getId();
		}else{
			$customerId=0;
		}

		return $customerId;
	}
	
	public function getCurrentDate(){

		return date("Y-m-d");
	}

	public function getFormKey(){
		return $this->_formKey->getFormKey();
	}
}
