<?php

namespace App\Debug;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TracyMiddleware implements MiddlewareInterface
{
    public static array $requestData = [];
    public static array $responseData = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uploaded = [];
        foreach ($request->getUploadedFiles() as $key => $file) {
            $uploaded[$key] = [
                'name' => $file->getClientFilename(),
                'size' => $file->getSize(),
                'media_type' => $file->getClientMediaType(),
                'error' => $file->getError(),
            ];
        }

        self::$requestData = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'scheme' => $request->getUri()->getScheme(),
            'host' => $request->getUri()->getHost(),
            'port' => $request->getUri()->getPort(),
            'path' => $request->getUri()->getPath(),
            'query_string' => $request->getUri()->getQuery(),
            'headers' => $request->getHeaders(),
            'query_params' => $request->getQueryParams(),
            'parsed_body' => $request->getParsedBody(),
            'cookies' => $request->getCookieParams(),
            'uploaded_files' => $uploaded,
            'server_params' => $request->getServerParams(),
            'attributes' => array_map(fn ($v) => is_object($v) ? '[' . $v::class . ']' : $v, $request->getAttributes()),
            'content_type' => $request->getHeaderLine('Content-Type'),
            'content_length' => $request->getHeaderLine('Content-Length'),
            'protocol_version' => 'HTTP/' . $request->getProtocolVersion(),
        ];

        $response = $handler->handle($request);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $response->getBody()->rewind();

        self::$responseData = [
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'body' => mb_strlen($body) > 2000
                ? mb_substr($body, 0, 2000) . "\n\n... [truncated, " . mb_strlen($body) . ' total bytes]'
                : $body,
            'protocol_version' => 'HTTP/' . $response->getProtocolVersion(),
        ];

        return $response;
    }
}
