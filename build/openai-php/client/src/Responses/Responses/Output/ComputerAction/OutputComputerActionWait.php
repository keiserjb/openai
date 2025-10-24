<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Output\ComputerAction;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type WaitType array{type: 'wait'}
 *
 * @implements ResponseContract<WaitType>
 */
final class OutputComputerActionWait implements ResponseContract
{
    /**
     * @use ArrayAccessible<WaitType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  'wait'  $type
     */
    private function __construct(public readonly string $type)
    {
    }
    /**
     * @param  WaitType  $attributes
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
