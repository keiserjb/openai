<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Responses;

use BackdropOpenAI\OpenAI\Contracts\ResponseContract;
use BackdropOpenAI\OpenAI\Exceptions\UnknownEventException;
use BackdropOpenAI\OpenAI\Responses\Concerns\ArrayAccessible;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\CodeInterpreterCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\CodeInterpreterCodeDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\CodeInterpreterCodeDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ContentPart;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\Error;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\FileSearchCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\FunctionCallArgumentsDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\FunctionCallArgumentsDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ImageGenerationPart;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ImageGenerationPartialImage;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\McpCall;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\McpCallArgumentsDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\McpCallArgumentsDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\McpListTools;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\McpListToolsInProgress;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\OutputItem;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\OutputTextAnnotationAdded;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\OutputTextDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\OutputTextDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ReasoningSummaryPart;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ReasoningSummaryTextDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ReasoningSummaryTextDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ReasoningTextDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\ReasoningTextDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\RefusalDelta;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\RefusalDone;
use BackdropOpenAI\OpenAI\Responses\Responses\Streaming\WebSearchCall;
use BackdropOpenAI\OpenAI\Testing\Responses\Concerns\FakeableForStreamedResponse;
/**
 * @phpstan-type CreateStreamedResponseType array{event: string, data: array<string, mixed>}
 *
 * @implements ResponseContract<CreateStreamedResponseType>
 */
final class CreateStreamedResponse implements ResponseContract
{
    /**
     * @use ArrayAccessible<CreateStreamedResponseType>
     */
    use ArrayAccessible;
    use FakeableForStreamedResponse;
    private function __construct(public readonly string $event, public readonly CreateResponse|OutputItem|ContentPart|OutputTextDelta|OutputTextAnnotationAdded|OutputTextDone|RefusalDelta|RefusalDone|FunctionCallArgumentsDelta|FunctionCallArgumentsDone|FileSearchCall|WebSearchCall|CodeInterpreterCall|CodeInterpreterCodeDelta|CodeInterpreterCodeDone|ReasoningSummaryPart|ReasoningSummaryTextDelta|ReasoningSummaryTextDone|ReasoningTextDelta|ReasoningTextDone|McpListTools|McpListToolsInProgress|McpCall|McpCallArgumentsDelta|McpCallArgumentsDone|ImageGenerationPart|ImageGenerationPartialImage|Error $response)
    {
    }
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function from(array $attributes): self
    {
        $event = $attributes['type'] ?? throw new UnknownEventException('Missing event type in streamed response');
        $meta = $attributes['__meta'];
        unset($attributes['__meta']);
        $response = match ($event) {
            'response.created', 'response.in_progress', 'response.completed', 'response.failed', 'response.incomplete' => CreateResponse::from($attributes['response'], $meta),
            // @phpstan-ignore-line
            'response.output_item.added', 'response.output_item.done' => OutputItem::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.content_part.added', 'response.content_part.done' => ContentPart::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.output_text.delta' => OutputTextDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.output_text.done' => OutputTextDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.output_text.annotation.added' => OutputTextAnnotationAdded::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.refusal.delta' => RefusalDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.refusal.done' => RefusalDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.function_call_arguments.delta' => FunctionCallArgumentsDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.function_call_arguments.done' => FunctionCallArgumentsDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.file_search_call.in_progress', 'response.file_search_call.searching', 'response.file_search_call.completed' => FileSearchCall::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.web_search_call.in_progress', 'response.web_search_call.searching', 'response.web_search_call.completed' => WebSearchCall::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.code_interpreter_call.in_progress', 'response.code_interpreter_call.running', 'response.code_interpreter_call.interpreting', 'response.code_interpreter_call.completed' => CodeInterpreterCall::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.code_interpreter_call_code.delta' => CodeInterpreterCodeDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.code_interpreter_call_code.done' => CodeInterpreterCodeDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.reasoning_summary_part.added', 'response.reasoning_summary_part.done' => ReasoningSummaryPart::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.reasoning_summary_text.delta' => ReasoningSummaryTextDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.reasoning_summary_text.done' => ReasoningSummaryTextDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.reasoning_text.delta' => ReasoningTextDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.reasoning_text.done' => ReasoningTextDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.mcp_list_tools.in_progress' => McpListToolsInProgress::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.mcp_list_tools.failed', 'response.mcp_list_tools.completed' => McpListTools::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.mcp_call.in_progress', 'response.mcp_call.failed', 'response.mcp_call.completed' => McpCall::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.mcp_call.arguments.delta', 'response.mcp_call_arguments.delta' => McpCallArgumentsDelta::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.mcp_call.arguments.done', 'response.mcp_call_arguments.done' => McpCallArgumentsDone::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.image_generation_call.completed', 'response.image_generation_call.generating', 'response.image_generation_call.in_progress' => ImageGenerationPart::from($attributes, $meta),
            // @phpstan-ignore-line
            'response.image_generation_call.partial_image' => ImageGenerationPartialImage::from($attributes, $meta),
            // @phpstan-ignore-line
            'error' => Error::from($attributes, $meta),
            // @phpstan-ignore-line
            default => throw new UnknownEventException('Unknown Responses streaming event: ' . $event),
        };
        return new self(
            event: $event,
            // @phpstan-ignore-line
            response: $response
        );
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return ['event' => $this->event, 'data' => $this->response->toArray()];
    }
}
