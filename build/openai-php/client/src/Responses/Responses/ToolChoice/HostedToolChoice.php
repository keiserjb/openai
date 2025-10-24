<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\ToolChoice;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type HostedToolChoiceType array{type: 'file_search'|'web_search'|'web_search_preview'|'computer_use_preview'}
 *
 * @implements ResponseContract<HostedToolChoiceType>
 */
final class HostedToolChoice implements ResponseContract
{
    /**
     * @use ArrayAccessible<HostedToolChoiceType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  'file_search'|'web_search'|'web_search_preview'|'computer_use_preview'  $type
     */
    private function __construct(public readonly string $type)
    {
    }
    /**
     * @param  HostedToolChoiceType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(type: $attributes['type']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['type' => $this->type];
    }
}
