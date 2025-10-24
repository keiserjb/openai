<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\EditsContract;
use BackdropOpenAI\OpenAI\Resources\Edits;
use BackdropOpenAI\OpenAI\Responses\Edits\CreateResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class EditsTestResource implements EditsContract
{
    use Testable;
    protected function resource(): string
    {
        return Edits::class;
    }
    public function create(array $parameters): CreateResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
