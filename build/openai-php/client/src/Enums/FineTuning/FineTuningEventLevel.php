<?php

namespace BackdropOpenAI\OpenAI\Enums\FineTuning;

enum FineTuningEventLevel : string
{
    case Info = 'info';
    case Warning = 'warn';
    case Error = 'error';
}
