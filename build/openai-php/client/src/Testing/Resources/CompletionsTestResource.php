<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\CompletionsContract;
use BackdropOpenAI\OpenAI\Resources\Completions;
use BackdropOpenAI\OpenAI\Responses\Completions\CreateResponse;
use BackdropOpenAI\OpenAI\Responses\StreamResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class CompletionsTestResource implements CompletionsContract
{
    use Testable;
    protected function resource(): string
    {
        return Completions::class;
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
