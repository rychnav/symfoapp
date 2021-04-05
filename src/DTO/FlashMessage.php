<?php

namespace App\DTO;

/**
 * Represents Flash message
 */
class FlashMessage
{
    public $type;
    public $text;
    public $action;
    public $params;
    public $domain;

    public function __construct(
        string $type,
        string $text,
        FlashAction $action = null,
        array $params = [],
        string $domain = 'messages'
    ) {
        $this->type = $type;
        $this->text = $text;
        $this->action = $action;
        $this->params = $params;
        $this->domain = $domain;
    }
}
