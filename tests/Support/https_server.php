<?php

// Minimal HTTPS stand-in (a self-signed certificate valid for 127.0.0.1) that answers every request
// with 200 and a small JSON body, and appends each request it receives to an event log as one JSON line.
// Usage: php https_server.php <port> <pem-file> <event-log-file>

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || count($arguments) !== 4) {
    fwrite(STDERR, "Usage: php https_server.php <port> <pem-file> <event-log-file>\n");
    exit(2);
}
[$script, $port, $pem, $eventLog] = array_map(static fn (mixed $value): string => is_scalar($value) ? (string)$value : '', $arguments);

$context = stream_context_create(['ssl' => ['local_cert' => $pem, 'verify_peer' => false, 'allow_self_signed' => true]]);
$server = stream_socket_server("tls://127.0.0.1:$port", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
if ($server === false) {
    fwrite(STDERR, 'cannot listen: ' . (is_string($errstr) ? $errstr : 'unknown error') . "\n");
    exit(1);
}

for (;;) {
    $connection = @stream_socket_accept($server, 3600);
    if ($connection === false) {
        continue; // failed handshake or timeout
    }
    stream_set_timeout($connection, 5);
    $head = '';
    while (($line = fgets($connection)) !== false) {
        $head .= $line;
        if ($line === "\r\n") {
            break;
        }
    }
    $length = preg_match('/^Content-Length:\s*(\d+)/mi', $head, $match) === 1 ? (int)$match[1] : 0;
    $body = $length > 0 ? (string)fread($connection, $length) : '';
    if ($head !== '') {
        $authorization = preg_match('/^Authorization:\s*(.*?)\r?$/mi', $head, $auth) === 1 ? $auth[1] : '';
        file_put_contents($eventLog, json_encode(['request' => strtok($head, "\r\n"), 'authorization' => $authorization, 'body' => $body]) . "\n", FILE_APPEND);
        $response = '{"success":1,"failure":0}';
        fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: " . strlen($response) . "\r\nConnection: close\r\n\r\n" . $response);
    }
    fclose($connection);
}
