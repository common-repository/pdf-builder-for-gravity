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

class GravityNameEntryItem extends EntryItemBase
{
    public $Value;
    public $FirstName;
    public $LastName;
    public $Prefix;
    public $MiddleName;
    public $Suffix;
    protected function InternalGetObjectToSave()
    {


        return (object)array(
            'Value'=>$this->Value,
            'Prefix'=>$this->Prefix,
            'FirstName'=>$this->FirstName,
            'MiddleName'=>$this->MiddleName,
            'LastName'=>$this->LastName,
            'Suffix'=>$this->Suffix
        );
    }


    public function InitializeWithValues($field,$firstName,$lastName,$prefix,$middle,$suffix)
    {
        $this->Initialize($field);

        $this->FirstName=$firstName;
        $this->LastName=$lastName;
        $this->Prefix=$prefix;
        $this->MiddleName=$middle;
        $this->Suffix=$suffix;

        $value='';
        $value=$this->Concatenate($value,$this->Prefix);
        $value=$this->Concatenate($value,$this->FirstName);
        $value=$this->Concatenate($value,$this->MiddleName);
        $value=$this->Concatenate($value,$this->LastName);
        $value=$this->Concatenate($value,$this->Suffix);

        $this->Value=$value;
        return $this;
    }

    public function InitializeWithOptions($field,$options)
    {
        $this->Field=$field;
        if(isset($options->Value))
            $this->Value=$options->Value;
        if(isset($options->FirstName))
            $this->FirstName=$options->FirstName;
        if(isset($options->LastName))
            $this->LastName=$options->LastName;
        if(isset($options->Prefix))
            $this->Prefix=$options->Prefix;
        if(isset($options->MiddleName))
            $this->MiddleName=$options->MiddleName;
        if(isset($options->Suffix))
            $this->Suffix=$options->Suffix;



    }

    public function GetHtml($style='standard',$field=null)
    {

        return new BasicPHPFormatter($this->Value);
    }

    public function Concatenate($previousValue,$valueToConcatenate)
    {
        if ($previousValue != '')
            $previousValue .= ' ';
        $previousValue.=$valueToConcatenate;
        return $previousValue;
    }
}