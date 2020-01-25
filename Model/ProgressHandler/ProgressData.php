<?php

declare(strict_types=1);

/**
 * File: ProgressData.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\ProgressHandler;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressDataInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class ProgressData
 * @package LizardMedia\VarnishWarmer\Model\ProgressHandler
 */
class ProgressData extends AbstractExtensibleModel implements ProgressDataInterface
{
    /**
     * @return int|null
     */
    public function getCurrent()
    {
        return $this->getData(self::FIELD_CURRENT);
    }

    /**
     * @param int $current
     * @return void
     */
    public function setCurrent(int $current): void
    {
        $this->setData(self::FIELD_CURRENT, $current);
    }

    /**
     * @return int|null
     */
    public function getTotal()
    {
        return $this->getData(self::FIELD_TOTAL);
    }

    /**
     * @param int $total
     * @return void
     */
    public function setTotal(int $total): void
    {
        $this->setData(self::FIELD_TOTAL, $total);
    }

    /**
     * @return string|null
     */
    public function getProcessType()
    {
        return $this->getData(self::FIELD_PROCESS_TYPE);
    }

    /**
     * @param string $type
     * @return void
     */
    public function setProcessType(string $type): void
    {
        $this->setData(self::FIELD_PROCESS_TYPE, $type);
    }
}
