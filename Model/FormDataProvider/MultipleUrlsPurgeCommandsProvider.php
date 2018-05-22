<?php
declare(strict_types=1);

/**
 * File:MultipleUrlsPurgeCommandsProvider.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\FormDataProvider;

/**
 * Class MultipleUrlsPurgeCommandsProvider
 * @package LizardMedia\VarnishWarmer\Model\FormDataProvider
 */
final class MultipleUrlsPurgeCommandsProvider
{
    /**
     * @return array
     */
    public function getCommandArray(): array
    {
        return [
            'lizardmediavarnish/purge/purgehomepage' => __('Varnish: purge HP'),
            'lizardmediavarnish/purge/purgegeneral' => __('Varnish: purge HP, Categories'),
            'lizardmediavarnish/purge/purgeall' => __('Varnish: purge HP, Categories, Products (long)'),
            'lizardmediavarnish/purge/purgewildcard' => __('Varnish: purge * (longest)'),
            'lizardmediavarnish/purge/purgewildcardforce' => __('Varnish: purge * (force)')
        ];
    }
}
