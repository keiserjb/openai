<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\AudioContract;
use BackdropOpenAI\OpenAI\Resources\Audio;
use BackdropOpenAI\OpenAI\Responses\Audio\SpeechStreamResponse;
use BackdropOpenAI\OpenAI\Responses\Audio\TranscriptionResponse;
use BackdropOpenAI\OpenAI\Responses\Audio\TranslationResponse;
use BackdropOpenAI\OpenAI\Responses\StreamResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class AudioTestResource implements AudioContract
{
    use Testable;
    protected function resource(): string
    {
        return Audio::class;
    }
    public function speech(array $parameters): string
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function speechStreamed(array $parameters): SpeechStreamResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function transcribe(array $parameters): TranscriptionResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function transcribeStreamed(array $parameters): StreamResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function translate(array $parameters): TranslationResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
