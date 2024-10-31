<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 5:15 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems;


use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\HTMLFormatters\GravityListFormatter;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\EntryItemBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\BasicPHPFormatter;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\MultipleLineFormatter;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\RawPHPFormatter;

class GravityRankEntryItem extends EntryItemBase
{
    public $Value=[];

    protected function InternalGetObjectToSave()
    {

        return (object)array(
            'Values'=>$this->Value
        );
    }


    public function InitializeWithValues()
    {

    }


    public function InitializeWithOptions($field,$options)
    {
        $this->Field=$field;
        if(isset($options->Headers))
            $this->Headers=$options->Headers;
        if(isset($options->Rows))
            $this->Rows=$options->Rows;
    }

    public function SetValue($value)
    {
       $this->Value=$value;
       return $this;
    }

    public function GetHtml($style='standard',$field=null)
    {
        $html='';
        $multipleLineFormatter=new MultipleLineFormatter();
        $index=1;
        foreach($this->Value as $currentValue)
        {
            $multipleLineFormatter->AddLine($index.'. '.$currentValue['label']);
            $index++;
        }
        return $multipleLineFormatter;
    }
}