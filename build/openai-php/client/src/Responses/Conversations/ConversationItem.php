<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Conversations;

use BackdropOpenAI\OpenAI\Actions\Conversations\ItemObjects;
use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Conversations\Objects\Message;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\ComputerToolCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\CustomToolCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\FunctionToolCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\LocalShellCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\McpApprovalResponse;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputCodeInterpreterToolCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputComputerToolCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputCustomToolCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputFileSearchToolCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputFunctionToolCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputImageGenerationToolCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputLocalShellCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputMcpApprovalRequest;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputMcpCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputMcpListTools;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputReasoning;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputWebSearchToolCall;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-import-type ItemObjectTypes from ItemObjects
 *
 * @phpstan-type ConversationItemType ItemObjectTypes
 *
 * @implements ResponseContract<ConversationItemType>
 */
final class ConversationItem implements ResponseContract
{
    /**
     * @use ArrayAccessible<ConversationItemType>
     */
    use ArrayAccessible;
    use Fakeable;
    private function __construct(public readonly Message|OutputFileSearchToolCall|OutputFunctionToolCall|FunctionToolCallOutput|LocalShellCallOutput|McpApprovalResponse|CustomToolCallOutput|OutputWebSearchToolCall|OutputComputerToolCall|ComputerToolCallOutput|OutputReasoning|OutputMcpListTools|OutputMcpApprovalRequest|OutputMcpCall|OutputImageGenerationToolCall|OutputCodeInterpreterToolCall|OutputLocalShellCall|OutputCustomToolCall $item)
    {
    }
    /**
     * @param  ConversationItemType  $attributes
     */
    public static function from(array $attributes): self
    {
        // Lets re-use our existing parser, so we don't have to duplicate the logic.
        // But we need to wrap the attributes in an array, since it expects an array of items.
        $item = ItemObjects::parse([$attributes])[0];
        return new self(item: $item);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->item->toArray();
    }
}
