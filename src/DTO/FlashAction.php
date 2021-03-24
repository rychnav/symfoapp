<?php

namespace App\DTO;

/**
 * Represents Action button in Flash messages.
 */
class FlashAction
{
    public $name;
    public $url;

    public function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;
    }
}
