<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/19/2019
 * Time: 11:38 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Loader;


use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\GravityEntryProcessor;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\FormProcessor\GravityFormProcessor;
use rednaoformpdfbuilder\Integration\Processors\Loader\ProcessorLoaderBase;

class GravityProcessorLoader extends ProcessorLoaderBase
{

    public function Initialize()
    {
        $this->FormProcessor=new GravityFormProcessor($this->Loader);
        $this->EntryProcessor=new GravityEntryProcessor($this->Loader);
    }
}