<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ImagesContract;
use BackdropOpenAI\OpenAI\Resources\Images;
use BackdropOpenAI\OpenAI\Responses\Images\CreateResponse;
use BackdropOpenAI\OpenAI\Responses\Images\EditResponse;
use BackdropOpenAI\OpenAI\Responses\Images\VariationResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ImagesTestResource implements ImagesContract
{
    use Testable;
    protected function resource(): string
    {
        return Images::class;
    }
    public function create(array $parameters): CreateResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function edit(array $parameters): EditResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function variation(array $parameters): VariationResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
