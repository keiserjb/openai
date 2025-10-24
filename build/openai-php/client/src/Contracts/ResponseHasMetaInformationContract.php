<?php

declare (strict_types=1);
namespace BackdropOpenAI\OpenAI\Contracts;

use BackdropOpenAI\OpenAI\Responses\Meta\MetaInformation;
interface ResponseHasMetaInformationContract
{
    public function meta(): MetaInformation;
}
