<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ThreadsContract;
use BackdropOpenAI\OpenAI\Resources\Threads;
use BackdropOpenAI\OpenAI\Responses\StreamResponse;
use BackdropOpenAI\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use BackdropOpenAI\OpenAI\Responses\Threads\ThreadDeleteResponse;
use BackdropOpenAI\OpenAI\Responses\Threads\ThreadResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ThreadsTestResource implements ThreadsContract
{
    use Testable;
    public function resource(): string
    {
        return Threads::class;
    }
    public function create(array $parameters): ThreadResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function createAndRun(array $parameters): ThreadRunResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function createAndRunStreamed(array $parameters): StreamResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function retrieve(string $id): ThreadResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function modify(string $id, array $parameters): ThreadResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function delete(string $id): ThreadDeleteResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function messages(): ThreadsMessagesTestResource
    {
        return new ThreadsMessagesTestResource($this->fake);
    }
    public function runs(): ThreadsRunsTestResource
    {
        return new ThreadsRunsTestResource($this->fake);
    }
}
