<?php

namespace Upseller\Clouldsearch\Model\System\Config\Source\Environment;

class Values implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'conversiotech.in', 'label' => __('Development')],
            ['value' => 'upsellerapp.com', 'label' => __('Live')]
        ];
    }
}
