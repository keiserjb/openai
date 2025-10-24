<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\RealtimeContract;
use BackdropOpenAI\OpenAI\Resources\Realtime;
use BackdropOpenAI\OpenAI\Responses\Realtime\SessionResponse;
use BackdropOpenAI\OpenAI\Responses\Realtime\TranscriptionSessionResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class RealtimeTestResource implements RealtimeContract
{
    use Testable;
    public function resource(): string
    {
        return Realtime::class;
    }
    public function token(array $parameters = []): SessionResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function transcribeToken(array $parameters = []): TranscriptionSessionResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
