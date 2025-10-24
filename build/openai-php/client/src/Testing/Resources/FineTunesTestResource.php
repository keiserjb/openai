<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\FineTunesContract;
use BackdropOpenAI\OpenAI\Resources\FineTunes;
use BackdropOpenAI\OpenAI\Responses\FineTunes\ListEventsResponse;
use BackdropOpenAI\OpenAI\Responses\FineTunes\ListResponse;
use BackdropOpenAI\OpenAI\Responses\FineTunes\RetrieveResponse;
use BackdropOpenAI\OpenAI\Responses\StreamResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class FineTunesTestResource implements FineTunesContract
{
    use Testable;
    protected function resource(): string
    {
        return FineTunes::class;
    }
    public function create(array $parameters): RetrieveResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function list(): ListResponse
    {
        return $this->record(__FUNCTION__);
    }
    public function retrieve(string $fineTuneId): RetrieveResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function cancel(string $fineTuneId): RetrieveResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function listEvents(string $fineTuneId): ListEventsResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function listEventsStreamed(string $fineTuneId): StreamResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
