<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\EmbeddingsContract;
use BackdropOpenAI\OpenAI\Resources\Embeddings;
use BackdropOpenAI\OpenAI\Responses\Embeddings\CreateResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class EmbeddingsTestResource implements EmbeddingsContract
{
    use Testable;
    protected function resource(): string
    {
        return Embeddings::class;
    }
    public function create(array $parameters): CreateResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
