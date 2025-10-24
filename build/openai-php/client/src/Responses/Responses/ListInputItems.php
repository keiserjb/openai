<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses;

use BackdropOpenAI\OpenAI\Actions\Responses\ItemObjects;
use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Contracts\ResponseHasMetaInformationContract;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Concerns\HasMetaInformation;
use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\ComputerToolCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\CustomToolCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\FunctionToolCallOutput;
use BackdropOpenAI\OpenAI\Responses\Responses\Input\InputMessage;
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
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputMessage;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputReasoning;
use BackdropOpenAI\OpenAI\Responses\Responses\Output\OutputWebSearchToolCall;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\Fakeable;
/**
 * @phpstan-import-type ResponseItemObjectTypes from ItemObjects
 *
 * @phpstan-type ListInputItemsType array{data: ResponseItemObjectTypes, first_id: string, has_more: bool, last_id: string, object: 'list'}
 *
 * @implements ResponseContract<ListInputItemsType>
 */
final class ListInputItems implements ResponseContract, ResponseHasMetaInformationContract
{
    /** @use ArrayAccessible<ListInputItemsType> */
    use ArrayAccessible;
    use Fakeable;
    use HasMetaInformation;
    /**
     * @param  array<int, InputMessage|OutputMessage|OutputFileSearchToolCall|OutputComputerToolCall|ComputerToolCallOutput|LocalShellCallOutput|McpApprovalResponse|CustomToolCallOutput|OutputWebSearchToolCall|OutputFunctionToolCall|FunctionToolCallOutput|OutputReasoning|OutputMcpListTools|OutputMcpApprovalRequest|OutputMcpCall|OutputImageGenerationToolCall|OutputCodeInterpreterToolCall|OutputLocalShellCall|OutputCustomToolCall>  $data
     * @param  'list'  $object
     */
    private function __construct(public readonly string $object, public readonly array $data, public readonly string $firstId, public readonly string $lastId, public readonly bool $hasMore, private readonly MetaInformation $meta)
    {
    }
    /**
     * @param  ListInputItemsType  $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        $data = ItemObjects::parse($attributes['data']);
        return new self(object: $attributes['object'], data: $data, firstId: $attributes['first_id'], lastId: $attributes['last_id'], hasMore: $attributes['has_more'], meta: $meta);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['object' => $this->object, 'data' => array_map(fn(InputMessage|OutputMessage|OutputFileSearchToolCall|OutputFunctionToolCall|FunctionToolCallOutput|OutputWebSearchToolCall|OutputComputerToolCall|ComputerToolCallOutput|LocalShellCallOutput|McpApprovalResponse|CustomToolCallOutput|OutputReasoning|OutputMcpListTools|OutputMcpApprovalRequest|OutputMcpCall|OutputImageGenerationToolCall|OutputCodeInterpreterToolCall|OutputLocalShellCall|OutputCustomToolCall $item): array => $item->toArray(), $this->data), 'first_id' => $this->firstId, 'last_id' => $this->lastId, 'has_more' => $this->hasMore];
    }
}
