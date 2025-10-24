<?php

namespace BackdropOpenAI\GuzzleHttp;

use BackdropOpenAI\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
