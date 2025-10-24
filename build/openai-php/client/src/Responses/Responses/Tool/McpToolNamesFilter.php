<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Tool;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type McpToolNamesFilterType array{tool_names: array<int, string>}
 *
 * @implements ResponseContract<McpToolNamesFilterType>
 */
final class McpToolNamesFilter implements ResponseContract
{
    /**
     * @use ArrayAccessible<McpToolNamesFilterType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  array<int, string>  $toolNames
     */
    private function __construct(public readonly array $toolNames)
    {
    }
    /**
     * @param  McpToolNamesFilterType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(toolNames: $attributes['tool_names']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['tool_names' => $this->toolNames];
    }
}
