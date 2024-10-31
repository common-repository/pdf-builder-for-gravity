<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 10/6/2017
 * Time: 6:52 AM
 */
namespace rednaoformpdfbuilder\htmlgenerator\sectionGenerators\fields;

use rednaoformpdfbuilder\DTO\FieldSummaryOptions;
use rednaoformpdfbuilder\htmlgenerator\tableCreator\HTMLTableCreator;
use rednaoformpdfbuilder\pr\htmlgenerator\sectionGenerator\fields\PDFCustomField;
use rednaoformpdfbuilder\Utils\Sanitizer;

class PDFSummary extends PDFFieldBase
{

    /** @var FieldSummaryOptions */
    public $options;

    protected function InternalGetHTML()
    {
       $creator='<div class="SummaryTable">';



       $sortedFields=array();
       if($this->entryRetriever!=null)
       {
           $originalFields=$this->entryRetriever->OriginalFieldSettings;
           foreach ($originalFields as $formField)
           {
               foreach($this->options->Fields as $configuredFields)
                   if($configuredFields->Id==$formField->Id)
                       $sortedFields[]=$configuredFields;
           }


       }else
           $sortedFields=$this->options->Fields;

        $fieldsToSort=$this->options->Fields;

        $sortBy=$this->GetPropertyValue('SortBy');

        switch ($sortBy)
        {
            case 'form':
                $sortedFields=[];
                $dictionary=$this->entryRetriever->FieldDictionary;
                foreach($dictionary as $currentField)
                {
                    foreach($fieldsToSort as $field)
                    {
                        if($field->Id==$currentField->Field->Id)
                        {
                            $sortedFields[]=$field;

                        }
                    }
                }
                break;
            case 'alpha':
                usort($fieldsToSort,function($a,$b){
                    return strcmp($a->Label,$b->Label);
                });
                $sortedFields=$fieldsToSort;
                break;
            default:
                $sortedFields=$fieldsToSort;
        }


        for($i=0;$i<count($sortedFields);$i++)
        {
            $field=null;
            if($this->entryRetriever!=null)
             $field=$this->entryRetriever->GetFieldById($sortedFields[$i]->Id);
            if($field!=null&&$field->Type=='Repeater')
            {
                $repeaterItems=[];
                $suffix='';
                $index=1;
                do
                {
                    $found=false;
                    $rowItems=[];
                    foreach ($field->Columns as $currentColum)
                    {
                        foreach ($currentColum->fields as $currentField)
                        {
                            $repeaterField = $this->entryRetriever->GetFieldById($currentField);
                            if ($repeaterField == null)
                                continue;

                            if(isset($this->entryRetriever->FieldDictionary[$currentField.$suffix]))
                            {
                                $found=true;
                            }

                            $newField = (object)(array)$sortedFields[$i];
                            $newField->Id = $currentField.$suffix;
                            $newField->Label = $repeaterField->Label;
                            $newField->IgnoreRepeaterFields=true;

                            $rowItems[] = $newField;;

                        }

                    }

                    if($found)
                    {
                        $repeaterItems=array_merge($repeaterItems,$rowItems);
                    }

                    $index++;
                    $suffix='_'.$index;
                }while($found);

                array_splice($sortedFields, $i, 1, $repeaterItems);


            }

        }

        if($this->GetPropertyValue('Format')=='compact')
        {
            $labelWidth=100;
            $valueWidth=100;
            $count=0;
            foreach($sortedFields as $field)
            {
                $labelWidth=Sanitizer::SanitizeNumber($this->GetPropertyValue('LabelWidth'),0);

                if($labelWidth==0)
                    $labelWidth=30;


                $valueWidth=100-$labelWidth-4;

                $value='';
                if($this->entryRetriever==null)
                    $value='<p>Value not available in preview</p>';
                else
                {
                    $style='standard';

                    if(isset($field->FieldSettings)&&isset($field->FieldSettings->Style)&&$field->FieldSettings->Style!='')
                    {
                        $style=$field->FieldSettings->Style;
                    }
                    $value = $this->GetRowValue($field->Id,$style,Sanitizer::GetBooleanValueFromPath($field,'IgnoreRepeaterFields',false));
                    if ($value==null||$value==''||(method_exists($value,'IsEmpty')&&$value->IsEmpty()))
                        if($this->GetPropertyValue("IncludeEmptyFields",false)==false)
                            continue;
                        else
                            $value='<div style="height: 30px"></div>';
                }

                $labelToUse=$field->Label;
                if(isset($field->FieldSettings->Label)&&trim($field->FieldSettings->Label)!='')
                    $labelToUse=$field->FieldSettings->Label;


                $creator.='<div style="vertical-align:top;width:100%;padding:2px" class="'.($count % 2 == 0?'CompactRow Odd':'CompactRow Even').'">';
                $creator.='<div class="CompactFieldLabel" style="vertical-align: top;width:'.$labelWidth.'% !important;display: inline-block"><p>'.esc_html($labelToUse).'</p></div>';
                $creator.='<div class="CompactFieldValue" style="vertical-align: top;width:'.$valueWidth.'% !important;display: inline-block">'.$value.'</div>';
                $creator.='</div>';
                $count++;
            }

        }else

       foreach($sortedFields as $field)
       {
           $value='';
           if($this->entryRetriever==null)
                $value='Value not available in preview';
           else
           {
               $style='standard';

               if(isset($field->FieldSettings)&&isset($field->FieldSettings->Style)&&$field->FieldSettings->Style!='')
               {
                   $style=$field->FieldSettings->Style;
               }
               $value =$this->GetRowValue($field->Id,$style,Sanitizer::GetBooleanValueFromPath($field,'IgnoreRepeaterFields',false));
               if ($value==null||$value==''||(method_exists($value,'IsEmpty')&&$value->IsEmpty()))
                   if($this->GetPropertyValue("IncludeEmptyFields",false)==false)
                    continue;
                   else
                       $value='<div style="height: 30px"></div>';
           }

           $labelToUse=$field->Label;

           if(isset($field->FieldSettings->Label)&&trim($field->FieldSettings->Label)!='')
               $labelToUse=$field->FieldSettings->Label;

           $creator.='<div class="FieldLabel">'.esc_html($labelToUse).'</div>';
           $creator.='<div class="FieldValue">'.$value.'</div>';
       }
       $creator.='</div>';
       return $creator;


    }


    public function GetRowValue($fieldId,$style,$ignoreRepeaterFields=false)
    {
        if(strpos($fieldId,'c_')===0)
        {
            return apply_filters('rnpdfbuilder_get_custom_field',str_replace('c_','', $fieldId),$this->entryRetriever);
        }
        return $this->entryRetriever->GetHtmlByFieldId($fieldId,$style,null,$ignoreRepeaterFields);
    }
}