<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Block\Adminhtml\Form\Element;

use Magento\Framework\Data\Form\Element\Obscure;

class ObscureWithoutAutofill extends Obscure
{
    public function getHtmlAttributes()
    {
        return [...parent::getHtmlAttributes(), 'autocomplete'];
    }

    public function getElementHtml()
    {
        $this->setData('autocomplete', 'new-password');

        return parent::getElementHtml();
    }
}
