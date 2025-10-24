<?php

namespace BackdropOpenAI\OpenAI\Testing\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ContainerFileContract;
use BackdropOpenAI\OpenAI\Resources\ContainerFile;
use BackdropOpenAI\OpenAI\Responses\Containers\Files\ContainerFileDeleteResponse;
use BackdropOpenAI\OpenAI\Responses\Containers\Files\ContainerFileListResponse;
use BackdropOpenAI\OpenAI\Responses\Containers\Files\ContainerFileResponse;
use BackdropOpenAI\OpenAI\Testing\Resources\Concerns\Testable;
final class ContainerFileTestResource implements ContainerFileContract
{
    use Testable;
    public function resource(): string
    {
        return ContainerFile::class;
    }
    public function create(string $containerId, array $parameters = []): ContainerFileResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function list(string $containerId, array $parameters = []): ContainerFileListResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function retrieve(string $containerId, string $fileId): ContainerFileResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function content(string $containerId, string $fileId): string
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
    public function delete(string $containerId, string $fileId): ContainerFileDeleteResponse
    {
        return $this->record(__FUNCTION__, func_get_args());
    }
}
