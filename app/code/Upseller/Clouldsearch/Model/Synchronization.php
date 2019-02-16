<?php 

namespace Upseller\Clouldsearch\Model;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Helper\Session;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Upseller\Clouldsearch\Helper\Data as DataHelper;

class Synchronization extends \Magento\Framework\Model\AbstractModel{
	protected $_databaseObject;
	
	protected $_batch;
	
	protected $_sessionHelper;
	
	protected $_helper;
	
	protected $_coreSession;
	
	protected $_cookieManager;
	
	public function __construct(
		Database $databaseObject,
		Session $sessionHelper,
		SessionManagerInterface $sessionManagerInterface,
		CookieManagerInterface $cookieManager,
		DataHelper $helper
	){
		$this->_databaseObject = $databaseObject;
		$this->_sessionHelper = $sessionHelper;
		$this->_coreSession = $sessionManagerInterface;
		$this->_cookieManager = $cookieManager;
		$this->_helper = $helper;
	}
  	
	public function initialization($synchronizationArray){

		$this->_batch = $this->_helper->getSyncrobatch($synchronizationArray['store']);
		$cloudseachSession = $this->_sessionHelper->getCloudSearchSession();

		return $this->__initialization($synchronizationArray,$cloudseachSession);
	}
	
	public function continuee($synchronizationArray){
		$this->_batch = $this->_helper->getSyncrobatch($synchronizationArray['store']);
		return $currentBatch=$this->__findCurrentBatch($synchronizationArray);

	}
	
	protected function __initialization($synchronizationArray,$cloudseachSession){

		$sessionArray=[];

		$categoryArray=$this->__initCatalog($synchronizationArray['is_categories'],"categories",$synchronizationArray['store']);
		$productArray=$this->__initCatalog($synchronizationArray['is_products'],"products",$synchronizationArray['store']);

		$sessionArray['categories']=$categoryArray;
		$sessionArray['products']=$productArray;
		//print_r($sessionArray);
		if($cloudseachSession==null){
			$this->_sessionHelper->setCloudSearchSession($sessionArray);
			return true;
		}else{
			$this->__reBuildSession($sessionArray,$cloudseachSession);
			return true;
		}
	}
	
	protected function __initCatalog($isCatalog,$catalogType,$poststore){

		$returnArray=[];

		$store = $this->_databaseObject->getStore($poststore);
		//$store = array('code' => 'default', 'id' => 1);
		
		if($isCatalog){
			if($catalogType=="categories"){
				$totalCatalog=$this->_databaseObject->getTotalCategories($poststore);
			}else{
				$totalCatalog=$this->_databaseObject->getTotalProducts($poststore);
			}
			
			$totalCatalogChunk = ceil($totalCatalog/$this->_batch);

			$returnArray[$store['code']][$catalogType.'_batch']=$this->_batch;
			$returnArray[$store['code']][$catalogType.'_finished']=0;
			$returnArray[$store['code']][$catalogType.'_total']=$totalCatalog;
			$returnArray[$store['code']][$catalogType.'_total_chunk']=$totalCatalogChunk;
			$returnArray[$store['code']][$catalogType.'_current_chunk']=0;
		}else{
			$returnArray[$store['code']][$catalogType.'_batch']=$this->_batch;
			$returnArray[$store['code']][$catalogType.'_finished']=1;
			$returnArray[$store['code']][$catalogType.'_total']=0;
			$returnArray[$store['code']][$catalogType.'_total_chunk']=0;
			$returnArray[$store['code']][$catalogType.'_current_chunk']=0;
		}
		
		return $returnArray;
	}
	
	protected function __reBuildSession($sessionArray,$cloudseachSession){
		return $sessionArray;
	}
	
	protected function __findCurrentBatch($synchronizationArray){
		
		
		$cloudseachSession = $this->_sessionHelper->getCloudSearchSession();
		$store = $this->_databaseObject->getStore($synchronizationArray['store']);
		//echo $store['code'];
		//print_r($cloudseachSession);exit;
		if($cloudseachSession['categories'][$store['code']]['categories_finished']==0){
			$updatedSession = $this->synchronizationCategoryData($cloudseachSession['categories'][$store['code']],$store['store_id']);
			$cloudseachSession['categories'][$store['code']]=$updatedSession;
			$this->_sessionHelper->setCloudSearchSession($cloudseachSession);
			return true;
		}

		if($cloudseachSession['products'][$store['code']]['products_finished']==0){
			
			$updatedSession=$this->synchronizationProductData($cloudseachSession['products'][$store['code']],$store['store_id']);
			$cloudseachSession['products'][$store['code']]=$updatedSession;
			$this->_sessionHelper->setCloudSearchSession($cloudseachSession);
			return true;
		}
	}
	
	protected function synchronizationCategoryData($cloudseachSessionCategories,$storeId){
		
		$currentChunk = $cloudseachSessionCategories['categories_current_chunk'];
		$batch = $cloudseachSessionCategories['categories_batch'];
		$categoriesTotalChunk = $cloudseachSessionCategories['categories_total_chunk'];

		if($categoriesTotalChunk < $currentChunk+1 ){
			return true;
		}
		if($currentChunk==0){
			$start=0;
			$limit=$batch;
		}else{
			$start=$currentChunk*$batch;
			$limit=$batch;
		}

		$categoryData = $this->_databaseObject->getCategoryData($limit,$start,$storeId);
		
//		$myfile = fopen("categoryData.txt", "w") or die("Unable to open file!");
//		$txt = json_encode($categoryData);
//		fwrite($myfile, $txt);
//		print_r($categoryData);exit;
		
		$this->syncronizationToCloud($categoryData,"categories",$storeId);

		if($categoriesTotalChunk==$currentChunk+1){
			$cloudseachSessionCategories['categories_finished']=1;
		}

		$cloudseachSessionCategories['categories_current_chunk']=$currentChunk+1;
		return $cloudseachSessionCategories;

	}
	
	protected function synchronizationProductData($cloudseachSessionProducts,$storeId){
		
		//print_r($cloudseachSessionProducts);exit;
		$currentChunk=$cloudseachSessionProducts['products_current_chunk'];
		$batch=$cloudseachSessionProducts['products_batch'];
		$productsTotalChunk=$cloudseachSessionProducts['products_total_chunk'];

		if($productsTotalChunk<$currentChunk+1){
			return true;
		}

		if($currentChunk==0){
			$start=0;
			$limit=$batch;
		}else{
			$start=$currentChunk*$batch;
			$limit=$batch;
		}

		$productsData=$this->_databaseObject->getProductData($limit,$start,$storeId);
		
//		$myfile = fopen("productsData.txt", "w") or die("Unable to open file!");
//		$txt = json_encode($productsData);
//		fwrite($myfile, $txt);
//		print_r($productsData);exit;
		$this->syncronizationToCloud($productsData,"products",$storeId);
		
		if($productsTotalChunk==$currentChunk+1){
			$cloudseachSessionProducts['products_finished']=1;
		}
		
		$cloudseachSessionProducts['products_current_chunk']=$currentChunk+1;
		return $cloudseachSessionProducts;

	}
	
	public function syncronizationToCloud($objects,$objectType,$storeId=false,$method="put"){
		
		$webserviceUrl = $this->_helper->getProtocol()."://".$this->_helper->getClusterKey($storeId).".".$this->_helper->getSubdomain($storeId).".".$this->_helper->getSearchDomain($storeId)."/api/synchronize/object";

		if($storeId===false){
			$postData=['method'=>$method,'object'=>$objects,"object_type"=>$objectType,"store_id"=>"","cloudsearch_uid"=>$this->_helper->getCloudsearchUid($storeId),"cloudsearch_key"=>$this->_helper->getCloudsearchKey($storeId),"version"=>$this->_helper->_version];
		}else{
			$postData=['method'=>$method,'object'=>$objects,"object_type"=>$objectType,"store_id"=>$storeId,"cloudsearch_uid"=>$this->_helper->getCloudsearchUid($storeId),"cloudsearch_key"=>$this->_helper->getCloudsearchKey($storeId),"version"=>$this->_helper->_version];
		}
		//print_r($postData);exit;
		$ch = curl_init($webserviceUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Accept: application/json'));
		curl_setopt($ch,CURLOPT_POST, count($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postData));
		curl_setopt($ch, CURLOPT_HEADER, 1);

		// execute!
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);

		//echo "<pre>";
		//print_r($response);
		//exit;

		if($info['http_code']=="200"){
			// close the connection, release resources used
			curl_close($ch);
			$this->_coreSession->setSyncronizationSuccess(true);
			// sleep for 10 seconds
			#sleep(1);
			//print_r($info);
			return true;
		}else{
			// close the connection, release resources used
			curl_close($ch);
			$this->_coreSession->setSyncronizationSuccess(false);
			// sleep for 10 seconds
			#sleep(1);
			print_r($info);
			//exit;
			return false;
		}
		
	}

	public function trackeventToCloud($event,$trackdata,$objectType){

		$csaKeywordId=$this->_cookieManager->getCookie('csa_keyword_id');
        $csaSessionId=$this->_cookieManager->getCookie('csa_session_id');
		
		$storeId = $this->_storeManager->getStore()->getId();

		$webserviceUrl=$this->_helper->getProtocol()."://".$this->_helper->getClusterKey($storeId).".cloudsearch.".$this->_helper->getSearchDomain($storeId)."/api/indices/trackevent";
		
		if($storeId===false){
			$postData=['event'=>$event,'trackdata'=>$trackdata,"cms"=>$this->_helper->_cms,"storeid"=>"","apiuid"=>$this->_helper->getCloudsearchUid($storeId),"apikey"=>$this->_helper->getCloudsearchKey($storeId),"cmsversion"=>$this->_helper->_version,"csa_session_id"=>$csaSessionId,"csa_keyword_id"=>$csaKeywordId,"object_type"=>$objectType];
		}else{
			$postData=['event'=>$event,'trackdata'=>$trackdata,"cms"=>$this->_helper->_cms,"storeid"=>$storeId,"apiuid"=>$this->_helper->getCloudsearchUid($storeId),"apikey"=>$this->_helper->getCloudsearchKey($storeId),"cmsversion"=>$this->_helper->_version,"csa_session_id"=>$csaSessionId,"csa_keyword_id"=>$csaKeywordId,"object_type"=>$objectType];
		}


		$ch = curl_init($webserviceUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Accept: application/json'));
		curl_setopt($ch,CURLOPT_POST, count($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postData));

		// execute!
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		#print_r($info);
		#exit;
		if($info['http_code']=="200"){
			// close the connection, release resources used
			curl_close($ch);
			// sleep for 10 seconds
			//sleep(1);
			return true;
		}else{
			// close the connection, release resources used
			curl_close($ch);
			// sleep for 10 seconds
			//sleep(1);
			return false;
		}

	}
	
} 
