<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Models;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @implements ResponseContract<array{id: string, object: string, created: ?int, created_at?: ?int, owned_by: ?string}>
 */
final class RetrieveResponse implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<array{id: string, object: string, created: ?int, owned_by: ?string}>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    private function __construct(public readonly string $id, public readonly string $object, public readonly ?int $created, public readonly ?string $ownedBy, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  array{id: string, object: string, created: ?int, created_at?: ?int, owned_by: ?string}  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(id: $attributes['id'], object: $attributes['object'], created: $attributes['created'] ?? $attributes['created_at'] ?? null, ownedBy: $attributes['owned_by'] ?? null, meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'object' => $this->object, 'created' => $this->created, 'owned_by' => $this->ownedBy];
    }
}
