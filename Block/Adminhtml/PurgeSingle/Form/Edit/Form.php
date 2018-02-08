<?php
/**
 * File: Form.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeSingle\Form\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Class Form
 * @package LizardMedia\VarnishWarmer\Block\Adminhtml\Form\PurgeSingle
 */
class Form extends Generic
{
    const URL_FORM_PARAM = 'url';

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('purgesingle_form');
        $this->setTitle(__('Varnish: purge single URL'));
    }

    /**
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getFormTargerUrl(),
                    'method' => 'post'
                ]
            ]
        );

        $form->setHtmlIdPrefix('purgesingle_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'class' => 'fieldset-wide'
            ]
        );

        $fieldset->addField(
            self::URL_FORM_PARAM,
            'text',
            [
                'name' => self::URL_FORM_PARAM,
                'label' => __('URL to purge'),
                'title' => __('URL to purge'),
                'required' => false,
                'note' => __('Relative URL, e.g. * or bizuteria. If empty, homepage will be purged')

            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    private function getFormTargerUrl()
    {
        return $this->_urlBuilder->getUrl('lizardmediavarnish/purgesingle/run');
    }
}
