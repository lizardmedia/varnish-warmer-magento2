<?php
/**
 * File: Form.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeMultiple\Form\Edit;

use LizardMedia\VarnishWarmer\Model\FormDataProvider\MultipleUrlsPurgeCommandsProvider;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

/**
 * Class Form
 * @package LizardMedia\VarnishWarmer\Block\Adminhtml\Form\PurgeMultiple
 */
class Form extends Generic
{
    const PROCESS_URL_FORM_PARAM = 'process_url';
    const STORE_VIEW_FORM_PARAM = 'store_id';

    /**
     * @var Store
     */
    private $systemStore;

    /**
     * @var MultipleUrlsPurgeCommandsProvider
     */
    private $multipleUrlsPurgeCommandsProvider;

    /**
     * Form constructor.
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param MultipleUrlsPurgeCommandsProvider $multipleUrlsPurgeCommandsProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        MultipleUrlsPurgeCommandsProvider $multipleUrlsPurgeCommandsProvider,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->systemStore = $systemStore;
        $this->multipleUrlsPurgeCommandsProvider = $multipleUrlsPurgeCommandsProvider;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('purgemultiple_form');
        $this->setTitle(__('Varnish: purge a group of URLs'));
    }

    /**
     * @return WidgetForm
     * @throws LocalizedException
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

        $form->setHtmlIdPrefix('purgemultiple_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'class' => 'fieldset-wide'
            ]
        );

        $fieldset->addField(
            self::PROCESS_URL_FORM_PARAM,
            'select',
            [
                'name' => self::PROCESS_URL_FORM_PARAM,
                'label' => __('Process to run'),
                'title' => __('Process to run'),
                'required' => true,
                'values' => $this->multipleUrlsPurgeCommandsProvider->getCommandArray()

            ]
        );

        $fieldset->addField(
            self::STORE_VIEW_FORM_PARAM,
            'select',
            [
                'name' => self::STORE_VIEW_FORM_PARAM,
                'label' => __('Store View'),
                'title' => __('Store View'),
                'required' => true,
                'values' => $this->systemStore->getStoreValuesForForm(false, true)
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
        return $this->_urlBuilder->getUrl('lizardmediavarnish/purgemultiple/run');
    }
}
