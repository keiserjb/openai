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
 * @phpstan-type McpCallArgumentsDoneType array{sequence_number: int, output_index: int, item_id: string, delta: string}
 *
 * @implements ResponseContract<McpCallArgumentsDoneType>
 */
final class McpCallArgumentsDelta implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<McpCallArgumentsDoneType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    private function __construct(public readonly int $sequenceNumber, public readonly int $outputIndex, public readonly string $itemId, public readonly string $delta, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  McpCallArgumentsDoneType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(sequenceNumber: $attributes['sequence_number'], outputIndex: $attributes['output_index'], itemId: $attributes['item_id'], delta: $attributes['delta'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['sequence_number' => $this->sequenceNumber, 'output_index' => $this->outputIndex, 'item_id' => $this->itemId, 'delta' => $this->delta];
    }
}
