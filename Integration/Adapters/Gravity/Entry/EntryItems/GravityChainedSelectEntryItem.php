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
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\LinkFormatter;

class GravityChainedSelectEntryItem extends EntryItemBase
{
    public $Items=[];
    public $Value=[];


    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>implode(' - ',$this->Items),
            'Items'=>$this->Items
        );
    }


    public function InitializeWithValues($field,$items)
    {
        $this->Initialize($field);
        $this->Items=$items;
        $this->Value=implode(' - ',$this->Items);
        return $this;
    }

    public function InitializeWithOptions($field,$options){
        $this->Field=$field;
        if(isset($options->Value))
            $this->Value=$options->Value;
        if(isset($options->Items))
            $this->Items=$options->Items;
    }

    public function GetHtml($style='standard',$field=null)
    {
        return new BasicPHPFormatter($this->Value);
    }
}