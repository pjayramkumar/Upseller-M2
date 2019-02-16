<?php

namespace Upseller\Clouldsearch\Model\ResourceModel\Queue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Upseller\Clouldsearch\Model\Queue', 'Upseller\Clouldsearch\Model\ResourceModel\Queue');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>