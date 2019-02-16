<?php

namespace Upseller\Clouldsearch\Cron;

use Upseller\Clouldsearch\Model\Queue;

class Runcronsynchronization{
	
	protected $_logger;
	
	protected $_queue;
 
	public function __construct(
		Queue $queue,
		\Psr\Log\LoggerInterface $loggerInterface
	){
		$this->_queue = $queue;
		$this->_logger = $loggerInterface;
	}
 
	public function execute(){
		$this->_logger->debug('Syncronization Queue Run');
		$this->_queue->runQueue();
		return true;
	}
}