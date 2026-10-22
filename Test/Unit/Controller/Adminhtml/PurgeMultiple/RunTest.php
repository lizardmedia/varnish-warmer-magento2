<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Controller\Adminhtml\PurgeMultiple;

use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeMultiple\Run;
use LizardMedia\VarnishWarmer\Model\FormDataProvider\MultipleUrlsPurgeCommandsProvider;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RunTest extends TestCase
{
    private array $requestParams = [];
    private array $requestedAdminRoutes = [];
    private array $redirectUrls = [];
    private array $errorMessages = [];

    public static function purgeProcesses(): array
    {
        $processPaths = array_keys((new MultipleUrlsPurgeCommandsProvider())->getCommandArray());

        return array_combine(
            $processPaths,
            array_map(static fn (string $processPath): array => [$processPath], $processPaths)
        );
    }

    #[DataProvider('purgeProcesses')]
    public function testRedirectsToSelectedProcessWithStoreView(string $processPath): void
    {
        $this->requestParams = ['process_url' => $processPath, 'store_id' => '2'];

        $this->createController()->execute();

        self::assertSame([[$processPath, ['store_id' => '2']]], $this->requestedAdminRoutes);
        self::assertSame(['https://admin.test/' . $processPath], $this->redirectUrls);
        self::assertSame([], $this->errorMessages);
    }

    public function testRejectsAdminRouteOutsideOfPurgeProcesses(): void
    {
        $this->requestParams = ['process_url' => 'admin/user/delete', 'store_id' => '2'];

        $this->createController()->execute();

        self::assertSame([['lizardmediavarnish/purgemultiple/form', []]], $this->requestedAdminRoutes);
        self::assertCount(1, $this->errorMessages);
    }

    private function createController(): Run
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            fn (string $key, $defaultValue = null) => $this->requestParams[$key] ?? $defaultValue
        );

        $response = $this->createStub(HttpResponse::class);
        $response->method('setRedirect')->willReturnCallback(function (string $url) use ($response): HttpResponse {
            $this->redirectUrls[] = $url;

            return $response;
        });

        $backendHelper = $this->createStub(BackendHelper::class);
        $backendHelper->method('getUrl')->willReturnCallback(function (string $route, array $params = []): string {
            $this->requestedAdminRoutes[] = [$route, $params];

            return 'https://admin.test/' . $route;
        });

        $messageManager = $this->createStub(ManagerInterface::class);
        $messageManager->method('addErrorMessage')->willReturnCallback(
            function ($message) use ($messageManager): ManagerInterface {
                $this->errorMessages[] = (string) $message;

                return $messageManager;
            }
        );

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($response);
        $context->method('getHelper')->willReturn($backendHelper);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getSession')->willReturn($this->createStub(Session::class));
        $context->method('getActionFlag')->willReturn($this->createStub(ActionFlag::class));

        return Instantiator::create(Run::class, [
            Context::class => $context,
            MultipleUrlsPurgeCommandsProvider::class => new MultipleUrlsPurgeCommandsProvider(),
        ]);
    }
}
