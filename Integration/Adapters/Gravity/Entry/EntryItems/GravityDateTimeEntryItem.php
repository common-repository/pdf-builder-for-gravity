<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 5:15 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\EntryItems;


use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\EntryItemBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\BasicPHPFormatter;

class GravityDateTimeEntryItem extends EntryItemBase
{
    public $Value;
    public $Unix;
    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>$this->Value,
            'Unix'=>$this->Unix
        );
    }


    public function InitializeWithValues($field,$date,$unix)
    {
        $this->Initialize($field);
        $this->Value=$date;
        $this->Unix=$unix;
        return $this;
    }

    public function InitializeWithOptions($field,$options)
    {
        $this->Field=$field;
        if(isset($options->Value))
            $this->Value=$options->Value;
        if(isset($options->Unix))
            $this->Unix=$options->Unix;
    }

    public function GetHtml($style='standard',$field=null)
    {
        return new BasicPHPFormatter($this->Value);
    }
}