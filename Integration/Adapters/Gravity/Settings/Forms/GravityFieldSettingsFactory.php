<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 4:26 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms;


use Exception;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityAddressFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityChainedSelect;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityDateFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityMultipleTextItemsSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityNameFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravitySurveyRank;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityTimeFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\WPForm\Settings\Forms\Fields\WPFormAddressFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\WPForm\Settings\Forms\Fields\WPFormDateFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\WPForm\Settings\Forms\Fields\NinjaFormsNameFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\MultipleOptionsFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\RatingFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\TextFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FieldSettingsFactoryBase;

class GravityFieldSettingsFactory extends FieldSettingsFactoryBase
{
    /**
     * @param $options
     * @return FieldSettingsBase
     * @throws Exception
     */
    public function GetFieldByOptions($options)
    {
        $field= parent::GetFieldByOptions($options);
        if($field!=null)
            return $field;

        switch ($options->Type)
        {

            case 'Time':
                $field=new GravityTimeFieldSettings();
                break;
            case 'Address':
                $field=new GravityAddressFieldSettings();
                break;
            case 'Date':
                $field=new GravityDateFieldSettings();
                break;
            case 'Name':
                $field=new GravityNameFieldSettings();
                break;
            case 'MultipleTextItems':
                $field=new GravityMultipleTextItemsSettings();
                break;
            case 'ChainedSelect':
                $field=new GravityChainedSelect();
                break;
            case 'survey_rank':
                $field=new GravitySurveyRank();
                break;

        }

        switch ($options->SubType)
        {
            case 'survey_radio':
            case 'survey_checkbox':
            case 'survey_select':
            case 'survey_likert':
                return new MultipleOptionsFieldSettings();
            case 'survey_rating':
                return new RatingFieldSettings();
            case 'survey_text':
            case 'survey_textarea':
                return new TextFieldSettings();
        }

        if($field==null)
            throw new Exception('Invalid field settings type '.$options->Type);

        $field->InitializeFromOptions($options);
        return $field;
    }


}