<?php

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields;

use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;

class GravitySurveyRank extends FieldSettingsBase
{
    public function GetType()
    {
        return 'survey_rank';
    }
}