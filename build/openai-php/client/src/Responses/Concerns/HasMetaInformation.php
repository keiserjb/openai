<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Responses\Concerns;

use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
trait HasMetaInformation
{
    public function meta(): MetaInformation
    {
        return $this->meta;
    }
}
