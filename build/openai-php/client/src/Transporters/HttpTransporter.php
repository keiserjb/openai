<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Transporters;

use Closure;
use BackdropOpenAI\GuzzleHttp\Exception\ClientException;
use JsonException;
use BackdropOpenAI\OpenAI\Contracts\TransporterContract;
use BackdropOpenAI\OpenAI\Enums\Transporter\ContentType;
use BackdropOpenAI\OpenAI\Exceptions\ErrorException;
use BackdropOpenAI\OpenAI\Exceptions\RateLimitException;
use BackdropOpenAI\OpenAI\Exceptions\TransporterException;
use BackdropOpenAI\OpenAI\Exceptions\UnserializableResponse;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\AdaptableResponse;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\BaseUri;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Headers;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Payload;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\QueryParams;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Response;
use BackdropOpenAI\Psr\Http\Client\ClientExceptionInterface;
use BackdropOpenAI\Psr\Http\Client\ClientInterface;
use BackdropOpenAI\Psr\Http\Message\ResponseInterface;
/**
 * @internal
 */
final class HttpTransporter implements TransporterContract
{
    /**
     * Creates a new Http Transporter instance.
     */
    public function __construct(private readonly ClientInterface $client, private readonly BaseUri $baseUri, private Headers $headers, private readonly QueryParams $queryParams, private readonly Closure $streamHandler)
    {
        // ..
    }
    /**
     * {@inheritDoc}
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers = $this->headers->withCustomHeader($name, $value);
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function requestObject(Payload $payload): Response
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);
        $response = $this->sendRequest(fn(): \BackdropOpenAI\Psr\Http\Message\ResponseInterface => $this->client->sendRequest($request));
        $contents = (string) $response->getBody();
        $this->throwIfRateLimit($response);
        $this->throwIfJsonError($response, $contents);
        try {
            /** @var array{error?: array{message: string, type: string, code: string}} $data */
            $data = json_decode($contents, \true, flags: \JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new UnserializableResponse($jsonException, $response);
        }
        return Response::from($data, $response->getHeaders());
    }
    /**
     * {@inheritDoc}
     */
    public function requestStringOrObject(Payload $payload): AdaptableResponse
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);
        $response = $this->sendRequest(fn(): \BackdropOpenAI\Psr\Http\Message\ResponseInterface => $this->client->sendRequest($request));
        $contents = (string) $response->getBody();
        if (str_contains($response->getHeaderLine('Content-Type'), ContentType::TEXT_PLAIN->value)) {
            return AdaptableResponse::from($contents, $response->getHeaders());
        }
        $this->throwIfRateLimit($response);
        $this->throwIfJsonError($response, $contents);
        try {
            /** @var array{error?: array{message: string, type: string, code: string}} $data */
            $data = json_decode($contents, \true, flags: \JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new UnserializableResponse($jsonException, $response);
        }
        return AdaptableResponse::from($data, $response->getHeaders());
    }
    /**
     * {@inheritDoc}
     */
    public function requestContent(Payload $payload): string
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);
        $response = $this->sendRequest(fn(): \BackdropOpenAI\Psr\Http\Message\ResponseInterface => $this->client->sendRequest($request));
        $contents = (string) $response->getBody();
        $this->throwIfRateLimit($response);
        $this->throwIfJsonError($response, $contents);
        return $contents;
    }
    /**
     * {@inheritDoc}
     */
    public function requestStream(Payload $payload): ResponseInterface
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);
        $response = $this->sendRequest(fn() => ($this->streamHandler)($request));
        $this->throwIfRateLimit($response);
        $this->throwIfJsonError($response, $response);
        return $response;
    }
    private function sendRequest(Closure $callable): ResponseInterface
    {
        try {
            return $callable();
        } catch (ClientExceptionInterface $clientException) {
            if ($clientException instanceof ClientException) {
                $this->throwIfJsonError($clientException->getResponse(), (string) $clientException->getResponse()->getBody());
            }
            throw new TransporterException($clientException);
        }
    }
    private function throwIfRateLimit(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 429) {
            return;
        }
        throw new RateLimitException($response);
    }
    private function throwIfJsonError(ResponseInterface $response, string|ResponseInterface $contents): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }
        if (!str_contains($response->getHeaderLine('Content-Type'), ContentType::JSON->value)) {
            return;
        }
        if ($contents instanceof ResponseInterface) {
            $contents = (string) $contents->getBody();
        }
        try {
            /** @var array{error?: string|array{message: string|array<int, string>, type: string, code: string}} $data */
            $data = json_decode($contents, \true, flags: \JSON_THROW_ON_ERROR);
            if (isset($data['error'])) {
                throw new ErrorException($data['error'], $response);
            }
        } catch (JsonException $jsonException) {
            throw new UnserializableResponse($jsonException, $response);
        }
    }
}
