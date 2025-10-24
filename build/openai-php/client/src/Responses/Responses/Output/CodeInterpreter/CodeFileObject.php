<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Output\CodeInterpreter;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type CodeFileObjectType array{file_id: string, mime_type: string}
 *
 * @implements ResponseContract<CodeFileObjectType>
 */
final class CodeFileObject implements ResponseContract
{
    /**
     * @use ArrayAccessible<CodeFileObjectType>
     */
    use ArrayAccessible;
    use Fakeable;
    private function __construct(public readonly string $fileId, public readonly string $mimeType)
    {
    }
    /**
     * @param  CodeFileObjectType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(fileId: $attributes['file_id'], mimeType: $attributes['mime_type']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['file_id' => $this->fileId, 'mime_type' => $this->mimeType];
    }
}
