<?php

declare(strict_types=1);

/**
 * File: Form.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
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
 * @SuppressWarnings(PHPMD.LongVariable)
 * @codeCoverageIgnore
 */
class Form extends Generic
{
    /**
     * @var string
     */
    public const PROCESS_URL_FORM_PARAM = 'process_url';

    /**
     * @var string
     */
    public const STORE_VIEW_FORM_PARAM = 'store_id';

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
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('purgemultiple_form');
        $this->setTitle(__('Varnish: purge a group of URLs'));
    }

    /**
     * @return Form
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareForm(): self
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getFormTargetUrl(),
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
    private function getFormTargetUrl(): string
    {
        return $this->_urlBuilder->getUrl('lizardmediavarnish/purgemultiple/run');
    }
}
