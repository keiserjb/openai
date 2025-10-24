<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ModerationsContract;
use BackdropOpenAI\OpenAI\Resources\Moderations;
use BackdropOpenAI\OpenAI\Responses\Moderations\CreateResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ModerationsTestResource implements ModerationsContract
{
    use Testable;
    protected function resource(): string
    {
        return Moderations::class;
    }
    public function create(array $parameters): CreateResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
