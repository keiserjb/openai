<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Tool;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type ComputerUseToolType array{display_height: int, display_width: int, environment: string, type: 'computer_use_preview'}
 *
 * @implements ResponseContract<ComputerUseToolType>
 */
final class ComputerUseTool implements ResponseContract
{
    /**
     * @use ArrayAccessible<ComputerUseToolType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  'computer_use_preview'  $type
     */
    private function __construct(public readonly int $displayHeight, public readonly int $displayWidth, public readonly string $environment, public readonly string $type)
    {
    }
    /**
     * @param  ComputerUseToolType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(displayHeight: $attributes['display_height'], displayWidth: $attributes['display_width'], environment: $attributes['environment'], type: $attributes['type']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['display_height' => $this->displayHeight, 'display_width' => $this->displayWidth, 'environment' => $this->environment, 'type' => $this->type];
    }
}
