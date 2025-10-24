<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Tool;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-import-type CodeInterpreterContainerAutoType from CodeInterpreterContainerAuto
 *
 * @phpstan-type CodeInterpreterToolType array{container: string|CodeInterpreterContainerAutoType, type: 'code_interpreter'}
 *
 * @implements ResponseContract<CodeInterpreterToolType>
 */
final class CodeInterpreterTool implements ResponseContract
{
    /**
     * @use ArrayAccessible<CodeInterpreterToolType>
     */
    use ArrayAccessible;
    use Fakeable;
    /**
     * @param  'code_interpreter'  $type
     */
    private function __construct(public readonly string|CodeInterpreterContainerAuto $container, public readonly string $type)
    {
    }
    /**
     * @param  CodeInterpreterToolType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(container: is_string($attributes['container']) ? $attributes['container'] : CodeInterpreterContainerAuto::from($attributes['container']), type: $attributes['type']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['container' => $this->container instanceof CodeInterpreterContainerAuto ? $this->container->toArray() : $this->container, 'type' => $this->type];
    }
}
