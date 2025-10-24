<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Tool;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type CodeInterpreterContainerAutoType array{file_ids?: array<int, string>|null, type: 'auto'}
 *
 * @implements ResponseContract<CodeInterpreterContainerAutoType>
 */
final class CodeInterpreterContainerAuto implements ResponseContract
{
    /**
     * @use ArrayAccessible<CodeInterpreterContainerAutoType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  array<int, string>|null  $fileIds
     * @param  'auto'  $type
     */
    private function __construct(public readonly ?array $fileIds, public readonly string $type)
    {
    }
    /**
     * @param  CodeInterpreterContainerAutoType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(fileIds: $attributes['file_ids'] ?? null, type: $attributes['type']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['file_ids' => $this->fileIds, 'type' => $this->type];
    }
}
