<?php

namespace BackdropOpenAI\OpenAI\Testing\Responses\Fixtures\FineTunes;

final class ListResponseFixture
{
    public const ATTRIBUTES = ['object' => 'list', 'data' => [RetrieveResponseFixture::ATTRIBUTES]];
}
