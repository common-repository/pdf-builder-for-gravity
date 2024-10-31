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
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\MultipleLineFormatter;

class GravityAddressEntryItem extends EntryItemBase
{
    public $Value;
    public $StreetAddress1;
    public $StreetAddress2;
    public $City;
    public $State;
    public $Zip;
    public $Country;
    protected function InternalGetObjectToSave()
    {


        return (object)array(
            'Value'=>$this->Value,
            'StreetAddress1'=>$this->StreetAddress1,
            'StreetAddress2'=>$this->StreetAddress1,
            'City'=>$this->City,
            'State'=>$this->State,
            'Zip'=>$this->Zip,
            'Country'=>$this->Country
        );
    }


    public function InitializeWithValues($field,$streetAddress1,$streetAddress2,$city,$state,$zip,$country)
    {
        $this->Initialize($field);

        $this->StreetAddress1=$streetAddress1;
        $this->StreetAddress2=$streetAddress2;
        $this->City=$city;
        $this->State=$state;
        $this->Zip=$zip;
        $this->Country=$country;

        $value='';
        $value=$this->Concatenate($value,$this->StreetAddress1);
        $value=$this->Concatenate($value,$this->StreetAddress2);
        $value=$this->Concatenate($value,$this->City);
        $value=$this->Concatenate($value,$this->State);
        $value=$this->Concatenate($value,$this->Zip);
        $value=$this->Concatenate($value,$this->Country);

        $this->Value=$value;
        return $this;
    }

    public function InitializeWithOptions($field,$options)
    {
        $this->Field=$field;
        if(isset($options->Value))
            $this->Value=$options->Value;
        if(isset($options->StreetAddress1))
            $this->StreetAddress1=$options->StreetAddress1;
        if(isset($options->StreetAddress2))
            $this->StreetAddress2=$options->StreetAddress2;
        if(isset($options->City))
            $this->City=$options->City;
        if(isset($options->State))
            $this->State=$options->State;
        if(isset($options->Zip))
            $this->Zip=$options->Zip;
        if(isset($options->Country))
            $this->Country=$options->Country;



    }

    public function GetHtml($style='standard',$field=null)
    {

        $formatter=new MultipleLineFormatter();

        if($this->StreetAddress1!='')
            $formatter->AddLine($this->StreetAddress1);
        if($this->StreetAddress2!='')
            $formatter->AddLine($this->StreetAddress2);
        if($this->City!='')
            $formatter->AddLine($this->City);
        if($this->State!='')
            $formatter->AddLine($this->State);
        if($this->Zip!='')
            $formatter->AddLine($this->Zip);
        if($this->Country!='')
            $formatter->AddLine($this->Country);


        return $formatter;
    }

    public function Concatenate($previousValue,$valueToConcatenate)
    {
        if ($previousValue != '')
            $previousValue .= ', ';
        $previousValue.=$valueToConcatenate;
        return $previousValue;
    }
}