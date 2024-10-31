<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/19/2019
 * Time: 11:39 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\FormProcessor;



use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityAddressFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityChainedSelect;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityDateFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityListFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityMultipleTextItemsSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityNameFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravitySurveyRank;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\Fields\GravityTimeFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\WPForm\Settings\Forms\Fields\WPFormAddressFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\WPForm\Settings\Forms\Fields\WPFormDateFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\WPForm\Settings\Forms\Fields\NinjaFormsNameFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\FormProcessor\FormProcessorBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\EmailNotification;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FileUploadFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\MultipleOptionsFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\NumberFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\RatingFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\TextFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FormSettings;
use rednaoformpdfbuilder\Utils\Sanitizer;
use Svg\Tag\Text;

class GravityFormProcessor extends FormProcessorBase
{
    public function __construct($loader)
    {
        parent::__construct($loader);
        \add_action('gform_after_save_form',array($this,'SavingForm'),10,3);
    }

    public function SavingForm($formMeta,$arg2,$arg3){
        $formSettings=new FormSettings();
        $formSettings->OriginalId=$formMeta['id'];
        $formSettings->Name=$formMeta['title'];

        foreach($formMeta['notifications'] as $currentNotification)
        {

            $formSettings->EmailNotifications[]=new EmailNotification($currentNotification['id'],$currentNotification['name']);

        }

        $formSettings->Fields=$this->SerializeFields($formMeta['fields']);
        $this->SaveOrUpdateForm($formSettings);
    }

    public function SerializeForm($form){
        $formId=$form['form_id'];
        $meta=\json_decode($form['display_meta']);
        $notifications=\json_decode($form['notifications'],true);
        $fields=$meta->fields;

        $formSettings=new FormSettings();

        foreach($notifications as $currentNotification)
        {

            $formSettings->EmailNotifications[]=new EmailNotification($currentNotification['id'],$currentNotification['name']);

        }

        $formSettings->OriginalId=$formId;
        $formSettings->Name=$meta->title;
        $formSettings->Fields=$this->SerializeFields($fields);


        return $formSettings;
    }

    public function SerializeFields($fieldList)
    {
        /** @var FieldSettingsBase[] $fieldSettings */
        $fieldSettings=array();
        foreach($fieldList as $field)
        {
            $type=$field->type;
            if($type=='survey')
                $type=$type.'_'.$field->inputType;
            IF($type=='quiz')
                $type=$type.'_'.$field->inputType;
            switch($type)
            {
                case 'text':
                case 'textarea':
                case 'uid':
                case 'phone':
                case 'website':
                case 'email':
                case 'post_title':
                case 'post_content':
                case 'post_excerpt':
                case 'survey_text':
                case 'survey_textarea':
                    $fieldSettings[]=(new TextFieldSettings())->Initialize($field->id,$field->label,$type);
                    break;
                case 'select':
                case 'multiselect':
                case 'checkbox':
                case 'option':
                case 'radio':
                case 'survey_radio':
                case 'survey_checkbox':
                case 'survey_select':
                case 'quiz_select':
                case 'quiz_checkbox':
                case 'quiz_radio':
                    $settings=(new MultipleOptionsFieldSettings())->Initialize($field->id,$field->label,$type);
                    foreach($field->choices as $choice)
                    {
                        if(!\is_object($choice))
                            $choice=(object)$choice;
                        $settings->AddOption($choice->text,$choice->value,$choice->price);
                    }
                $fieldSettings[]=$settings;
                    break;
                case 'time':
                    $fieldSettings[]=(new GravityTimeFieldSettings())->Initialize($field->id,$field->label,$field->type);
                    break;
                case 'number':
                case 'product':
                case 'quantity':
                case 'shipping':
                case 'total':
                    $numberField=(new NumberFieldSettings())->Initialize($field->id,$field->label,$field->type);

                    if(Sanitizer::GetStringValueFromPath($field,['numberFormat'])=='currency')
                        $numberField->SetFormatAsCurrency();
                    $fieldSettings[]=$numberField;
                    break;
                case 'name':
                    $fieldSettings[]=(new GravityNameFieldSettings())->Initialize($field->id,$field->label,$field->type);
                    break;
                case 'address':
                    $fieldSettings[]=(new GravityAddressFieldSettings())->Initialize($field->id,$field->label,$field->type);
                    break;
                case 'date':
                    $fieldSettings[]=(new GravityDateFieldSettings())->Initialize($field->id,$field->label,$field->type)
                        ->SetDateFormat($field->dateFormat);
                    break;
                case 'list':
                case 'post_tags':
                case "post_category":
                    $fieldSettings[]=(new GravityMultipleTextItemsSettings())->Initialize($field->id,$field->label,$field->type);
                    break;
                case "fileupload":
                case 'post_image':
                case 'post_custom_field':
                case 'signature':
                    $fieldSettings[]=(new FileUploadFieldSettings())->Initialize($field->id,$field->label,$field->type);
                    break;
                case 'chainedselect':
                    $fieldSettings[]=(new GravityChainedSelect())->Initialize($field->id,$field->label,$field->type);
                    break;
                case 'survey_rank':
                    $fieldSettings[]=(new GravitySurveyRank())->Initialize($field->id,$field->label,$type);
                    break;
                case 'survey_likert':
                    $settings=(new MultipleOptionsFieldSettings())->Initialize($field->id,$field->label,$type);
                    foreach($field->choices as $choice)
                    {
                        if(is_array($choice))
                            $settings->AddOption($choice['text'],$choice['value']);
                        else
                            $settings->AddOption($choice->text,$choice->value);
                    }
                    $fieldSettings[]=$settings;
                    break;
                case 'survey_rating':
                    $fieldSettings[]=(new RatingFieldSettings())->Initialize($field->id,$field->label,$type,count($field->choices));
                    break;
                default:
                    $a=1;
            }
        }

        return $fieldSettings;
    }

    public function SyncCurrentForms()
    {
        global $wpdb;
        $results=$wpdb->get_results("select form_id, display_meta,notifications from ".$wpdb->prefix."gf_form_meta",'ARRAY_A');
        $formIds=array();
        foreach($results as $form)
        {
            $formIds[]=$form['form_id'];
            $form=$this->SerializeForm($form);
            $this->SaveOrUpdateForm($form);
        }

        $how_many = count($formIds);
        $placeholders = array_fill(0, $how_many, '%d');
        $format = implode(', ', $placeholders);

        $query = "delete from ".$this->Loader->FormConfigTable." where original_id not in($format)";
        $wpdb->query($wpdb->prepare($query,$formIds));

    }

    public function GetFormList()
    {
        global $wpdb;

        $rows= $wpdb->get_results("select id Id, name Name, fields Fields,original_id OriginalId,notifications Notifications from ".$this->Loader->FormConfigTable );
        return $rows;
    }
}