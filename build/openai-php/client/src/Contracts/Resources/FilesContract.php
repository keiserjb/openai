<?php

namespace BackdropOpenAI\OpenAI\Contracts\Resources;

use BackdropOpenAI\OpenAI\Responses\Files\CreateResponse;
use BackdropOpenAI\OpenAI\Responses\Files\DeleteResponse;
use BackdropOpenAI\OpenAI\Responses\Files\ListResponse;
use BackdropOpenAI\OpenAI\Responses\Files\RetrieveResponse;
interface FilesContract
{
    /**
     * Returns a list of files that belong to the user's organization.
     *
     * @see https://platform.openai.com/docs/api-reference/files/list
     *
     * @param  array<string, mixed>  $parameters
     */
    public function list(array $parameters = []): ListResponse;
    /**
     * Returns information about a specific file.
     *
     * @see https://platform.openai.com/docs/api-reference/files/retrieve
     */
    public function retrieve(string $file): RetrieveResponse;
    /**
     * Returns the contents of the specified file.
     *
     * @see https://platform.openai.com/docs/api-reference/files/retrieve-content
     */
    public function download(string $file): string;
    /**
     * Upload a file that contains document(s) to be used across various endpoints/features.
     *
     * @see https://platform.openai.com/docs/api-reference/files/upload
     *
     * @param  array<string, mixed>  $parameters
     */
    public function upload(array $parameters): CreateResponse;
    /**
     * Delete a file.
     *
     * @see https://platform.openai.com/docs/api-reference/files/delete
     */
    public function delete(string $file): DeleteResponse;
}
