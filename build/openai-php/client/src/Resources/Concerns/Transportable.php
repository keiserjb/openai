<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Resources\Concerns;

use BackdropOpenAI\OpenAI\Contracts\TransporterContract;
trait Transportable
{
    public function __construct(private readonly TransporterContract $transporter)
    {
        // ..
    }
}
