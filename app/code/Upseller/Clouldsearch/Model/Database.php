<?php
namespace Upseller\Clouldsearch\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Database extends \Magento\Framework\Model\AbstractModel{
	protected $_catalogCategoryEntityTypeId='catalog_category';
	
	protected $_catalogProductEntityTypeId='catalog_product';
	
	protected $_taxClassIdAttributeCode="tax_class_id";
  	
	protected $_storeManager;
	
	protected $_taxCalculationModel;
    
	public $_resource;
    
	protected $_readConnection;
    
	protected $_writeConnection;
	
	protected $_configScopeConfigInterface;
	
	protected $_eventManager;


	public function __construct(
		StoreManagerInterface $storeManager,
		TaxCalculation $taxCalculationModel,
		ScopeConfigInterface $configScopeConfigInterface,
		ResourceConnection $resource,
		\Magento\Framework\Event\ManagerInterface $eventManager
	){
		$this->_storeManager = $storeManager;
		$this->_taxCalculationModel = $taxCalculationModel;
		$this->_configScopeConfigInterface = $configScopeConfigInterface;
		$this->_resource = $resource;
		$this->_eventManager = $eventManager;
	}
    
	public function getReadConnection(){
		if(!$this->_readConnection){
			$this->_readConnection = $this->_resource->getConnection('core_read');
		}
		return $this->_readConnection;
	}
	
	public function getWriteConnection(){
		if(!$this->_writeConnection){
			$this->_writeConnection = $this->_resource->getConnection('core_write');
		}
		return $this->_writeConnection;
	}
	
	public function getTableName($tablename){
		$tableName = $this->_resource->getTableName($tablename);
		return $tableName;	
	}
	
	public function getStore($id){
		$stores=$this->getReadConnection()->fetchRow("select code,store_id from `".$this->getTableName('store')."` where store_id=".$id);
		return $stores;
	}
	
	public function getStores(){
		$stores=$this->getReadConnection()->fetchAll("select code,store_id from `".$this->getTableName('store')."` where store_id!=0");
		return $stores;
	}

	public function getTotalCategories($storeId){
		$rootCategoryId = $this->_storeManager->getStore($storeId)->getRootCategoryId();

		$totalCategories = $this->getReadConnection()->fetchOne("select count(*) from `".$this->getTableName('catalog_category_entity')."` where `path` LIKE '%1/".$rootCategoryId."/%'");
		return $totalCategories;
	}

	public function getTotalProducts($storeId){
		$store = $this->_storeManager->getStore($storeId);
		$websiteId = $store->getWebsiteId();

		$totalProducts=$this->getReadConnection()->fetchOne("select count(*) from `".$this->getTableName('catalog_product_entity')."` as prd , `".$this->getTableName('catalog_product_website')."` as website where website.product_id=prd.entity_id and website.website_id=".$websiteId);


		return $totalProducts;
	}
 	
	public function getCategoryData($limit,$start,$storeId){
		$rootCategoryId = $this->_storeManager->getStore($storeId)->getRootCategoryId();
		$categoryDataArray = $this->getReadConnection()->fetchAll("select * from `".$this->getTableName('catalog_category_entity')."` where `path` LIKE '%1/".$rootCategoryId."/%' limit ".$start." , ".$limit);
		
		//print_r($categpryDataArray);exit;
		$categoryArray=[];
		foreach($categoryDataArray as $category){
			$catData = $this->getCategoryDataById($category['entity_id'],$storeId,$category);
			$categoryArray[$category['entity_id']]=$catData[$category['entity_id']];	     
		}	
	    
		return $categoryArray;
	}
 	
	public function getCategoryDataById($categoryId,$storeId,$categoryArray=array()){

		$attributes = $this->getAllAttributes($this->_catalogCategoryEntityTypeId);

		if(count($categoryArray)==0){
			$category=$this->getReadConnection()->fetchRow("select * from `".$this->getTableName('catalog_category_entity')."` where entity_id=".$categoryId);
		}else{
			$category=$categoryArray;
		}

		$_categoryArray=[];
		$attributeName = $this->getCategoryNameAttribute();	    
		if(isset($category['path'])){
			$category['path'] = $this->decorateCategoryPath($category['path'],$attributeName,$storeId);
		}
		$_categoryArray[$category['entity_id']]=$category;
		foreach($attributes as $attribute){
			if(!isset($_categoryArray[$category['entity_id']][$attribute['attribute_code']])){
				$_categoryArray[$category['entity_id']][$attribute['attribute_code']]=$this->getAttributeValue($attribute,$category['entity_id'],"category",$storeId);
			}
		}

		return $_categoryArray;
	}
	
	protected function getAllAttributes($entryTypeId){
		$eavEntityId=$this->getReadConnection()->fetchOne("select entity_type_id from `".$this->getTableName('eav_entity_type')."` where entity_type_code='".$entryTypeId."'");
		$totalAttributes=$this->getReadConnection()->fetchAll("select * from `".$this->getTableName('eav_attribute')."` where entity_type_id='".$eavEntityId."'");
		return $totalAttributes;
	}
	
	public function getCategoryNameAttribute(){
		$attributeNameCode = "name";
		$entityTypeId = $this->getReadConnection()->fetchOne("select entity_type_id from `".$this->getTableName('eav_entity_type')."` where entity_type_code='".$this->_catalogCategoryEntityTypeId."'");
		$attributeName = $this->getReadConnection()->fetchRow("select * from `".$this->getTableName('eav_attribute')."` where entity_type_id='".$entityTypeId."' AND attribute_code='".$attributeNameCode."'");

		return $attributeName;
	}
	
	public function decorateCategoryPath($path,$attribute,$storeId){

		$pathArray=explode("/",$path);
		$returnArray=[];

		$jk=0;
		foreach($pathArray as $id){
			if($jk!=0){
				$returnArray[]=$this->getCategoryNameById($id,$attribute,$storeId);
			}
			$jk++;
		}
		unset($returnArray[0]);
		return implode(" /// ",$returnArray);
	}
	
	public function getCategoryNameById($id,$attribute,$storeId){
		return $this->getAttributeValue($attribute,$id,"category",$storeId);
	}
	
	public function getProductData($limit,$start,$storeId){

		$store = $this->_storeManager->getStore($storeId);
		$websiteId = $store->getWebsiteId(); 
		

	    $productDataArray=$this->getReadConnection()->fetchAll("select prd.* from `".$this->getTableName('catalog_product_entity')."` as prd, `".$this->getTableName('catalog_product_website')."` as website where website.product_id=prd.entity_id and website.website_id=".$websiteId." limit ".$start." , ".$limit);

		$productArray=[];
		foreach($productDataArray as $product){
			$prdData = $this->getProductDataById($product['entity_id'],$storeId,$product);
			$productArray[$product['entity_id']]=$prdData[$product['entity_id']];
		}
		
		return $productArray;
	}
	
	public function getProductDataById($productId,$storeId,$productArray=array()){

		$attributes=$this->getAllAttributes($this->_catalogProductEntityTypeId);

		if(count($productArray)==0){
			$product=$this->getReadConnection()->fetchRow("select * from `".$this->getTableName('catalog_product_entity')."` where entity_id=".$productId);
			//$product=$product[0];
		}else{
			$product=$productArray;
		}
		
		// Checking for Parent Relation Config Product
	    $parentId=$this->getReadConnection()->fetchRow("select parent_id from `".$this->getTableName('catalog_product_super_link')."` where product_id=".$productId);
	    if($parentId){
	      $product['relations_join']=["name" => 'child_doc','parent'=>$parentId['parent_id']];
	    }
	    if($product['type_id']=="configurable"){
	      $product['relations_join']=["name" => 'parent_doc'];
	    }

	    // Checking for Parent Relation Group Product
	    $linkRow=$this->getReadConnection()->fetchRow("select linktbl.product_id as product_id from `".$this->getTableName('catalog_product_link')."` as linktbl, `".$this->getTableName('catalog_product_link_type')."` as linktypetbl where linktbl.linked_product_id=".$productId." and linktypetbl.code='super' and linktypetbl.link_type_id=linktbl.link_type_id");
	    if($linkRow){
	      $product['relations_join']=["name" => 'child_doc','parent'=>$linkRow['product_id']];
	    }
	    if($product['type_id']=="grouped"){
	      $product['relations_join']=["name" => 'parent_doc'];
	    }

	    // Checking for Parent Relation Bundle Product
	    $relationRow=$this->getReadConnection()->fetchRow("select parent_id from `".$this->getTableName('catalog_product_relation')."` as rl, `".$this->getTableName('catalog_product_entity')."` as cpe where rl.child_id=".$productId." and rl.parent_id=cpe.entity_id and cpe.type_id='bundle'");
	    if($relationRow){
	      $product['relations_join']=["name" => 'child_doc','parent'=>$relationRow['parent_id']];
	    }
	    if($product['type_id']=="bundle"){
	      $product['relations_join']=["name" => 'parent_doc'];
	    }

	    // END
		
		$_productArray=array();

		$_productArray[$product['entity_id']]=$product;
		foreach($attributes as $attribute){
			if(!isset($_productArray[$product['entity_id']][$attribute['attribute_code']])){
				if($attribute['attribute_code']=="category_ids"){
					$_productArray[$product['entity_id']][$attribute['attribute_code']]=$this->getAttributeValue($attribute,$product['entity_id'],"product",$storeId);
					$attribute['attribute_code']="category_path";
					$_productArray[$product['entity_id']][$attribute['attribute_code']]=$this->getAttributeValue($attribute,$product['entity_id'],"product",$storeId);
				}elseif($attribute['attribute_code']=="visibility"){
					$values=$this->getAttributeValue($attribute,$product['entity_id'],"product",$storeId);

					$_productArray[$product['entity_id']]["visibility_search"]=0;
					$_productArray[$product['entity_id']]["visibility_catalog"]=0;
                         
					if($values == '4'){
						$_productArray[$product['entity_id']]["visibility_search"]=1;
						$_productArray[$product['entity_id']]["visibility_catalog"]=1;
					}elseif($values == '3'){
						$_productArray[$product['entity_id']]["visibility_search"]=1;
					}elseif($values == '2'){
						$_productArray[$product['entity_id']]["visibility_catalog"]=1;
					}
				}else{
					$_productArray[$product['entity_id']][$attribute['attribute_code']]=$this->getAttributeValue($attribute,$product['entity_id'],"product",$storeId);
				}
              
			}
		}

		$currentTimestamp = time();
		$ruleCurrentDate=date('Y-m-d', $currentTimestamp);
		$catalogruleProductPrice=$this->getReadConnection()->fetchAll("select rule_date,customer_group_id,rule_price from `".$this->getTableName('catalogrule_product_price')."` where product_id=".$productId." AND rule_date >='".$ruleCurrentDate."'");

		if(count($catalogruleProductPrice)){
			$_catalogruleProductPrice=$this->getPriceWithCurrencyIncExcl($catalogruleProductPrice,$productId,$storeId,'rule_price');;
      

			$_productArray[$product['entity_id']]["catalog_rule_prices"]=$_catalogruleProductPrice;
		}else{
			$_productArray[$product['entity_id']]["catalog_rule_prices"]=[];
		}
      
		//$_productArray[$product['entity_id']]["test"]="test";

		$store = $this->_storeManager->getStore($storeId);
		$websiteId = $store->getWebsiteId(); 

//		$customerProductPrice=$this->getReadConnection()->fetchAll("select customer_group_id,value from `".$this->getTableName('catalog_product_entity_group_price')."` where entity_id='".$productId."' and website_id IN ('".$websiteId."',0)");

//		if(count($customerProductPrice)){
//			$_customerProductPrice=$this->getPriceWithCurrencyIncExcl($customerProductPrice,$productId,$storeId,'value');
//			$_productArray[$product['entity_id']]["customer_prices"]=$_customerProductPrice;
//		}else{
//			$_productArray[$product['entity_id']]["customer_prices"]=[];
//		}

		$stockValues=$this->getReadConnection()->fetchAll("select * from `".$this->getTableName('cataloginventory_stock_item')."` where product_id='".$product['entity_id']."'");
		if(isset($stockValues[0]['product_id'])){
			if($stockValues[0]['manage_stock'] || ($stockValues[0]['use_config_manage_stock'] && $this->_configScopeConfigInterface->getValue('cataloginventory/item_options/manage_stock'))){
				$_productArray[$product['entity_id']]["is_in_stock"]=$stockValues[0]['is_in_stock'];
				$_productArray[$product['entity_id']]["qty"]=$stockValues[0]['qty'];
			}else{
				$_productArray[$product['entity_id']]["is_in_stock"]=1;
				$_productArray[$product['entity_id']]["qty"]=$stockValues[0]['qty'];
			}  
		}

		unset($_productArray[$product['entity_id']]['description']);
		
		/* code added by pratik */ 
		$productArrContainer = new Varien_Object();		
		$this->_eventManager->dispatch('upseller_cloudsync_product_get_after', ['product_array' => $_productArray, 'entity_id' => $product['entity_id'], 'store_id'=>$storeId, 'product_arr_container' => $productArrContainer]);
		if($productArrContainer->getProductArr() && count($productArrContainer->getProductArr())){
			$_productArray = $productArrContainer->getProductArr();
		}
	
		return $_productArray;

	}
	
	protected function getAttributeValue($attribute,$entityId,$entityCode,$storeId){

		if(in_array($attribute['backend_type'],array("datetime","decimal","int","text","varchar"))){
			$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$tableName = $this->getTableName('catalog_'.$entityCode.'_entity_'.$attribute['backend_type']);
			$entityTableName = $this->getTableName('catalog_'.$entityCode.'_entity');

			if(!in_array($attribute['frontend_input'],array("select","multiselect"))){
				if($entityCode=="product"){
					if($attribute['backend_type']=="varchar" && $attribute['frontend_model']=="Magento\Catalog\Model\Product\Attribute\Frontend\Image"){

						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
						}
						if($value != ''){
							$value=$this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $value;
						}
						

					}elseif( $attribute['frontend_input']=="gallery"){
						$tableName=$this->getTableName('catalog_'.$entityCode.'_entity_media_gallery');	
						$valueTableName=$this->getTableName('catalog_'.$entityCode.'_entity_media_gallery_value');	
						$values=$this->getReadConnection()->fetchAll("select value from `".$tableName."` 
						LEFT JOIN `".$valueTableName."` ON `".$tableName."`.value_id = `".$valueTableName."`.value_id
						where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."'");
						
						$value=[];
						foreach($values as $val){
							$value[]=$this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $val['value'];
						}

					}elseif($attribute['attribute_code']=="url_path"){
						$value=$this->getReadConnection()->fetchOne("select request_path from `". $this->getTableName('url_rewrite') ."` where entity_type='".$entityCode."' and entity_id='".$entityId."' and store_id='".$storeId."' LIMIT 1" );
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select request_path from `". $this->getTableName('url_rewrite') ."` where entity_type='".$entityCode."' and entity_id='".$entityId."' and store_id='0' LIMIT 1");
						}
						$value=$this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).$value; 

					}elseif(in_array($attribute['backend_model'], array('Magento\Catalog\Model\Product\Attribute\Backend\Price'))){

						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
						}

						$value=$this->getPriceWithCurrencyIncExcl($value,$entityId,$storeId);

					}else{
						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
						}
					}

				}else{
					if($attribute['attribute_code']=="url_path"){  
						$value=$this->getReadConnection()->fetchOne("select request_path from `". $this->getTableName('url_rewrite') ."` where entity_type='".$entityCode."' and entity_id='".$entityId."' and store_id='".$storeId."' LIMIT 1" );
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select request_path from `". $this->getTableName('url_rewrite') ."` where entity_type='".$entityCode."' and entity_id='".$entityId."' and store_id='0' LIMIT 1");
						}
						$value=$this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).$value;
					}else{
						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
						}
					}
				}  		
			}else{
				if($entityCode=="product"){
					if($attribute['backend_type']=="varchar" && $attribute['frontend_input']=="multiselect"){
						$values=$this->getReadConnection()->fetchAll("SELECT eaov.value as value FROM `".$this->getTableName('eav_attribute_option')."` as eao , `".$this->getTableName('eav_attribute_option_value')."` as eaov , `".$tableName."` as cpev where eao.attribute_id=cpev.attribute_id and eao.option_id=eaov.option_id and cpev.entity_id='".$entityId."' and cpev.attribute_id='".$attribute['attribute_id']."' and FIND_IN_SET (eao.option_id,cpev.value) and cpev.store_id='".$storeId."'");
    				
						if($values===false || !count($values)){
							$values=$this->getReadConnection()->fetchAll("SELECT eaov.value as value FROM `".$this->getTableName('eav_attribute_option')."` as eao , `".$this->getTableName('eav_attribute_option_value')."` as eaov , `".$tableName."` as cpev where eao.attribute_id=cpev.attribute_id and eao.option_id=eaov.option_id and cpev.entity_id='".$entityId."' and cpev.attribute_id='".$attribute['attribute_id']."' and FIND_IN_SET (eao.option_id,cpev.value) and cpev.store_id='0'");
						}

						$value=[];
						foreach($values as $val){
							$value[]=$val['value'];
						}


					}elseif($attribute['backend_type']=="int" && $attribute['frontend_input']=="select" && ( $attribute['source_model']=="" || $attribute['source_model']=="Magento\Eav\Model\Entity\Attribute\Source\Table")){
						$value=$this->getReadConnection()->fetchOne("SELECT eaov.value as value  FROM `".$tableName."` as eint , `".$entityTableName."` as cpa , `".$this->getTableName('eav_attribute_option_value')."` as eaov WHERE eint.entity_id='".$entityId."' and eint.attribute_id='".$attribute['attribute_id']."' and eint.entity_id=cpa.entity_id and eint.value=eaov.option_id and eint.store_id='".$storeId."'");

						if($value===false){
							$value=$this->getReadConnection()->fetchOne("SELECT eaov.value as value  FROM `".$tableName."` as eint , `".$entityTableName."` as cpa , `".$this->getTableName('eav_attribute_option_value')."` as eaov WHERE eint.entity_id='".$entityId."' and eint.attribute_id='".$attribute['attribute_id']."' and eint.entity_id=cpa.entity_id and eint.value=eaov.option_id and eint.store_id='0'");
						}


					}elseif($attribute['attribute_code']=="visibility"){
						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
						}
						if($value!==false){
							$modelArray = $this->_objectManager->create($attribute['source_model'])->getAllOptions();

							foreach($modelArray as $valArray){
								if($valArray['value']==$value){
									$value=$valArray['value'];
									/*
									if($valArray['label'] instanceof  \Magento\Framework\Phrase){
										$value=$valArray['label']->getText();
									}else{
										$value=$valArray['label'];
									}
									*/
								}
							}
						}else{
							$value=1;
						} 

						//$value=array_map('trim',explode(",",$value));
          
					}elseif($attribute['attribute_code']=="status"){
						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
						if($value===false){
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
						}
						if($value!==false){
							$modelArray=$this->_objectManager->create($attribute['source_model'])->getAllOptions();

							foreach($modelArray as $valArray){
								if($valArray['value']==$value){
									$value=$valArray['value'];
								}
							}
						}else{
							$value=0;
						} 
			
					}else{
						if($attribute['is_user_defined']!=1 && $attribute['source_model']!=""){

							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
							if($value===false){
								$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
							}
							if($value!==false){
								$modelArray=$this->_objectManager->create($attribute['source_model'])->getAllOptions();
								//print_r($modelArray);

								foreach($modelArray as $valArray){
									if($valArray['value']==$value){
										//echo get_class($valArray['label']);
										if($valArray['label'] instanceof  \Magento\Framework\Phrase){
											$value=$valArray['label']->getText();
										}else{
											$value=$valArray['label'];
										}
										
									}
								}

							}else{
								$value="";
							}	

						}else{
							$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
							if($value===false){
								$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
							}
						}	
    					
					}
				}else{
					$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='".$storeId."'");
					if($value===false){
						$value=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."' and store_id='0'");
					}
				}	
			}

		}else{
  		
			if($entityCode=="product"){
        
				if($attribute['attribute_code']=="category_ids"){

					$eavEntityId=$this->getReadConnection()->fetchOne("select entity_type_id from `".$this->getTableName('eav_entity_type')."` where entity_type_code='".$this->_catalogCategoryEntityTypeId."'");
					$attributeId=$this->getReadConnection()->fetchOne("select attribute_id from `".$this->getTableName('eav_attribute')."` where entity_type_id='".$eavEntityId."' and attribute_code='name'");
					$tableName=$this->getTableName('catalog_category_entity_varchar');

					$categoryProduct=$this->getTableName('catalog_category_product');

					$allCategoryIds=$this->getReadConnection()->fetchAll("select category_id from `".$categoryProduct."` where product_id='".$entityId."'");


					$__allCategoryIds=[];
  				
					foreach($allCategoryIds as $cat){
						$__allCategoryIds[]=$cat['category_id'];
					}

					$value=[];

					$attributeName=$this->getCategoryNameAttribute();

					foreach($__allCategoryIds as $cat){

						$path=$this->getReadConnection()->fetchOne("select path from `".$this->getTableName('catalog_category_entity')."` where entity_id=".$cat);

						$level=$this->getReadConnection()->fetchOne("select level from `".$this->getTableName('catalog_category_entity')."` where entity_id=".$cat);
              
						if($level!=0 && $level!=1){
							$value["level".$level][]=$this->decorateCategoryPath($path,$attributeName,$storeId);
						}

					}


				}elseif($attribute['attribute_code']=="category_path"){

					$eavEntityId=$this->getReadConnection()->fetchOne("select entity_type_id from `".$this->getTableName('eav_entity_type')."` where entity_type_code='".$this->_catalogCategoryEntityTypeId."'");
					$attributeId=$this->getReadConnection()->fetchOne("select attribute_id from `".$this->getTableName('eav_attribute')."` where entity_type_id='".$eavEntityId."' and attribute_code='name'");
					$tableName=$this->getTableName('catalog_category_entity_varchar');

					$categoryProduct=$this->getTableName('catalog_category_product');

					$allCategoryIds=$this->getReadConnection()->fetchAll("select category_id from `".$categoryProduct."` where product_id='".$entityId."'");


					$__allCategoryIds=[];
          
					foreach($allCategoryIds as $cat){
						$__allCategoryIds[]=$cat['category_id'];
					}

					$value=[];
					
					$attributeName=$this->getCategoryNameAttribute();

					foreach($__allCategoryIds as $cat){
						$value[]=$this->getCategoryNameById($cat,$attributeName,$storeId);
					}
				}elseif($attribute['attribute_code']=="media_gallery"){

					$tableName=$this->getTableName('catalog_'.$entityCode.'_entity_media_gallery');		
					$valueTableName=$this->getTableName('catalog_'.$entityCode.'_entity_media_gallery_value');		
					$values=$this->getReadConnection()->fetchAll("select value from `".$tableName."` 
					LEFT JOIN `".$valueTableName."` ON `".$tableName."`.value_id = `".$valueTableName."`.value_id
					where attribute_id='".$attribute['attribute_id']."' and entity_id='".$entityId."'");
					
					
					$value=[];
					foreach($values as $val){
						$value[]=$this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $val['value'];
					}
				}else{
					$value="";
				}

			}else{
				$value="";
			}
  	
		}
		if($value===false){
			$value="";
		}
		
		/* code added by pratik */ 
		$attributeValContainer = new \Magento\Framework\DataObject();
		$this->_eventManager->dispatch('upseller_cloudsync_attribute_get_after', ['attribute'=>$attribute, 'entity_id'=>$entityId, 'entity_code'=>$entityCode, 'store_id'=>$storeId, 'attribute_val_container' => $attributeValContainer]);
		if($attributeValContainer->getAttVal() && $attributeValContainer->getAttVal() != ''){
			$value = $attributeValContainer->getAttVal();
		}
		
		return $value;
	}
	
	protected function getPriceWithCurrencyIncExcl($value,$productId,$storeId,$valueAttribute=''){
		$allowedCurrency =  $this->_configScopeConfigInterface->getValue('currency/options/allow', ScopeInterface::SCOPE_STORE, $storeId);
		$allowedCurrency = explode(',', $allowedCurrency);
		$priceIncludesTax = $this->_configScopeConfigInterface->getValue('tax/calculation/price_includes_tax', ScopeInterface::SCOPE_STORE, $storeId);
		$percent=$this->getProductTaxRate($productId,$storeId);

		$prices=[];

		if(is_array($allowedCurrency)){

			foreach($allowedCurrency as  $currency){

				if(is_array($value)){

					$_arrayPrices=[];

					foreach($value as $val){
                      
						$valPrice=$val[$valueAttribute];
						unset($val[$valueAttribute]);
						$_prices=$this->calculateIncExcl($valPrice,$percent,$priceIncludesTax);

						$excludingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['excl'], $currency); 
						$includingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['incl'], $currency); 
                      
						$val[$valueAttribute.'_including_tax']=$includingTaxPrice;
						$val[$valueAttribute.'_excluding_tax']=$excludingTaxPrice;

						$_arrayPrices[]=$val;

					}

					$prices[$currency]=$_arrayPrices;

				}else{

					$_prices=$this->calculateIncExcl($value,$percent,$priceIncludesTax);

					$excludingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['excl'], $currency); 
					$includingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['incl'], $currency); 
					$prices[$currency]=['including_tax'=>$includingTaxPrice,'excluding_tax'=>$excludingTaxPrice];

				}                  
			}

		}else{

			if(is_array($value)){

				$_arrayPrices=[];

				foreach($value as $val){
                        
					$valPrice=$val[$valueAttribute];
					unset($val[$valueAttribute]);
					$_prices=$this->calculateIncExcl($valPrice,$percent,$priceIncludesTax);

					$excludingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['excl'], $allowedCurrency); 
					$includingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['incl'], $allowedCurrency); 
                        
					$val[$valueAttribute.'_including_tax']=$includingTaxPrice;
					$val[$valueAttribute.'_excluding_tax']=$excludingTaxPrice;

					$_arrayPrices[]=$val;
				}
				$prices[$allowedCurrency]=$_arrayPrices;
			}else{
				$_prices=$this->calculateIncExcl($value,$percent,$priceIncludesTax);
				$excludingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['excl'], $allowedCurrency); 
				$includingTaxPrice = $this->_storeManager->getStore()->getBaseCurrency()->convert($_prices['incl'], $allowedCurrency); 
				$prices[$allowedCurrency]=['including_tax'=>$includingTaxPrice,'excluding_tax'=>$excludingTaxPrice];
			}

		}

		return $prices;
	}
	
	protected function getProductTaxRate($productId,$storeId){

		$eavEntityId=$this->getReadConnection()->fetchOne("select entity_type_id from `".$this->getTableName('eav_entity_type')."` where entity_type_code='".$this->_catalogProductEntityTypeId."'");

		$taxClassIdAttribute=$this->getReadConnection()->fetchRow("select * from `".$this->getTableName('eav_attribute')."` where entity_type_id='".$eavEntityId."' and attribute_code='".$this->_taxClassIdAttributeCode."'");

		$tableName=$this->getTableName('catalog_product_entity_'.$taxClassIdAttribute['backend_type']);

		$taxclassid=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$taxClassIdAttribute['attribute_id']."' and entity_id='".$productId."' and store_id='".$storeId."'");
		if($taxclassid===false){
			$taxclassid=$this->getReadConnection()->fetchOne("select value from `".$tableName."` where attribute_id='".$taxClassIdAttribute['attribute_id']."' and entity_id='".$productId."' and store_id='0'");
		}

		$store = $this->_storeManager->getStore($storeId);

		$request = $this->_taxCalculationModel->getRateRequest(null, null, null, $store);
		$percent = $this->_taxCalculationModel->getRate($request->setProductClassId($taxclassid));
		// Zend_Debug::dump($percent);
		return $percent;

	}
  	
	protected function calculateIncExcl($price,$rate,$priceIncludesTax){
		$prices=[];
		if($priceIncludesTax){
			// Including Tax to Excluding Tax
			$excludingTaxPrice=round(((100*$price)/(100+$rate)),4);
			$includingTaxPrice=$price;

		}else{
			// Excluding Tax to Including Tax
			$excludingTaxPrice=$price;
			$includingTaxPrice=round($price+(($price*$rate)/100),4);
		}

		$prices['incl']=$includingTaxPrice;
		$prices['excl']=$excludingTaxPrice;

		return $prices;

	}
}
?>