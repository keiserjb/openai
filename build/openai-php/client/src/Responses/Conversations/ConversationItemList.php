<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Conversations;

use BackdropOpenAI\OpenAI\Actions\Conversations\ItemObjects;
use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-import-type ConversationItemObjectTypes from ItemObjects
 *
 * @phpstan-type ConversationItemListType array{object: 'list', data: ConversationItemObjectTypes, first_id: ?string, last_id: ?string, has_more: bool}
 *
 * @implements ResponseContract<ConversationItemListType>
 */
final class ConversationItemList implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<ConversationItemListType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    /**
     * @param  'list'  $object
     * @param  array<int, ConversationItem>  $data
     */
    private function __construct(public readonly string $object, public readonly array $data, public readonly ?string $firstId, public readonly ?string $lastId, public readonly bool $hasMore, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  ConversationItemListType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        $items = array_map(fn(array $item): ConversationItem => ConversationItem::from($item), $attributes['data']);
        return new self(object: $attributes['object'], data: $items, firstId: $attributes['first_id'] ?? null, lastId: $attributes['last_id'] ?? null, hasMore: $attributes['has_more'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['object' => $this->object, 'data' => array_map(fn(ConversationItem $item): array => $item->toArray(), $this->data), 'first_id' => $this->firstId, 'last_id' => $this->lastId, 'has_more' => $this->hasMore];
    }
}
