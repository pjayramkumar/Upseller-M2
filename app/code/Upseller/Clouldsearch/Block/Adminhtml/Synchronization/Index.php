<?php
namespace Upseller\Clouldsearch\Block\Adminhtml\Synchronization;

use Upseller\Clouldsearch\Helper\Data as DataHelper;
/**
 * Adminhtml cms pages content block
 */
class Index extends \Magento\Backend\Block\Widget
{
   	/**
     * Layout
     *
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

	protected $_helper;

    /**
     *
     */
     public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        DataHelper $helper,
        \Magento\Framework\View\LayoutInterface $layout
     ) {
     	
     	parent::__construct($context);
     	
        $this->_helper = $helper;
        $this->_layout = $layout;
        
     }
     
   	
   	public function getMigrateButton()
    {
        
        $startLabel = 'Start Synchronization';
        $startAction = 'startSynchronization';
        
        return $this->_makeButton($startLabel, $startAction, false);
    }
    
    public function getCloudsearchUid()
    {
        return $this->_helper->getCloudsearchUid();
    }
    
    public function getCloudsearchKey()
    {
        return $this->_helper->getCloudsearchKey();
    }
    
    public function getWebsites()
    {
        return $this->_helper->getWebsites();
    }
    
	private function _makeButton($label, $action, $disabled = false)
    {
        
		
		$buttonHtml = $this->_layout->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
            'id' => 'clouldsearch_synchronization_start',
            'label' => __($label),
            'disabled' => $disabled
            ]
        )->toHtml();
        return $buttonHtml;
    }
    
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
