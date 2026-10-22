<?php

declare(strict_types=1);

/**
 * @author Paweł Majcherczyk <pawel.majcherczyk@lizardmedia.pl>
 * @copyright Copyright (C) 2026 Lizard Media (http://lizardmedia.pl)
 */

[, $requestLog, $responseDelayMilliseconds] = $argv;

$server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
if ($server === false) {
    fwrite(STDERR, $errorMessage);
    exit(1);
}
stream_set_blocking($server, false);

fwrite(STDOUT, parse_url('tcp://' . stream_socket_get_name($server, false), PHP_URL_PORT) . PHP_EOL);
fflush(STDOUT);

$clients = [];
$buffers = [];
$responseDueAt = [];

while (true) {
    $readable = [$server, ...array_values($clients)];
    $writable = null;
    $failed = null;
    if (stream_select($readable, $writable, $failed, 0, 10000) === false) {
        exit(1);
    }

    foreach ($readable as $socket) {
        if ($socket === $server) {
            $client = stream_socket_accept($server, 0);
            if ($client !== false) {
                stream_set_blocking($client, false);
                $clients[(int) $client] = $client;
                $buffers[(int) $client] = '';
            }
            continue;
        }

        $clientId = (int) $socket;
        $chunk = fread($socket, 8192);
        if ($chunk === '' || $chunk === false) {
            if (feof($socket)) {
                fclose($socket);
                unset($clients[$clientId], $buffers[$clientId], $responseDueAt[$clientId]);
            }
            continue;
        }

        $buffers[$clientId] .= $chunk;
        if (isset($responseDueAt[$clientId]) || !str_contains($buffers[$clientId], "\r\n\r\n")) {
            continue;
        }

        $headerLines = explode("\r\n", strstr($buffers[$clientId], "\r\n\r\n", true));
        [$method, $target] = explode(' ', array_shift($headerLines));
        $headers = [];
        foreach ($headerLines as $headerLine) {
            [$name, $value] = explode(':', $headerLine, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        $responseDueAt[$clientId] = microtime(true) + ((int) $responseDelayMilliseconds) / 1000;
        file_put_contents(
            $requestLog,
            json_encode([
                'method' => $method,
                'target' => $target,
                'headers' => $headers,
                'in_flight' => count($responseDueAt),
            ]) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    foreach ($responseDueAt as $clientId => $dueAt) {
        if ($dueAt > microtime(true)) {
            continue;
        }
        fwrite($clients[$clientId], "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 2\r\nConnection: close\r\n\r\nOK");
        fclose($clients[$clientId]);
        unset($clients[$clientId], $buffers[$clientId], $responseDueAt[$clientId]);
    }
}
