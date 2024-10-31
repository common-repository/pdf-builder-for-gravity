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

class GravityListEntryItem extends EntryItemBase
{
    public $Rows=[];
    public $Headers=[];

    protected function InternalGetObjectToSave()
    {

        return (object)array(
            'Headers'=>$this->Headers,
            'Rows'=>$this->Rows
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
        if(!is_array($value)||count($value)==0)
            return null;

        $this->Headers=array_keys($value[0]);

        $this->Rows=$value;


        return $this;
    }

    public function GetHtml($style='standard',$field=null)
    {
        if(count($this->Rows)==0)
            return new GravityListFormatter('','');

        $table='<table style="border-collapse: collapse">';
        if(count($this->Headers)>0)
        {
            $table.='<thead><tr>';
            foreach ($this->Headers as $currentHeader)
            {
                $table .= '<th style="padding:10px;border:1px solid #ccc;">' . esc_html($currentHeader) . '</th>';
            }
            $table.='</tr></thead>';
        }
        $table.='<tbody>';


        if(count($this->Headers)>0)
        {
            foreach ($this->Rows as $currentRow)
            {
                $table .= '<tr>';
                foreach ($this->Headers as $headerId)
                {
                    $value = '';
                    if (isset($currentRow->$headerId))
                        $value = $currentRow->$headerId;
                    $table .= '<td style="padding:10px;border:1px solid #ccc;">' . esc_html($value) . '</td>';
                }

                $table .= '</tr>';

            }
        }else{
            foreach($this->Rows as $currentvalue)
            {
                if(is_scalar($currentvalue))
                    $table .= '<tr><td style="padding:10px;">' . esc_html($currentvalue) . '</td></tr>';
            }
        }
        $table.='</tbody></table>';

        return new GravityListFormatter($table,'');
    }
}