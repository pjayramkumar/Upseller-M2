<?php
namespace Upseller\Clouldsearch\Model;

use Upseller\Clouldsearch\Model\Database;
use Upseller\Clouldsearch\Model\Synchronization;
use Upseller\Clouldsearch\Helper\Session;
use Upseller\Clouldsearch\Helper\Data as DataHelper;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\CookieManagerInterface;


class Queue extends \Magento\Framework\Model\AbstractModel{	
	protected $_databaseObject;
	
	protected $_synchronization;
	
	protected $_helper;
	
	protected $_date;
	
	protected $_queueTable;
	
	protected $_writeConnection;
	
	protected $_readConnection;
	
	protected $maxSingleJobDataSize = 30;
	
	public function __construct(
		Database $databaseObject,
		Synchronization $synchronization,
		DataHelper $helper,
		\Magento\Framework\Stdlib\DateTime\DateTime $date
	){
		$this->_databaseObject = $databaseObject;
		$this->_synchronization = $synchronization;
		$this->_helper = $helper;
		$this->_date = $date;
		$this->_queueTable = $this->_databaseObject->getTableName('upseller_clouldsearch/queue');
		$this->_writeConnection = $this->_databaseObject->getWriteConnection();
		$this->_readConnection = $this->_databaseObject->getReadConnection();
	}
	
	/**
	* Initialize resource model
	*
	* @return void
	*/
	protected function _construct(){
		$this->_init('Upseller\Clouldsearch\Model\ResourceModel\Queue');
	}
    
	public function add($class , $priority, $method, $data , $storeId){
		$dataChunk=array_chunk($data,$this->_helper->getSyncrobatch());
		foreach($dataChunk as $dataChu){
            
			$currentTime = $this->_date->gmtDate('Y-m-d H:i:s');
			$binds = array(
				'qpriority' => $priority,
				'qclass' => $class,
				'qmethod' => $method,
				'qdata' => json_encode($dataChu),
				'qdata_size' => $this->_helper->getSyncrobatch(),
				'qstore_id' => $storeId,
				'created_at' => $currentTime,
				'updated_at' => $currentTime,
			);

			$query = "INSERT INTO " . $this->_queueTable . " SET qpriority = :qpriority , qclass = :qclass , qmethod = :qmethod , qdata = :qdata , qdata_size = :qdata_size , qstore_id = :qstore_id , created_at = :created_at , updated_at = :updated_at ";
			$this->_writeConnection->query( $query, $binds );
		}
		$this->_writeConnection->commit(); 
	}
    
	public function addQueue(){

		$this->clearCompletedQueue();

		$stores=$this->_databaseObject->getStores();
		foreach($stores as $store){
			if($this->_helper->IsActive($store['store_id'])){

				$totalCategories = $this->_databaseObject->getCategoriesIds($store['store_id']);
				$totalProducts = $this->_databaseObject->getProductsIds($store['store_id']);
				$this->add("categories",0,"put",$totalCategories,$store['store_id']);
				$this->add("products",0,"put",$totalProducts,$store['store_id']);
			}
            
		}
		$this->runQueue();
	}
    
    public function runQueue(){

        $selectSql="select * from `".$this->_queueTable."` where qstatus='pending' and qmax_retries > qretries limit 0,".$this->maxSingleJobDataSize;
        $jobs=$this->_readConnection->fetchAll($selectSql);
        $this->runJobs($jobs);
       
    }
    
	public function clearCompletedQueue(){
		//$deleteSql="delete from `".$this->table."` where qstatus='completed'";
		$deleteSql="TRUNCATE TABLE `".$this->_queueTable."`";
		$this->_writeConnection->query($deleteSql);
	}
    
	protected function runJobs($jobs){

		foreach($jobs as $job){
            
			$pid = getmypid();
			$data=json_decode($job['qdata'],true);
			$storeId=$job['qstore_id'];
			$qclass=$job['qclass'];

			$jobObjects=[];

			if($qclass=="categories"){
				foreach($data as $da){
					$object=$this->_databaseObject->getCategoryDataById($da['entity_id'],$storeId);
					$jobObjects[key($object)]=$object[key($object)];
				}                 
			}elseif($qclass=="products"){
				foreach($data as $da){
					$object=$this->_databaseObject->getProductDataById($da['entity_id'],$storeId);
					$jobObjects[key($object)]=$object[key($object)];
				} 
			}
            
			if($this->_synchronization->syncronizationToCloud($jobObjects,$qclass,$storeId)){
				$currentTime=$this->_date->gmtDate('Y-m-d H:i:s');
				$this->_writeConnection->query("update `".$this->table."` set qpid='".$pid."' , qstatus='completed' , updated_at='".$currentTime."' where qjob_id='".$job['qjob_id']."'");
				$this->_writeConnection->commit(); 
            
			}else{
				$currentTime=$this->_date->gmtDate('Y-m-d H:i:s');
				$qretries=$job['qretries']+1;    
				$this->_writeConnection->query("update `".$this->table."` set qpid='".$pid."' , qerror_log='Internal Serverl Error' , qretries='".$qretries."' , updated_at='".$currentTime."' where qjob_id='".$job['qjob_id']."'");
				$this->_writeConnection->commit(); 
			}
		}
	}
}
?>