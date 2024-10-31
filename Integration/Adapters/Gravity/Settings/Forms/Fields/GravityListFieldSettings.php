<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/21/2019
 * Time: 6:20 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields;

use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;

class GravityListFieldSettings extends FieldSettingsBase
{
    public function GetType()
    {
        return 'List';
    }



}