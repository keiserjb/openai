<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ThreadsMessagesContract;
use BackdropOpenAI\OpenAI\Resources\ThreadsMessages;
use BackdropOpenAI\OpenAI\Responses\Threads\Messages\ThreadMessageDeleteResponse;
use BackdropOpenAI\OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use BackdropOpenAI\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ThreadsMessagesTestResource implements ThreadsMessagesContract
{
    use Testable;
    public function resource(): string
    {
        return ThreadsMessages::class;
    }
    public function create(string $threadId, array $parameters): ThreadMessageResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function retrieve(string $threadId, string $messageId): ThreadMessageResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function modify(string $threadId, string $messageId, array $parameters): ThreadMessageResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function delete(string $threadId, string $messageId): ThreadMessageDeleteResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function list(string $threadId, array $parameters = []): ThreadMessageListResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
