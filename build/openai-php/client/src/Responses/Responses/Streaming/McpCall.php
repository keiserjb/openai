<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses\Streaming;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type McpCallType array{sequence_number: int}
 *
 * @implements ResponseContract<McpCallType>
 */
final class McpCall implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<McpCallType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    private function __construct(public readonly int $sequenceNumber, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  McpCallType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(sequenceNumber: $attributes['sequence_number'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['sequence_number' => $this->sequenceNumber];
    }
}
