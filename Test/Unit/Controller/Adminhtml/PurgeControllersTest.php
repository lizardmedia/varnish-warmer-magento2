<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Controller\Adminhtml;

use LizardMedia\VarnishWarmer\Api\ProgressHandler\ProgressBarRendererInterface;
use LizardMedia\VarnishWarmer\Api\ProgressHandler\QueueProgressLoggerInterface;
use LizardMedia\VarnishWarmer\Api\VarnishActionManagerInterface;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeAll;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeGeneral;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeHomepage;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeWildcard;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\Purge\PurgeWildcardForce;
use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeSingle\Run as PurgeSingleRun;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\Factory as MessageFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

final class PurgeControllersTest extends TestCase
{
    private const MAGENTO_ROOT = '/var/www/magento';
    private const LOCK_DATE = '2026-10-22 10:15:00';
    private const PROGRESS_HTML = 'REGENERATE progress: <progress max="10" value="3"></progress> (3/10)';

    private array $requestParams = [];
    private bool $locked = false;
    private array $noticeMessages = [];
    private array $warningMessages = [];
    private array $redirectPaths = [];

    protected function setUp(): void
    {
        ExecSpy::reset();
    }

    public static function purgeActions(): array
    {
        return [
            'homepage' => [PurgeHomepage::class, 'lm-varnish:cache-purge-homepage'],
            'general' => [PurgeGeneral::class, 'lm-varnish:cache-purge-general'],
            'all' => [PurgeAll::class, 'lm-varnish:cache-purge-all'],
            'wildcard' => [PurgeWildcard::class, 'lm-varnish:cache-purge-wildcard'],
            'wildcard force' => [PurgeWildcardForce::class, 'lm-varnish:cache-purge-force'],
        ];
    }

    public static function lockRespectingPurgeActions(): array
    {
        return array_map(
            static fn (array $purgeAction): array => [$purgeAction[0]],
            array_diff_key(self::purgeActions(), ['wildcard force' => true])
        );
    }

    public static function singleUrls(): array
    {
        return [
            'relative path' => ['bizuteria'],
            'wildcard' => ['*'],
            'homepage' => [''],
        ];
    }

    public static function shellInjectionUrls(): array
    {
        return [
            'quote breakout' => ['foo"; echo INJECTED; echo "'],
            'command substitution' => ['$(echo INJECTED)'],
            'backticks' => ['`echo INJECTED`'],
        ];
    }

    #[DataProvider('purgeActions')]
    public function testRunsCommandInBackgroundAndRedirectsToDashboard(string $controllerClass, string $command): void
    {
        $this->createController($controllerClass)->execute();

        self::assertSame([[$command]], $this->argumentsPassedToMagento());
        self::assertCount(1, $this->noticeMessages);
        self::assertSame(['admin/dashboard/index'], $this->redirectPaths);
    }

    #[DataProvider('purgeActions')]
    public function testPassesSelectedStoreViewToCommand(string $controllerClass, string $command): void
    {
        $this->requestParams = ['store_id' => '2'];

        $this->createController($controllerClass)->execute();

        self::assertSame([[$command, '--store=2']], $this->argumentsPassedToMagento());
    }

    #[DataProvider('lockRespectingPurgeActions')]
    public function testDoesNotRunCommandWhenLocked(string $controllerClass): void
    {
        $this->locked = true;

        $this->executeIgnoringFailure($this->createController($controllerClass));

        self::assertSame([], ExecSpy::$commands);
    }

    public function testWildcardForceRunsDespiteLock(): void
    {
        $this->locked = true;

        $this->createController(PurgeWildcardForce::class)->execute();

        self::assertSame([['lm-varnish:cache-purge-force']], $this->argumentsPassedToMagento());
        self::assertSame([], $this->warningMessages);
    }

    #[DataProvider('singleUrls')]
    public function testSingleUrlPurgePassesUrlAndStoreViewToCommand(string $url): void
    {
        $this->requestParams = ['url' => $url, 'store_id' => '1'];

        $this->createController(PurgeSingleRun::class)->execute();

        self::assertSame([['lm-varnish:cache-refresh-url', $url, '--store=1']], $this->argumentsPassedToMagento());
        self::assertSame(['admin/dashboard/index'], $this->redirectPaths);
    }

    public function testSingleUrlPurgeDoesNotRunWhenLockedWithoutForce(): void
    {
        $this->locked = true;
        $this->requestParams = ['url' => 'bizuteria', 'store_id' => '1'];

        $this->executeIgnoringFailure($this->createController(PurgeSingleRun::class));

        self::assertSame([], ExecSpy::$commands);
    }

    public function testSingleUrlPurgeRunsWhenLockedWithForce(): void
    {
        $this->locked = true;
        $this->requestParams = ['url' => 'bizuteria', 'store_id' => '1', 'force_purge' => 'on'];

        $this->createController(PurgeSingleRun::class)->execute();

        self::assertSame([['lm-varnish:cache-refresh-url', 'bizuteria', '--store=1']], $this->argumentsPassedToMagento());
    }

    #[DataProvider('lockRespectingPurgeActions')]
    public function testShowsLockWarningWithStartTimeAndProgressWhenLocked(string $controllerClass): void
    {
        $this->locked = true;

        $this->createController($controllerClass)->execute();

        self::assertCount(1, $this->warningMessages);
        self::assertStringContainsString(self::LOCK_DATE, $this->warningMessages[0]);
        self::assertStringContainsString(self::PROGRESS_HTML, $this->warningMessages[0]);
        self::assertSame(['admin/dashboard/index'], $this->redirectPaths);
    }

    public function testSingleUrlPurgeShowsLockWarningWhenLockedWithoutForce(): void
    {
        $this->locked = true;
        $this->requestParams = ['url' => 'bizuteria', 'store_id' => '1'];

        $this->createController(PurgeSingleRun::class)->execute();

        self::assertCount(1, $this->warningMessages);
        self::assertSame(['admin/dashboard/index'], $this->redirectPaths);
    }

    #[DataProvider('shellInjectionUrls')]
    public function testSingleUrlReachesCommandAsOneLiteralArgument(string $url): void
    {
        $this->requestParams = ['url' => $url, 'store_id' => '1'];

        $this->createController(PurgeSingleRun::class)->execute();

        self::assertSame([['lm-varnish:cache-refresh-url', $url, '--store=1']], $this->argumentsPassedToMagento());
    }

    #[DataProvider('purgeActions')]
    public function testNonNumericStoreViewDoesNotReachShell(string $controllerClass, string $command): void
    {
        $this->requestParams = ['store_id' => '1; echo INJECTED'];

        $this->createController($controllerClass)->execute();

        $argumentLists = $this->argumentsPassedToMagento();
        self::assertNotContains('INJECTED', array_merge([], ...$argumentLists));
        foreach ($argumentLists as $arguments) {
            self::assertSame($command, $arguments[0]);
        }
    }

    private function executeIgnoringFailure(Purge $controller): void
    {
        try {
            $controller->execute();
        } catch (Throwable) {
        }
    }

    private function createController(string $controllerClass): Purge
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            fn (string $key, $defaultValue = null) => $this->requestParams[$key] ?? $defaultValue
        );

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function (string $path) use ($redirect): Redirect {
            $this->redirectPaths[] = $path;

            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $messageManager = $this->createStub(ManagerInterface::class);
        $messageManager->method('addNoticeMessage')->willReturnCallback(
            function ($message) use ($messageManager): ManagerInterface {
                $this->noticeMessages[] = (string) $message;

                return $messageManager;
            }
        );
        $messageManager->method('addMessage')->willReturnCallback(
            function (MessageInterface $message) use ($messageManager): ManagerInterface {
                if ($message->getType() === MessageInterface::TYPE_WARNING) {
                    $this->warningMessages[] = $message->getText();
                }

                return $messageManager;
            }
        );

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getMessageManager')->willReturn($messageManager);

        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn(self::MAGENTO_ROOT);

        $varnishActionManager = $this->createStub(VarnishActionManagerInterface::class);
        $varnishActionManager->method('isLocked')->willReturnCallback(fn (): bool => $this->locked);
        $varnishActionManager->method('getLockMessage')->willReturn(self::LOCK_DATE);

        $progressBarRenderer = $this->createStub(ProgressBarRendererInterface::class);
        $progressBarRenderer->method('getProgressHtml')->willReturn(self::PROGRESS_HTML);

        $messageFactory = $this->createStub(MessageFactory::class);
        $messageFactory->method('create')->willReturnCallback(
            function (string $type, $text): MessageInterface {
                $message = $this->createStub(MessageInterface::class);
                $message->method('getType')->willReturn($type);
                $message->method('getText')->willReturn((string) $text);

                return $message;
            }
        );

        return Instantiator::create($controllerClass, [
            Context::class => $context,
            DirectoryList::class => $directoryList,
            VarnishActionManagerInterface::class => $varnishActionManager,
            QueueProgressLoggerInterface::class => $this->createStub(QueueProgressLoggerInterface::class),
            ProgressBarRendererInterface::class => $progressBarRenderer,
            MessageFactory::class => $messageFactory,
        ]);
    }

    /**
     * @return string[][] argv that bin/magento receives for every background command, as the shell parses it
     */
    private function argumentsPassedToMagento(): array
    {
        $magentoBinary = 'nohup ' . self::MAGENTO_ROOT . '/bin/magento ';
        $silencedInBackground = '>/dev/null 2>&1 &';

        return array_map(
            static function (string $command) use ($magentoBinary, $silencedInBackground): array {
                self::assertStringStartsWith($magentoBinary, $command);
                self::assertStringEndsWith($silencedInBackground, $command);

                $arguments = substr($command, strlen($magentoBinary), -strlen($silencedInBackground));
                $output = (string) shell_exec("printf '%s\\n' " . $arguments);

                return explode("\n", substr($output, 0, -1));
            },
            ExecSpy::$commands
        );
    }
}
