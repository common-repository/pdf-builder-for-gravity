<?php

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\HTMLFormatters;

use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\PHPFormatterBase;

class GravityListFormatter extends PHPFormatterBase
{
    public $html;
    public $text;

    public function __construct($htmlTable,$text,$field=null)
    {
        parent::__construct($field);
        $this->html=$htmlTable;
        $this->text=$text;
    }

    public function __toString()
    {
        return $this->html;
    }

    public function IsEmpty()
    {
        return $this->html=='';
    }

    public function ToText()
    {
        return $this->text;
    }
}