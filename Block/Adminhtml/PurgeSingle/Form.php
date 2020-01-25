<?php

declare(strict_types=1);

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
 * @codeCoverageIgnore
 */
class Form extends Container
{
    /**
     * @return void
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct(): void
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
    public function getHeaderText(): Phrase
    {
        return __('Varnish: purge single URL');
    }

    /**
     * @return void
     */
    private function updateSaveButton(): void
    {
        $this->buttonList->update(
            'save',
            'label',
            __('Run process')
        );
    }

    /**
     * @return void
     */
    private function removeButtons(): void
    {
        $this->buttonList->remove('delete');
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
    }
}
