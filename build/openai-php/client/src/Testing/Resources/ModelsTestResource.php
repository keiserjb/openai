<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ModelsContract;
use BackdropOpenAI\OpenAI\Resources\Models;
use BackdropOpenAI\OpenAI\Responses\Models\DeleteResponse;
use BackdropOpenAI\OpenAI\Responses\Models\ListResponse;
use BackdropOpenAI\OpenAI\Responses\Models\RetrieveResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ModelsTestResource implements ModelsContract
{
    use Testable;
    protected function resource(): string
    {
        return Models::class;
    }
    public function list(): ListResponse
    {
        return $this->record(__FUNCTION__);
    }
    public function retrieve(string $model): RetrieveResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function delete(string $model): DeleteResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
