<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Containers;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Containers\Objects\ExpiresAfter;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-import-type ExpiresAfterType from ExpiresAfter
 *
 * @phpstan-type CreateContainerType array{id: string, object: 'container', created_at: int, status: string, expires_after: ExpiresAfterType, last_active_at: int, name: string}
 *
 * @implements ResponseContract<CreateContainerType>
 */
final class CreateContainer implements ResponseContract, ResponseHasMetaInformationContract
{
    /**
     * @use ArrayAccessible<CreateContainerType>
     */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    /**
     * @param  'container'  $object
     */
    private function __construct(public readonly string $id, public readonly string $object, public readonly int $createdAt, public readonly string $status, public readonly ExpiresAfter $expiresAfter, public readonly int $lastActiveAt, public readonly string $name, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  CreateContainerType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        return new self(id: $attributes['id'], object: $attributes['object'], createdAt: $attributes['created_at'], status: $attributes['status'], expiresAfter: ExpiresAfter::from($attributes['expires_after']), lastActiveAt: $attributes['last_active_at'], name: $attributes['name'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'object' => $this->object, 'created_at' => $this->createdAt, 'status' => $this->status, 'expires_after' => $this->expiresAfter->toArray(), 'last_active_at' => $this->lastActiveAt, 'name' => $this->name];
    }
}
