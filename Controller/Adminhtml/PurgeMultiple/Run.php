<?php

declare(strict_types=1);

/**
 * File:Run.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeMultiple;

use LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeMultiple\Form\Edit\Form as PurgeMultipleForm;
use LizardMedia\VarnishWarmer\Block\Adminhtml\PurgeSingle\Form\Edit\Form as PurgeSingleForm;
use LizardMedia\VarnishWarmer\Model\FormDataProvider\MultipleUrlsPurgeCommandsProvider;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

/**
 * Class Run
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeSingle
 */
class Run extends Action
{
    public const ADMIN_RESOURCE = 'LizardMedia_VarnishWarmer::cache_handling';

    private $multipleUrlsPurgeCommandsProvider;

    public function __construct(
        Context $context,
        MultipleUrlsPurgeCommandsProvider $multipleUrlsPurgeCommandsProvider
    ) {
        parent::__construct($context);
        $this->multipleUrlsPurgeCommandsProvider = $multipleUrlsPurgeCommandsProvider;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $storeId = $this->getRequest()->getParam(PurgeMultipleForm::STORE_VIEW_FORM_PARAM);
        $destinationUrl = (string) $this->getRequest()->getParam(PurgeMultipleForm::PROCESS_URL_FORM_PARAM);

        if (!array_key_exists($destinationUrl, $this->multipleUrlsPurgeCommandsProvider->getCommandArray())) {
            $this->messageManager->addErrorMessage(__('Unknown process to run.'));

            return $this->_redirect('lizardmediavarnish/purgemultiple/form');
        }

        return $this->_redirect(
            $destinationUrl,
            [
                PurgeSingleForm::STORE_VIEW_FORM_PARAM => $storeId
            ]
        );
    }
}
