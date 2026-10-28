<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Test\Unit\Model\QueueHandler;

use RuntimeException;

final class RecordingServer
{
    private const SCRIPT = __DIR__ . '/../../_files/recording-server.php';

    private $process;
    private array $pipes = [];
    private string $requestLog;
    private int $port;

    private function __construct()
    {
    }

    public static function start(int $responseDelayMilliseconds = 0): self
    {
        $server = new self();
        $server->requestLog = tempnam(sys_get_temp_dir(), 'varnish-warmer-requests-');
        $server->process = proc_open(
            [PHP_BINARY, self::SCRIPT, $server->requestLog, (string) $responseDelayMilliseconds],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $server->pipes
        );

        $port = fgets($server->pipes[1]);
        if ($port === false) {
            throw new RuntimeException('Recording server did not start: ' . stream_get_contents($server->pipes[2]));
        }
        $server->port = (int) $port;

        return $server;
    }

    public static function closedPortUrl(string $path): string
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = parse_url('tcp://' . stream_socket_get_name($socket, false), PHP_URL_PORT);
        fclose($socket);

        return sprintf('http://127.0.0.1:%d/%s', $port, $path);
    }

    public function url(string $path): string
    {
        return sprintf('http://127.0.0.1:%d/%s', $this->port, $path);
    }

    public function hostHeader(): string
    {
        return sprintf('127.0.0.1:%d', $this->port);
    }

    /**
     * @return array<int, array{method: string, target: string, headers: array<string, string>, in_flight: int}>
     */
    public function requests(): array
    {
        $lines = file($this->requestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_map(
            static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
            $lines
        );
    }

    public function stop(): void
    {
        proc_terminate($this->process);
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($this->process);
        unlink($this->requestLog);
    }
}
