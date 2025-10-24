<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Conversations;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type ConversationType array{id: string, object: 'conversation', created_at: int, metadata?: array<string, mixed>}
 *
 * @implements ResponseContract<ConversationType>
 */
final class ConversationResponse implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<ConversationType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    /**
     * @param  'conversation'  $object
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(public readonly string $id, public readonly string $object, public readonly int $createdAt, public readonly array $metadata, private readonly MetaInformation $meta)
    {
    }
    /**
     * Acts as static factory, and returns a new Response instance.
     *
     * @param  ConversationType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(id: $attributes['id'], object: $attributes['object'], createdAt: $attributes['created_at'], metadata: $attributes['metadata'] ?? [], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'object' => $this->object, 'created_at' => $this->createdAt, 'metadata' => $this->metadata];
    }
}
