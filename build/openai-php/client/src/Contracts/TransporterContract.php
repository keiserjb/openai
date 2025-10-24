<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Contracts;

use BackdropOpenAI\OpenAI\Exceptions\ErrorException;
use BackdropOpenAI\OpenAI\Exceptions\TransporterException;
use BackdropOpenAI\OpenAI\Exceptions\UnserializableResponse;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\AdaptableResponse;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Payload;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Response;
use BackdropOpenAI\Psr\Http\Message\ResponseInterface;
/**
 * @internal
 */
interface TransporterContract
{
    /**
     * Adds a custom header that will be included in all subsequent requests.
     */
    public function addHeader(string $name, string $value): self;
    /**
     * Sends a request to a server expecting an object back.
     *
     * @return Response<array<array-key, mixed>>
     *
     * @throws ErrorException|UnserializableResponse|TransporterException
     */
    public function requestObject(Payload $payload): Response;
    /**
     * Sends a request to a server expecting an adaptable response (object/string) back.
     *
     * @return AdaptableResponse<array<array-key, mixed>|string>
     *
     * @throws ErrorException|UnserializableResponse|TransporterException
     */
    public function requestStringOrObject(Payload $payload): AdaptableResponse;
    /**
     * Sends a content request to a server expecting a string back.
     *
     * @throws ErrorException|TransporterException
     */
    public function requestContent(Payload $payload): string;
    /**
     * Sends a stream request to a server.
     **
     * @throws ErrorException
     */
    public function requestStream(Payload $payload): ResponseInterface;
}
