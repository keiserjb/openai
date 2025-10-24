<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Output;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type OutputWebSearchToolCallType array{id: string, status: string, type: 'web_search_call'}
 *
 * @implements ResponseContract<OutputWebSearchToolCallType>
 */
final class OutputWebSearchToolCall implements ResponseContract
{
    /**
     * @use ArrayAccessible<OutputWebSearchToolCallType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  'web_search_call'  $type
     */
    private function __construct(public readonly string $id, public readonly string $status, public readonly string $type)
    {
    }
    /**
     * @param  OutputWebSearchToolCallType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(id: $attributes['id'], status: $attributes['status'], type: $attributes['type']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'status' => $this->status, 'type' => $this->type];
    }
}
