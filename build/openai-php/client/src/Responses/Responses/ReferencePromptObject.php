<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type ReferencePromptObjectType array{id: string, variables?: array<string, string>|null, version?: string|null}
 *
 * @implements ResponseContract<ReferencePromptObjectType>
 */
final class ReferencePromptObject implements ResponseContract
{
    /**
     * @use ArrayAccessible<ReferencePromptObjectType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  array<string, string>|null  $variables
     */
    private function __construct(public readonly string $id, public readonly ?array $variables = null, public readonly ?string $version = null)
    {
    }
    /**
     * @param  ReferencePromptObjectType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(id: $attributes['id'], variables: $attributes['variables'] ?? null, version: $attributes['version'] ?? null);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'variables' => $this->variables, 'version' => $this->version];
    }
}
