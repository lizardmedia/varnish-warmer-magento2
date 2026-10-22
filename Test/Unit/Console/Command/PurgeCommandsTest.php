<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Console\Command;

use LizardMedia\VarnishWarmer\Api\VarnishActionManagerInterface;
use LizardMedia\VarnishWarmer\Console\Command\PurgeAllCommand;
use LizardMedia\VarnishWarmer\Console\Command\PurgeGeneralCommand;
use LizardMedia\VarnishWarmer\Console\Command\PurgeHomepageCommand;
use LizardMedia\VarnishWarmer\Console\Command\PurgeUrlCommand;
use LizardMedia\VarnishWarmer\Console\Command\PurgeWildcardCommand;
use LizardMedia\VarnishWarmer\Console\Command\PurgeWildcardWithoutRegenerationCommand;
use LizardMedia\VarnishWarmer\Console\Command\RegenerateProductsCacheCommand;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeCommandsTest extends TestCase
{
    private array $calls = [];

    public static function commands(): array
    {
        return [
            'homepage' => [PurgeHomepageCommand::class, 'lm-varnish:cache-purge-homepage', 'purgeHomepage'],
            'general' => [PurgeGeneralCommand::class, 'lm-varnish:cache-purge-general', 'purgeGeneral'],
            'all' => [PurgeAllCommand::class, 'lm-varnish:cache-purge-all', 'purgeAll'],
            'wildcard' => [PurgeWildcardCommand::class, 'lm-varnish:cache-purge-wildcard', 'purgeWildcard'],
            'wildcard without regeneration' => [
                PurgeWildcardWithoutRegenerationCommand::class,
                'lm-varnish:cache-purge-force',
                'purgeWildcardWithoutRegen',
            ],
            'products' => [
                RegenerateProductsCacheCommand::class,
                'lm-varnish:regenerate-products-cache',
                'purgeAndRegenerateProducts',
            ],
        ];
    }

    #[DataProvider('commands')]
    public function testRunsActionOnDefaultStoreView(string $commandClass, string $name, string $action): void
    {
        $command = $this->createCommand($commandClass);

        $exitCode = (new CommandTester($command))->execute([]);

        self::assertSame($name, $command->getName());
        self::assertSame(0, $exitCode);
        self::assertSame([[$action]], $this->calls);
    }

    #[DataProvider('commands')]
    public function testSetsStoreViewBeforeRunningAction(string $commandClass, string $name, string $action): void
    {
        $exitCode = (new CommandTester($this->createCommand($commandClass)))->execute(['--store' => '3']);

        self::assertSame(0, $exitCode);
        self::assertSame([['setStoreViewId', 3], [$action]], $this->calls);
    }

    public function testRefreshUrlCommandPurgesAndRegeneratesGivenUrl(): void
    {
        $command = $this->createCommand(PurgeUrlCommand::class);

        $exitCode = (new CommandTester($command))->execute(['url' => 'bizuteria', '--store' => '2']);

        self::assertSame('lm-varnish:cache-refresh-url', $command->getName());
        self::assertSame(0, $exitCode);
        self::assertSame([['setStoreViewId', 2], ['purgeAndRegenerateUrl', 'bizuteria']], $this->calls);
    }

    public function testRefreshUrlCommandRequiresUrl(): void
    {
        $this->expectException(RuntimeException::class);

        (new CommandTester($this->createCommand(PurgeUrlCommand::class)))->execute([]);
    }

    private function createCommand(string $commandClass): Command
    {
        $varnishActionManager = $this->createStub(VarnishActionManagerInterface::class);
        $varnishActionManager->method('setStoreViewId')->willReturnCallback(function (int $storeViewId): void {
            $this->calls[] = ['setStoreViewId', $storeViewId];
        });
        $varnishActionManager->method('purgeAndRegenerateUrl')->willReturnCallback(function (string $url): void {
            $this->calls[] = ['purgeAndRegenerateUrl', $url];
        });
        foreach (array_column(self::commands(), 2) as $action) {
            $varnishActionManager->method($action)->willReturnCallback(function () use ($action): void {
                $this->calls[] = [$action];
            });
        }

        return Instantiator::create($commandClass, [VarnishActionManagerInterface::class => $varnishActionManager]);
    }
}
