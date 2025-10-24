<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ContainerFileContract;
use BackdropOpenAI\OpenAI\Contracts\Resources\ContainersContract;
use BackdropOpenAI\OpenAI\Resources\Containers;
use BackdropOpenAI\OpenAI\Responses\Containers\CreateContainer;
use BackdropOpenAI\OpenAI\Responses\Containers\DeleteContainer;
use BackdropOpenAI\OpenAI\Responses\Containers\ListContainers;
use BackdropOpenAI\OpenAI\Responses\Containers\RetrieveContainer;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ContainersTestResource implements ContainersContract
{
    use Testable;
    public function resource(): string
    {
        return Containers::class;
    }
    public function create(array $parameters): CreateContainer
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function retrieve(string $id): RetrieveContainer
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function list(array $parameters = []): ListContainers
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function delete(string $id): DeleteContainer
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function files(): ContainerFileContract
    {
        return new ContainerFileTestResource($this->fake);
    }
}
