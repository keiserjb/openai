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
 * @phpstan-type WebSearchCallType array{item_id: string, output_index: int}
 *
 * @implements ResponseContract<WebSearchCallType>
 */
final class WebSearchCall implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<WebSearchCallType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    private function __construct(public readonly string $itemId, public readonly int $outputIndex, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  WebSearchCallType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(itemId: $attributes['item_id'], outputIndex: $attributes['output_index'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['item_id' => $this->itemId, 'output_index' => $this->outputIndex];
    }
}
