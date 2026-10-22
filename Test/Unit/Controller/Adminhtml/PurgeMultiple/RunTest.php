<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Controller\Adminhtml\PurgeMultiple;

use LizardMedia\VarnishWarmer\Controller\Adminhtml\PurgeMultiple\Run;
use LizardMedia\VarnishWarmer\Test\Unit\Instantiator;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class RunTest extends TestCase
{
    private array $requestParams = [];
    private array $requestedAdminRoutes = [];
    private array $redirectUrls = [];

    public function testRedirectsToSelectedProcessWithStoreView(): void
    {
        $this->requestParams = ['process_url' => 'lizardmediavarnish/purge/purgeall', 'store_id' => '2'];

        $this->createController()->execute();

        self::assertSame([['lizardmediavarnish/purge/purgeall', ['store_id' => '2']]], $this->requestedAdminRoutes);
        self::assertSame(['https://admin.test/lizardmediavarnish/purge/purgeall'], $this->redirectUrls);
    }

    #[Group('known-bug')]
    public function testDoesNotRedirectToAdminRouteOutsideOfPurgeProcesses(): void
    {
        $this->requestParams = ['process_url' => 'admin/user/delete', 'store_id' => '2'];

        $this->createController()->execute();

        self::assertNotContains('admin/user/delete', array_column($this->requestedAdminRoutes, 0));
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

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($response);
        $context->method('getHelper')->willReturn($backendHelper);
        $context->method('getSession')->willReturn($this->createStub(Session::class));
        $context->method('getActionFlag')->willReturn($this->createStub(ActionFlag::class));

        return Instantiator::create(Run::class, [Context::class => $context]);
    }
}
