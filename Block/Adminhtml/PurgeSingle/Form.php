<?php
/**
 * File: Form.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeSingle;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Phrase;

/**
 * Class Form
 * @package LizardMedia\VarnishWarmer\Block\Adminhtml\Form
 */
class Form extends Container
{
    /**
     * @return null
     */
    protected function _construct()
    {
        $this->_blockGroup = 'LizardMedia_VarnishWarmer';
        $this->_controller = 'adminhtml_purgeSingle_form';

        parent::_construct();

        $this->updateSaveButton();
        $this->removeButtons();
    }

    /**
     * @return Phrase
     */
    public function getHeaderText()
    {
        return __('Varnish: purge single URL');
    }

    /**
     * @return null
     */
    private function updateSaveButton()
    {
        $this->buttonList->update(
            'save',
            'label',
            __('Run process')
        );
    }

    /**
     * @return null
     */
    private function removeButtons()
    {
        $this->buttonList->remove('delete');
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
    }
}
