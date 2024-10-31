<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/22/2019
 * Time: 5:03 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry;


use DateTime;
use DateTimeZone;
use Exception;
use rednaoformpdfbuilder\htmlgenerator\generators\PDFGenerator;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityAddressEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityChainedSelectEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityDateTimeEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityFileUploadEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityListEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityNameEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems\GravityRankEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\Retriever\GravityEntryRetriever;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\FormProcessor\GravityFormProcessor;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\CheckBoxEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\DropDownEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\EntryItemBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\LikertEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\MultipleSelectionEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\RatingEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\SimpleTextEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\SimpleTextWithAmountEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryProcessorBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FormSettings;
use stdClass;

class GravityEntryProcessor extends EntryProcessorBase
{
    public function __construct($loader)
    {
        parent::__construct($loader);
        \add_filter('gform_entry_post_save',array($this,'SaveEntry'),10,2);
        \add_filter('gform_pre_send_email',array($this,'AddAttachment'),10,4);
        \add_shortcode('bpdfbuilder_download_link',array($this,'AddPDFLink'));
        add_action( 'gform_entry_detail_sidebar_middle', array($this,'AddPDFLinkEntry'), 10, 2 );

    }


    public function AddPDFLinkEntry($form,$entry)
    {
        if(!$this->Loader->IsPR())
            return;


        global $wpdb;
        $result=$wpdb->get_results($wpdb->prepare(
            "select template.id Id,template.name Name
                    from ".$this->Loader->FormConfigTable." form
                    join ".$this->Loader->TEMPLATES_TABLE." template
                    on form.id=template.form_id
                    where original_id=%s"
            ,$entry['form_id']));

        if(!current_user_can('administrator')||!$this->Loader->IsPR())
            return;
        $links='';
        foreach($result as $pdfTemplate)
        {
            $data=array(
                'entryid'=>$entry['id'],
                'templateid'=>$pdfTemplate->Id,
                'use_original_entry'=>true,
                'nonce'=>\wp_create_nonce($this->Loader->Prefix.'_'.$entry['id'].'_'.$pdfTemplate->Id.'_1')
            );

            $links.= '
                <p class="wpforms-entry-star">
                    <a href="'.esc_attr(admin_url( 'admin-ajax.php' )) .'?action='.esc_attr($this->Loader->Prefix).'_generate_pdf_from_original&entryid='.esc_attr($entry['id']).
                '&templateid='.esc_attr($pdfTemplate->Id).'&nonce='.esc_attr(wp_create_nonce('generate_'.$pdfTemplate->Id.'_'.$entry['id'])).'">
                        <span class="dashicons dashicons-pdf"></span>View '.esc_html($pdfTemplate->Name).'
                    </a>
                </p>
            ';
        }

        if($links!='')
            echo "<div class='stuffbox'><h3><span class='hndle'>PDF Builder</span></h3><div class='inside'>$links</div></div>";

    }

    public function AddPDFLink($attrs,$content){
        $message='Click here to download';
        if(isset($attrs['message']))
            $message=$attrs['message'];

        if(!isset($_SESSION['Gravity_Generated_PDF']))
            return;

        $pdfData=$_SESSION['Gravity_Generated_PDF'];

        if(!isset($pdfData['TemplateId'])||!isset($pdfData['EntryId']))
            return;



        $nonce=\wp_create_nonce('view_'.$pdfData['EntryId'].'_'.$pdfData['TemplateId']);
        $url=admin_url('admin-ajax.php').'?action='.$this->Loader->Prefix.'_view_pdf'.'&nonce='.\urlencode($nonce).'&templateid='.$pdfData['TemplateId'].'&entryid='.$pdfData['EntryId'];
        return "<a target='_blank' href='$url'>".\esc_html($message)."</a>";

    }





    public function UpdateOriginalEntryId($entryId,$formData)
    {
        if(!isset($formData['fields']))
            return;
        global $RNWPCreatedEntry;
        if(!isset($RNWPCreatedEntry)||!isset($RNWPCreatedEntry['Entry']))
            return;

        global $wpdb;
        $wpdb->update($this->Loader->RECORDS_TABLE,array(
            'original_id'=>$entryId
        ),array('id'=>$RNWPCreatedEntry['EntryId']));

    }

    public function SaveLittleEntry($fields,$entry,$formId,$formData,$entryId=0)
    {
        $this->SaveEntry($fields,$entry,$formId,$formData,0);
    }

    public function SaveEntry($originalEntry,$form){
        $originalId=$originalEntry['id'];
        /** @var GravityFormProcessor $formProcessor */
        $formProcessor=$this->Loader->ProcessorLoader->FormProcessor;
        $entry=$this->SerializeEntry($originalEntry,$this->Loader->GetForm($form['id']));
        $entryId='';
        if($entry!=null&&!\rednaoformpdfbuilder\Utils\Sanitizer::SanitizeBoolean(get_option($this->Loader->Prefix.'_skip_save',false)))
        {
            $entryId=$this->SaveEntryToDB($form['id'],$entry,$originalId,$originalEntry);
        }


        return $originalEntry;
    }

    public function AddAttachment($emailData,$arg1,$arg2,$arg3)
    {


        $entryRetriever=new GravityEntryRetriever($this->Loader);
        $entryRetriever->InitializeFromOriginalEntryId($arg3['id']);

        global $wpdb;
        $result=$wpdb->get_results($wpdb->prepare(
            "select template.id Id,template.pages Pages, template.document_settings DocumentSettings,styles Styles,form_id FormId
                    from ".$this->Loader->FormConfigTable." form
                    join ".$this->Loader->TEMPLATES_TABLE." template
                    on form.id=template.form_id
                    where form.id=%s"
        ,$arg3['form_id']));
        $files=[];
        global $RNWPCreatedEntry;
        if($RNWPCreatedEntry==null)
            $RNWPCreatedEntry=[];
        if(!isset($RNWPCreatedEntry['CreatedDocuments'])){
            $RNWPCreatedEntry['CreatedDocuments']=[];
        }
        foreach($result as $templateSettings)
        {
            $templateSettings->Pages=\json_decode($templateSettings->Pages);
            $templateSettings->DocumentSettings=\json_decode($templateSettings->DocumentSettings);

            if(isset($templateSettings->DocumentSettings->Notifications)&&count($templateSettings->DocumentSettings->Notifications)>0)
            {
                $found=false;
                foreach($templateSettings->DocumentSettings->Notifications as $attachToNotificationId)
                {
                    if($attachToNotificationId==$arg2['id'])
                        $found=true;
                }

                if(!$found)
                    continue;
            }


            $generator=(new PDFGenerator($this->Loader,$templateSettings,$entryRetriever));
            if(!$generator->ShouldAttach())
            {
                continue;
            }
            $path=$generator->SaveInTempFolder();

            $RNWPCreatedEntry['CreatedDocuments'][]=array(
                'TemplateId'=>$generator->options->Id,
                'Name'=>$generator->options->DocumentSettings->FileName
            );
            $emailData['attachments'][]=$path;

            $_SESSION['Gravity_Generated_PDF']=array(
                'TemplateId'=>$generator->options->Id,
                'EntryId'=>$RNWPCreatedEntry['EntryId']
            );

        }

        return $emailData;

    }

    public function SerializeEntry($entry, $form)
    {

        foreach($form->Fields as $currentField)
        {
            $fieldId=$currentField->Id;
            switch($currentField->SubType)
            {
                case 'text':
                case 'uid':
                case 'textarea':
                case 'select':
                case 'number':
                case 'phone':
                case 'time':
                case 'website':
                case 'email':
                case 'post_title':
                case 'post_content':
                case 'quantity':
                case 'shipping':
                case 'total':
                case 'survey_text':
                case 'survey_textarea':
                    if(!isset($entry[$fieldId]))
                        break;
                    $entryItems[]=(new SimpleTextEntryItem())->Initialize($currentField)->SetValue($entry[$fieldId]);

                    break;
                case 'multiselect':
                    if(!isset($entry[$fieldId]))
                        break;
                    $value=\json_decode($entry[$fieldId]);
                    if($value==null)
                        break;
                    $entryItems[]=(new DropDownEntryItem())->Initialize($currentField)->SetValue($value);
                    break;
                case 'radio':
                     $options=array();
                    if(isset($entry[$fieldId]))
                    {
                        $value=$entry[$fieldId];
                        if(trim($value)!='')
                            $options[]=$value;
                    }else
                        break;
                    $entryItems[]=(new CheckBoxEntryItem())->Initialize($currentField)->SetValue($options);
                    break;
                case 'checkbox':
                    $count=1;
                    $options=array();
                    while(true)
                    {
                        if(isset($entry[$fieldId.'.'.$count]))
                        {
                            $value=$entry[$fieldId.'.'.$count];
                            if(trim($value)!='')
                                $options[]=$value;
                        }else
                        {
                            if($count % 10 !=0)
                                break;
                        }
                        $count++;
                    }
                    $entryItems[]=(new CheckBoxEntryItem())->Initialize($currentField)->SetValue($options);
                    break;
                case 'name':
                    $firstName='';
                    $lastName='';
                    $middleName='';
                    $prefix='';
                    $suffix='';

                    if(isset($entry[$fieldId.'.2']))
                        $prefix=$entry[$fieldId.'.2'];
                    if(isset($entry[$fieldId.'.3']))
                        $firstName=$entry[$fieldId.'.3'];
                    if(isset($entry[$fieldId.'.4']))
                        $middleName=$entry[$fieldId.'.4'];
                    if(isset($entry[$fieldId.'.6']))
                        $lastName=$entry[$fieldId.'.6'];
                    if(isset($entry[$fieldId.'.8']))
                        $suffix=$entry[$fieldId.'.8'];

                    $entryItems[]=(new GravityNameEntryItem())->InitializeWithValues($currentField,$firstName,$lastName,$prefix,$middleName,$suffix);
                    break;
                case 'date':
                    if(!isset($entry[$fieldId]))
                        break;

                    $value=\GFCommon::date_display( $entry[$fieldId], $currentField->DateFormat);
                    $entryItems[]=(new GravityDateTimeEntryItem())->InitializeWithValues($currentField,$value,\strtotime($entry[$fieldId]));

                    break;
                case 'address':
                    $streetAddress1='';
                    $streetAddress2='';
                    $city='';
                    $state='';
                    $zip='';
                    $country='';

                    if(isset($entry[$fieldId.'.1']))
                        $streetAddress1=$entry[$fieldId.'.1'];
                    if(isset($entry[$fieldId.'.2']))
                        $streetAddress2=$entry[$fieldId.'.2'];
                    if(isset($entry[$fieldId.'.3']))
                        $city=$entry[$fieldId.'.3'];
                    if(isset($entry[$fieldId.'.4']))
                        $state=$entry[$fieldId.'.4'];
                    if(isset($entry[$fieldId.'.5']))
                        $zip=$entry[$fieldId.'.5'];
                    if(isset($entry[$fieldId.'.6']))
                        $country=$entry[$fieldId.'.6'];

                    $entryItems[]=(new GravityAddressEntryItem())->InitializeWithValues($currentField,$streetAddress1,$streetAddress2,
                    $city,$state,$zip,$country);
                    break;

                case 'fileupload':
                case 'post_image':
                case 'signature':
                    if(!isset($entry[$fieldId]))
                        break;
                    $url=$entry[$fieldId];
                    if($currentField->SubType=='signature')
                    {
                        $url= gf_signature()->get_signatures_folder().$url;
                    }

                    if($currentField->SubType=='post_image')
                    {
                        if(strpos($url,'|')!==false)
                            $url=substr($url,0,strpos($url,'|'));
                    }

                    $entryItems[]=(new GravityFileUploadEntryItem())->InitializeWithValues($currentField,$url);
                    break;

                case 'list':
                    if(!isset($entry[$fieldId]))
                        break;

                    $value=\unserialize($entry[$fieldId]);
                    if($value!==false&&is_array($value)&&count($value)>0)
                        $entryItems[]=(new GravityListEntryItem())->Initialize($currentField)->SetValue($value);
                    break;
                case 'product':
                    if(!isset($entry[$fieldId.'.1']))
                        break;
                    $entryItems[]=(new SimpleTextWithAmountEntryItem())->Initialize($currentField)->SetValue($entry[$fieldId.'.1'],
                        \GFCommon::to_number($entry[$fieldId.'.2']),isset($entry[$fieldId.'.3'])?$entry[$fieldId.'.3']:null);
                    break;
                case 'option':
                    $items=array();
                    if(isset($entry[$fieldId]))
                        $items[]=$entry[$fieldId];
                    else
                    {
                        $count=1;
                        while (true)
                        {
                            if(isset($entry[$fieldId.'.'.$count]))
                            {
                                $items[]=$entry[$fieldId.'.'.$count];
                            }else{
                                break;
                            }
                            $count++;

                        }
                    }

                    $item=new DropDownEntryItem();
                    $item->Initialize($currentField);

                    foreach($items as $submittedItem)
                    {
                        $exploded=\explode('|',$submittedItem);
                        if(count($exploded)!=2)
                            continue;

                        $item->AddItem($exploded[0],$exploded[1]);
                    }

                    $entryItems[]=$item;
                    break;
                case 'chainedselect':
                    $items=[];
                    $i=1;
                    while(true)
                    {
                        if(isset($entry[$fieldId.'.'.$i]))
                            $items[]=$entry[$fieldId.'.'.$i];
                        else
                            break;
                        $i++;
                    }

                    if(count($items)>0)
                        $entryItems[]=(new GravityChainedSelectEntryItem())->InitializeWithValues($currentField,$items);
                    break;
                case 'survey_rank':
                    if(!isset($entry[$fieldId]))
                        break;

                    $form=\GFAPI::get_form($entry['form_id']);
                    $originalField=\GFFormsModel::get_field( $form, $fieldId );
                    if ( ! is_object( $originalField ) || $originalField->get_input_type() != 'rank' ) {
                        break;
                    }
                    $values=explode(',',$entry[$fieldId]);
                    $serializedValue=[];
                    foreach($values as $currentValue)
                    {
                         foreach($originalField->choices as $choice)
                        {
                            if($choice['value']==$currentValue)
                            {
                                $serializedValue[]=['value'=>$choice['value'],'label'=>$choice['text']];
                                break;
                            }
                        }
                    }

                    $entryItems[]=(new GravityRankEntryItem())->Initialize($currentField)->SetValue($serializedValue);
                    break;
                case 'survey_radio':
                case 'survey_checkbox':
                case 'survey_select':
                case 'quiz_select':
                case 'quiz_radio':
                case 'quiz_checkbox':

                    $count=1;
                    $options=array();
                    while(true)
                    {
                        if(isset($entry[$fieldId.'.'.$count]))
                        {
                            $value=$entry[$fieldId.'.'.$count];
                            if(trim($value)!='')
                                $options[]=$value;
                        }else
                        {
                            if($count==1&&isset($entry[$fieldId]))
                                $options[]=$entry[$fieldId];
                            break;
                        }
                        $count++;
                    }

                    $field=

                    $entryItems[]=(new CheckBoxEntryItem())->Initialize($currentField)->SetValue($options);
                    break;
                case 'survey_rating':
                    $form=\GFAPI::get_form($entry['form_id']);
                    $originalField=\GFFormsModel::get_field( $form, $fieldId );

                    if ( ! is_object( $originalField ) || $originalField->get_input_type() != 'rating' ) {
                        break;
                    }
                    $serializedValue=[];
                    $count=0;
                    for($i=0;$i<count($originalField->choices);$i++)
                    {
                        if($originalField->choices[$i]['value']==$entry[$fieldId])
                        {
                            $count=$i;
                        }
                    }

                    if($count>0)
                        $entryItems[]=(new RatingEntryItem())->Initialize($currentField)->SetValue($count);
                    break;
                case 'survey_likert':
                    $form=\GFAPI::get_form($entry['form_id']);
                    $originalField=\GFFormsModel::get_field( $form, $fieldId );

                    $count=1;
                    $options=array();
                    while(true)
                    {
                        if(isset($entry[$fieldId.'.'.$count]))
                        {
                            $value=$entry[$fieldId.'.'.$count];
                            if(trim($value)!='')
                                $options[]=$value;
                        }else
                        {
                            if($count==1&&isset($entry[$fieldId]))
                                $options[]=$entry[$fieldId];
                            break;
                        }
                        $count++;
                    }

                    $likertEntryItem=(new LikertEntryItem())->Initialize($currentField);
                    if($originalField->gsurveyLikertEnableMultipleRows==false)
                    {
                        foreach ($options as $currentOption)
                        {
                            foreach ($originalField->choices as $row)
                            {
                                if ($row['value'] == $currentOption)
                                {
                                    $likertEntryItem->AddRow('', $row['text']);
                                }
                            }
                        }

                    }else
                    {


                        foreach ($options as $currentOption)
                        {
                            $ids = explode(':', $currentOption);
                            $rowLabel = '';
                            $valueLabel = '';
                            foreach ($originalField->gsurveyLikertRows as $row)
                            {
                                if ($row['value'] == $ids[0])
                                {
                                    $rowLabel = $row['text'];
                                    break;
                                }
                            }

                            foreach ($originalField->choices as $row)
                            {
                                if ($row['value'] == $ids[1])
                                {
                                    $valueLabel = $row['text'];
                                    break;
                                }
                            }
                            if ($rowLabel == '' || $valueLabel == '')
                                continue;

                            $likertEntryItem->AddRow($rowLabel, $valueLabel);

                        }

                    }

                    if(count($likertEntryItem->Rows)>0)
                        $entryItems[] = $likertEntryItem;
                    break;

            }
        }

        return $entryItems;

    }

    public function InflateEntryItem(FieldSettingsBase $field,$entryData)
    {
        $entryItem=null;
        switch($field->SubType)
        {
            case 'text':
            case 'textarea':
            case 'select':
            case 'number':
            case 'phone':
            case 'time':
            case 'website':
            case 'email':
            case 'post_title':
            case 'post_content':
            case 'quantity':
            case 'shipping':
            case 'uid':
            case 'total':
            case 'survey_text':
            case 'survey_textarea':
                $entryItem= new SimpleTextEntryItem();
                break;
            case 'multiselect':
            case 'radio':
                $entryItem= new DropDownEntryItem();
                break;
            case 'checkbox':
            case 'survey_radio':
            case 'survey_checkbox':
            case 'survey_select':
                $entryItem= new CheckBoxEntryItem();
                break;
            case 'name':
                $entryItem= new GravityNameEntryItem();
                break;
            case 'date':
            case 'date-time':
                $entryItem= new GravityDateTimeEntryItem();

                break;
            case 'address':
                $entryItem= new GravityAddressEntryItem();
                break;

            case 'fileupload':
            case 'post_image':
            case 'signature':
                $entryItem= new GravityFileUploadEntryItem();
                break;

            case 'list':
                $entryItem= new GravityListEntryItem();
                break;
            case 'product':
                $entryItem= new SimpleTextWithAmountEntryItem();
                break;
            case 'option':
                $entryItem= new DropDownEntryItem();
            case 'chainedselect':
                $entryItem=new GravityChainedSelectEntryItem();
                break;
            case 'survey_rank':
                $entryItem=new GravityRankEntryItem();
                break;
            case 'survey_rating':
                $entryItem=new RatingEntryItem();
                break;
            case 'survey_likert':
                $entryItem=new LikertEntryItem();
                break;
        }

        if($entryItem==null)
            throw new Exception("Invalid entry sub type ".$field->SubType);
        $entryItem->InitializeWithOptions($field,$entryData);
        return $entryItem;
    }


}