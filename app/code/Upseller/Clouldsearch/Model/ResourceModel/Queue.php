<?php
namespace Upseller\Clouldsearch\Model\ResourceModel;

class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('upseller_clouldsearch_queue', 'qjob_id');
    }
}
?>