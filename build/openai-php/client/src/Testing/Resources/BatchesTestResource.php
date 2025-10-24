<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\BatchesContract;
use BackdropOpenAI\OpenAI\Resources\Batches;
use BackdropOpenAI\OpenAI\Responses\Batches\BatchListResponse;
use BackdropOpenAI\OpenAI\Responses\Batches\BatchResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class BatchesTestResource implements BatchesContract
{
    use Testable;
    public function resource(): string
    {
        return Batches::class;
    }
    public function create(array $parameters): BatchResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function retrieve(string $id): BatchResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function cancel(string $id): BatchResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function list(array $parameters = []): BatchListResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
