<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources\Concerns;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseStreamContract;
use BackdropOpenAI\OpenAI\Testing\ClientFake;
use BackdropOpenAI\OpenAI\Testing\Requests\TestRequest;
trait Testable
{
    public function __construct(protected ClientFake $fake)
    {
    }
    abstract protected function resource(): string;
    /**
     * @param  array<string, mixed>  $args
     */
    protected function record(string $method, array $args = []): ResponseContract|ResponseStreamContract|string
    {
        return $this->fake->record(new TestRequest($this->resource(), $method, $args));
    }
    public function assertSent(callable|int|null $callback = null): void
    {
        $this->fake->assertSent($this->resource(), $callback);
    }
    public function assertNotSent(callable|int|null $callback = null): void
    {
        $this->fake->assertNotSent($this->resource(), $callback);
    }
}
