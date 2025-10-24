<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-type DeleteResponseType array{id: string, object: string, deleted: bool}
 *
 * @implements ResponseContract<DeleteResponseType>
 */
final class DeleteResponse implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<DeleteResponseType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    private function __construct(public readonly string $id, public readonly string $object, public readonly bool $deleted, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  DeleteResponseType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(id: $attributes['id'], object: $attributes['object'], deleted: $attributes['deleted'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'object' => $this->object, 'deleted' => $this->deleted];
    }
}
