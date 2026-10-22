<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Block\Adminhtml\Form\Element;

use Magento\Framework\Data\Form\Element\Text;

class TextWithoutAutofill extends Text
{
    public function getHtmlAttributes()
    {
        return [...parent::getHtmlAttributes(), 'autocomplete'];
    }

    public function getElementHtml()
    {
        $this->setData('autocomplete', 'off');

        return parent::getElementHtml();
    }
}
