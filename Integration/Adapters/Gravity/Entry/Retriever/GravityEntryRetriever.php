<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 4:30 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\Retriever;


use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\GravityEntryProcessor;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Settings\Forms\GravityFieldSettingsFactory;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\MultipleSelectionEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\MultipleSelectionValueItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryProcessorBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\Retriever\EntryRetrieverBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FieldSettingsFactoryBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FormSettings;

class GravityEntryRetriever extends EntryRetrieverBase
{


    /**
     * @return FieldSettingsFactoryBase
     */
    public function GetFieldSettingsFactory()
    {
        return new GravityFieldSettingsFactory();
    }

    /**
     * @return EntryProcessorBase
     */
    protected function GetEntryProcessor()
    {
        return $this->Loader->ProcessorLoader->EntryProcessor;
    }

    public function GetProductItems()
    {
        $items=array();
        foreach($this->EntryItems as $item)
        {
            switch ($item->Field->SubType)
            {
                case 'product':
                    $items[]= array('name'=>$item->Value,'price'=>$item->Amount,'quantity'=>isset($item->Quantity)?$item->Quantity:1);
                    break;
                case 'payment-select':
                case 'payment-multiple':
                    /** @var MultipleSelectionEntryItem $multipleItem */
                    $multipleItem=$item;

                    foreach($multipleItem->Items as $valueItem)
                    {
                        $items[]= array('name'=>$valueItem->Value,'price'=>$valueItem->Amount,'quantity'=>isset($valueItem->Quantity)?$valueItem->Quantity:1);
                    }
                break;
                case 'payment-single':
                $items[]=array('name'=>$item->Field->Label,'price'=>$item->Value,'quantity'=>isset($item->Quantity)?$item->Quantity:1);
                    break;
            }
        }

        return $items;
    }
}