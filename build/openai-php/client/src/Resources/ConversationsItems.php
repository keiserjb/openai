<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Resources;

use BackdropOpenAI\OpenAI\Contracts\Resources\ConversationsItemsContract;
use BackdropOpenAI\OpenAI\Responses\Conversations\ConversationItem;
use BackdropOpenAI\OpenAI\Responses\Conversations\ConversationItemList;
use BackdropOpenAI\OpenAI\Responses\Conversations\ConversationResponse;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Payload;
use BackdropOpenAI\OpenAI\ValueObjects\Transporter\Response;
/**
 * @phpstan-import-type ConversationItemType from ConversationItem
 * @phpstan-import-type ConversationItemListType from ConversationItemList
 * @phpstan-import-type ConversationType from ConversationResponse
 */
final class ConversationsItems implements ConversationsItemsContract
{
    use Concerns\Transportable;
    /**
     * {@inheritdoc}
     */
    public function create(string $conversationId, array $parameters): ConversationItemList
    {
        $payload = Payload::create("conversations/{$conversationId}/items", $parameters);
        /** @var Response<ConversationItemListType> $response */
        $response = $this->transporter->requestObject($payload);
        return ConversationItemList::from($response->data(), $response->meta());
    }
    /**
     * {@inheritdoc}
     */
    public function list(string $conversationId, array $parameters = []): ConversationItemList
    {
        $payload = Payload::list("conversations/{$conversationId}/items", $parameters);
        /** @var Response<ConversationItemListType> $response */
        $response = $this->transporter->requestObject($payload);
        return ConversationItemList::from($response->data(), $response->meta());
    }
    /**
     * {@inheritdoc}
     */
    public function retrieve(string $conversationId, string $itemId, array $parameters = []): ConversationItem
    {
        // include query params if provided
        $base = "conversations/{$conversationId}/items";
        $payload = Payload::retrieve($base, $itemId, '', $parameters);
        /** @var Response<ConversationItemType> $response */
        $response = $this->transporter->requestObject($payload);
        return ConversationItem::from($response->data());
    }
    /**
     * {@inheritdoc}
     */
    public function delete(string $conversationId, string $itemId): ConversationResponse
    {
        $payload = Payload::delete("conversations/{$conversationId}/items", $itemId);
        /** @var Response<ConversationType> $response */
        $response = $this->transporter->requestObject($payload);
        return ConversationResponse::from($response->data(), $response->meta());
    }
}
