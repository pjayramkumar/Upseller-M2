<?php
namespace Upseller\Clouldsearch\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;

class Data extends AbstractHelper
{
    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    /**
     * @var ScopeConfigInterface
     */
    protected $_configScopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    
    protected $_isDevelopmentMode=true;

	public $_cms="magento";

	public $_version="";

    public function __construct(
    	Context $context, 
        Registry $frameworkRegistry, 
        ScopeConfigInterface $configScopeConfigInterface, 
        StoreManagerInterface $storeManagerInterface,
        ProductMetadataInterface $productMetadata
    )
    {
        $this->_frameworkRegistry = $frameworkRegistry;
        $this->_configScopeConfigInterface = $configScopeConfigInterface;
        $this->_storeManager = $storeManagerInterface;
        $this->_version = $productMetadata->getVersion();
		
        parent::__construct($context);
        
        //echo $this->_version;exit;
    }

    public function getProtocol(){

		return "https";
	}
	
	public function getSyncrobatch($storeId = 0){
		$syncrobatch = $this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/syncrobatch', ScopeInterface::SCOPE_STORE, $storeId);
		return $syncrobatch;
	}
	public function getSearchDomain($storeId = 0){
		$searchdomain = $this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/searchdomain', ScopeInterface::SCOPE_STORE, $storeId);
		return $searchdomain;
	}
    
	public function getCloudsearchUid($storeId = 0){
		$cloudsearchUid = $this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/apiuid', ScopeInterface::SCOPE_STORE, $storeId);
		return $cloudsearchUid;
	}
	
    public function getCloudsearchKey($storeId = 0){
		$cloudsearchKey=$this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/apikey', ScopeInterface::SCOPE_STORE, $storeId);
		return $cloudsearchKey;	}

	public function IsActive($storeId=null){

		$active=$this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/active', ScopeInterface::SCOPE_STORE, $storeId);
		return $active;
	}

	public function IsQuickSearchActive($storeId = 0){

		$activeQuicksearch=$this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/active_quicksearch', ScopeInterface::SCOPE_STORE, $storeId);
		return $activeQuicksearch;
	}

	public function IsAdvanceSearchActive($storeId = 0){

		$activeQuicksearch=$this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/active_advance', ScopeInterface::SCOPE_STORE, $storeId);
		return $activeQuicksearch;
	}
	
	public function IsCatalogSearchActive($storeId){

		$activeQuicksearch=$this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/active_catalogsearch', ScopeInterface::SCOPE_STORE, $storeId);
		return $activeQuicksearch;
	}
	
	public function IsPriceIncludingTax($storeId = 0){
		
		$priceIncludesTax=$this->_configScopeConfigInterface->getValue('tax/calculation/price_includes_tax', ScopeInterface::SCOPE_STORE, $storeId);
		return $priceIncludesTax;
	}
	
	public function priceDisplayType($storeId = 0){
		
		$taxDisplayType=$this->_configScopeConfigInterface->getValue('tax/display/type', ScopeInterface::SCOPE_STORE,  $storeId);
		$returnVal='incl';
		if($taxDisplayType==1){
			$returnVal='excl';
		}elseif($taxDisplayType==2){
			$returnVal='incl';
		}elseif($taxDisplayType==3){
			$returnVal='both';
		}
		return $returnVal;
	}

	public function getSubdomain($storeId = 0){

		$subdomain=$this->_configScopeConfigInterface->getValue('upseller_clouldsearch/settings/subdomain', ScopeInterface::SCOPE_STORE, $storeId);
		return $subdomain;
	}
	
	public function getClusterKey($storeId = 0){

		$clusterkey=Mage::getStoreConfig('upseller_clouldsearch/settings/cluster_key',$storeId);
		return $clusterkey;
	}
	
	public function getSearchUrl($storeId = 0){
		$subdomain=$this->getSubdomain($storeId);
		$clusterkey=$this->getClusterKey($storeId);
		$searchurl=$this->getProtocol().'://'.$clusterkey.'.'.$subdomain.'.'.$this->getSearchDomain($storeId).'/';
		return $searchurl;
	}
	
    public function getStoreCurrencyCode($storeId = 0){
		$currency = $this->_storeManager->getStore($storeId)->getCurrentCurrencyCode();
		return $currency;
	}
    
    public function getWebsites() {
	    return $this->_storeManager->getWebsites();
	}	
	
}