<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type IncompleteDetailsType array{reason: string}
 *
 * @implements ResponseContract<IncompleteDetailsType>
 */
final class CreateResponseIncompleteDetails implements ResponseContract
{
    /**
     * @use ArrayAccessible<IncompleteDetailsType>
     */
    use ArrayAccessible;
    use Fakeable;
    private function __construct(public readonly string $reason)
    {
    }
    /**
     * @param  IncompleteDetailsType  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self(reason: $attributes['reason']);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['reason' => $this->reason];
    }
}
