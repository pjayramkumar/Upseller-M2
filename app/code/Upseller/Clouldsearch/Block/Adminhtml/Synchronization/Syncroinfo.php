<?php
namespace Upseller\Clouldsearch\Block\Adminhtml\Synchronization;

use Upseller\Clouldsearch\Helper\Session;
/**
 * Adminhtml cms pages content block
 */
class Syncroinfo extends \Magento\Backend\Block\Template
{
   	/**
     * Layout
     *
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

	protected $_sessionHelper;

    /**
     *
     */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		Session $sessionHelper,
		\Magento\Framework\View\LayoutInterface $layout
	)
	{		
		//echo 'pratik-test7';exit;
		parent::__construct($context);
	 	$this->_sessionHelper = $sessionHelper;
	 	$this->_layout = $layout;
	 	
	}
     
   	
   	public function getCloudSearchSession(){
		return $this->_sessionHelper->getCloudSearchSession();
	}
   	
   	public function getAttributeSyncroInfo(){

		$cloudseachSession=$this->getCloudSearchSession();
		//print_r($cloudseachSession);exit;
		$attributes=$cloudseachSession;
		$attributesCategoriesFinished=true;//$attributes['categories']['attributes_finished'];
		$attributesCategoriesTotal=$attributes['categories']['default']['categories_total'];
		$attributesCategoriesBatch=$attributes['categories']['default']['categories_batch'];
		$attributesCategoriesCurrentChunk=$attributes['categories']['default']['categories_current_chunk'];

		$attributesProductsFinished=false;//$attributes['products']['attributes_finished'];
		$attributesProductsTotal=$attributes['products']['default']['products_total'];
		$attributesProductsBatch=$attributes['products']['default']['products_batch'];
		$attributesProductsCurrentChunk=$attributes['products']['default']['products_current_chunk'];

		$attributesClass="";
		$totalDone=(($attributesCategoriesCurrentChunk*$attributesCategoriesBatch)+($attributesProductsCurrentChunk*$attributesProductsBatch));
		if($attributesCategoriesFinished && $attributesProductsFinished){
			$attributesClass="finish";
			$totalDone=$attributesCategoriesTotal+$attributesProductsTotal;
		}elseif(($attributesCategoriesFinished==0 || $attributesProductsFinished==0) || ($attributesCategoriesCurrentChunk>0 || $attributesProductsCurrentChunk>0)){
			$attributesClass="continue";
		}

		$returnArray=[];

		$returnArray['attributesClass']=$attributesClass;
		$returnArray['totalDone']=$totalDone;
		$returnArray['total']=$attributesCategoriesTotal+$attributesProductsTotal;

		return $returnArray;

	}
	
	public function getCatalogSyncroInfo(){

		$cloudseachSession=$this->getCloudSearchSession();
		
//		$database=Mage::getModel('upseller_clouldsearch/database');
//		$stores=$database->getStores();
		$stores[] = array('code' => 'default', 'id' => 1);
		
		$categories=$cloudseachSession['categories'];
		$products=$cloudseachSession['products'];

		$categoryResult=[];
		$categoriesBatch=0;
		
		$productResult=[];
		$productBatch=0;
		

		foreach($stores as $store){

			$categoriesBatch=$categories[$store['code']]['categories_batch'];
			$categoryResult["finish"][]=$categories[$store['code']]['categories_finished'];
			$categoryResult["total"][]=$categories[$store['code']]['categories_total'];
			$categoryResult["total_done"][]=$categories[$store['code']]['categories_current_chunk']*$categories[$store['code']]['categories_batch'];

			$productBatch=$products[$store['code']]['products_batch'];
			$productResult["finish"][]=$products[$store['code']]['products_finished'];
			$productResult["total"][]=$products[$store['code']]['products_total'];
			$productResult["total_done"][]=$products[$store['code']]['products_current_chunk']*$products[$store['code']]['products_batch'];

		}

		$categoryClass="";
		$categoryIsDone=array_sum($categoryResult["finish"])/count($categoryResult["finish"]);
		$categorydone=array_sum($categoryResult["total_done"]);
		$categoryTotal=array_sum($categoryResult["total"]);
		if($categoryIsDone==1){
			$categoryClass="finish"; 
			$categorydone=$categoryTotal;
		}elseif($categoryIsDone==0 && $categorydone==0){
			$categoryClass="";
		}else{
			$categoryClass="continue";
		}


		$productClass="";
		$productIsDone=array_sum($productResult["finish"])/count($productResult["finish"]);
		$productdone=array_sum($productResult["total_done"]);
		$productTotal=array_sum($productResult["total"]);
		if($productIsDone==1){
			$productClass="finish"; 
			$productdone=$productTotal;
		}elseif($productIsDone==0 && $productdone==0){
			$productClass="";
		}else{
			$productClass="continue";
		}


		$returnArray=[];

		$returnArray['categoryClass']=$categoryClass;
		$returnArray['categorydone']=$categorydone;
		$returnArray['categoryTotal']=$categoryTotal;

		$returnArray['productClass']=$productClass;
		$returnArray['productdone']=$productdone;
		$returnArray['productTotal']=$productTotal;

		return $returnArray;
	}
	
}
