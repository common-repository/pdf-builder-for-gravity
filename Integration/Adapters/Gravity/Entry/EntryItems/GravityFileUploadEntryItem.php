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

class GravityFileUploadEntryItem extends EntryItemBase
{
    public $URL;
    public $FileName;
    public $Ext;
    public $OriginalName;


    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>$this->URL
        );
    }


    public function InitializeWithValues($field,$url)
    {
        $this->Initialize($field);
        $this->URL=$url;
        return $this;
    }

    public function InitializeWithOptions($field,$options){
        $this->Field=$field;
        if(isset($options->Value))
            $this->URL=$options->Value;

    }

    public function GetHtml($style='standard',$field=null)
    {
        return new LinkFormatter($this->URL,$this->URL);
    }
}