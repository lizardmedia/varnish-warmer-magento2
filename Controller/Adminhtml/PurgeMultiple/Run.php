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
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;

/**
 * Class Run
 * @package LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeSingle
 */
class Run extends Action
{
    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $storeId = $this->getRequest()->getParam(PurgeMultipleForm::STORE_VIEW_FORM_PARAM);
        $destinationUrl = $this->getRequest()->getParam(PurgeMultipleForm::PROCESS_URL_FORM_PARAM);

        return $this->_redirect(
            $destinationUrl,
            [
                PurgeSingleForm::STORE_VIEW_FORM_PARAM => $storeId
            ]
        );
    }
}
