<?php
namespace Upseller\Clouldsearch\Controller\Adminhtml\Synchronization;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Model\Synchronization;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\LayoutFactory;

class Init extends Action
{
	
	protected $_helper;
	
	protected $_sessionHelper;
	
	protected $_synchronizationModel;
	
	protected $resultPageFactory;
    
    protected $resultJsonFactory;
    
    protected $layoutFactory;

    
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Synchronization $synchronizationModel,
        Session $sessionHelper,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        DataHelper $helper
    ) {
    	//echo 'pratik123';exit;
    	parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->_sessionHelper = $sessionHelper;
        $this->_helper = $helper;
        $this->_synchronizationModel = $synchronizationModel;
    }
    /**
     * Check the permission to run it
     *
     * @return bool
     */
   /*  protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Cms::page');
    } */

    
    public function execute(){
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
       
        $post = $this->getRequest()->getParams();
        
        $apiuid = $this->_helper->getCloudsearchUid($post['store']);
		$apikey = $this->_helper->getCloudsearchKey($post['store']);
		//if($apiuid!="" || $apikey!=""){

			$isCategories=false;
			$isProducts=false;

			$synchronizationArray=[];

			$synchronizationArray['is_products'] = false;
			if(isset($post['products'])){
				$synchronizationArray['is_products']=true;
			}
			
			$synchronizationArray['is_categories'] = false;
			if(isset($post['categories'])){
				$synchronizationArray['is_categories']=true; 
			}

			if(isset($post['store'])){
				$synchronizationArray['store']=$post['store']; 
			}
			
			$return = $this->_synchronizationModel->initialization($synchronizationArray);

			return $this->__ResponceUrl("",$return);
			

//		}else{
//
//			return $this->__ResponceUrl(__("Configuration is not Setup yet."),false);
//		}
        
    }
    
    protected function __ResponceUrl($error_message,$return, $syncStatus = false){

		$cloudseachSession = $this->_sessionHelper->getCloudSearchSession();

		$resultPage = $this->resultPageFactory->create();
		$layout = $resultPage->getLayout();
		//$layout = $this->layoutFactory->create();
//		$layout->getUpdate()->load('clouldsearch_synchronization_infomation');
//		$layout->generateXml();
//		$layout->generateBlocks();
//		$output = $layout->getOutput();
		$output = $layout->createBlock('Upseller\Clouldsearch\Block\Adminhtml\Synchronization\Syncroinfo')
          ->setTemplate('Upseller_Clouldsearch::synchronization/syncroinfo.phtml')
          ->toHtml();
          
		$isFinished = $this->_sessionHelper->isFinished();
		
		$returnArray['error']=true;
		$returnArray['finish']=$isFinished;
		$returnArray['error_message']=$error_message;
		$returnArray['loading_html']=$output;
		if($return==true){
			$returnArray['error']=false;
			$returnArray['finish']=$isFinished;
			$returnArray['error_message']=$error_message;
			$returnArray['loading_html']=$output;
		}
		
		if($syncStatus && !$return){
			$this->_sessionHelper->unsetCloudSearchSession();
		}
		
		$result = $this->resultJsonFactory->create();
		return $result->setData($returnArray);
	}
}
