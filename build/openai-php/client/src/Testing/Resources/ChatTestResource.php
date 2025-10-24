<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ChatContract;
use BackdropOpenAI\OpenAI\Resources\Chat;
use BackdropOpenAI\OpenAI\Responses\Chat\CreateResponse;
use BackdropOpenAI\OpenAI\Responses\StreamResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ChatTestResource implements ChatContract
{
    use Testable;
    protected function resource(): string
    {
        return Chat::class;
    }
    public function create(array $parameters): CreateResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function createStreamed(array $parameters): StreamResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
