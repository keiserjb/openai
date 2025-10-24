<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ResponsesContract;
use BackdropOpenAI\OpenAI\Resources\Responses;
use BackdropOpenAI\OpenAI\Responses\Responses\CreateResponse;
use BackdropOpenAI\OpenAI\Responses\Responses\DeleteResponse;
use BackdropOpenAI\OpenAI\Responses\Responses\ListInputItems;
use BackdropOpenAI\OpenAI\Responses\Responses\RetrieveResponse;
use BackdropOpenAI\OpenAI\Responses\StreamResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ResponsesTestResource implements ResponsesContract
{
    use Testable;
    public function resource(): string
    {
        return Responses::class;
    }
    public function conversations(): ConversationsTestResource
    {
        return new ConversationsTestResource($this->fake);
    }
    public function create(array $parameters): CreateResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function createStreamed(array $parameters): StreamResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function retrieve(string $id): RetrieveResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function list(string $id, array $parameters = []): ListInputItems
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function cancel(string $id): RetrieveResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function delete(string $id): DeleteResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
