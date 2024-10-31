<?php

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields;

use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;

class GravityChainedSelect extends FieldSettingsBase
{
    public function GetType()
    {
        return 'ChainedSelect';
    }
}