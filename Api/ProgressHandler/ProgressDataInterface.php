<?php
/**
 * File: ProgressDataInterface.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Api\ProgressHandler;

/**
 * Interface ProgressDataInterface
 * @package LizardMedia\VarnishWarmer\Api\ProgressHandler
 */
interface ProgressDataInterface
{
    const FIELD_CURRENT = 'current';
    const FIELD_TOTAL = 'total';
    const FIELD_PROCESS_TYPE = 'process_type';

    /**
     * @return int|null
     */
    public function getCurrent();

    /**
     * @param int $current
     * @return null
     */
    public function setCurrent(int $current);

    /**
     * @return int|null
     */
    public function getTotal();

    /**
     * @param int $total
     * @return null
     */
    public function setTotal(int $total);

    /**
     * @return string|null
     */
    public function getProcessType();

    /**
     * @param string $type
     * @return null
     */
    public function setProcessType(string $type);
}
