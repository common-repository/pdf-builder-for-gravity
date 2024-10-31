<?php


namespace rednaoformpdfbuilder\Integration\Adapters\Gravity\Loader;
use rednaoformpdfbuilder\core\Loader;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\FormProcessor\GravityFormProcessor;
use rednaoformpdfbuilder\pr\PRLoader;
use rednaoformpdfbuilder\Integration\Adapters\Gravity\Entry\Retriever\GravityEntryRetriever;

class GravitySubLoader extends Loader
{

    public function __construct($rootFilePath,$config)
    {
        $this->ItemId=31;
        $prefix='rednaopdfgravity';
        $formProcessorLoader=new GravityProcessorLoader($this);
        $formProcessorLoader->Initialize();
        parent::__construct($prefix,$formProcessorLoader,$rootFilePath,$config);
        $this->AddMenu('Gravity PDF Builder',$prefix.'_pdf_builder','pdfbuilder_manage_templates','','Pages/BuilderList.php');
        \add_filter('wpforms_frontend_confirmation_message',array($this,'AddPDFLink'),10,2);
        if($this->IsPR())
        {
            $this->PRLoader=new PRLoader($this);
        }else{
            $this->AddMenu('Entries',$prefix.'_pdf_builder_entries','manage_options','','Pages/EntriesFree.php');
        }
    }

    public function GetForm($formId)
    {
        global $wpdb;
        $results=$wpdb->get_results($wpdb->prepare("select form_id, display_meta,notifications from ".$wpdb->prefix."gf_form_meta where form_id=%d",$formId),'ARRAY_A');
        if(count($results)==0)
            return null;

        $formProcessor=$this->ProcessorLoader->FormProcessor;
        return $formProcessor->SerializeForm($results[0]);
    }

    public function GetEntry($entryId)
    {
        return \GFAPI::get_entry($entryId);
    }


    public function AddPDFLink($message,$formData)
    {
        global $RNWPCreatedEntry;
        if(!isset($RNWPCreatedEntry['CreatedDocuments']))
            return $message;

        if(\strpos($message,'[wpformpdflink]')===false)
            return $message;

        $links=array();
        foreach($RNWPCreatedEntry['CreatedDocuments'] as $createdDocument)
        {
            $data=array(
              'entryid'=>$RNWPCreatedEntry['EntryId'],
              'templateid'=>$createdDocument['TemplateId'],
              'nonce'=>\wp_create_nonce($this->Prefix.'_'.$RNWPCreatedEntry['EntryId'].'_'.$createdDocument['TemplateId'])
            );
            $url=admin_url('admin-ajax.php').'?data='.\json_encode($data).'&action='.$this->Prefix.'_view_pdf';
            $links[]='<a target="_blank" href="'.esc_attr($url).'">'.\esc_html($createdDocument['Name']).'.pdf</a>';
        }

        $message=\str_replace('[wpformpdflink]',\implode($links),$message);

        return $message;


    }

    /**
     * @return GravityEntryRetriever
     */
    public function CreateEntryRetriever()
    {
        return new GravityEntryRetriever($this);
    }


    public function AddBuilderScripts()
    {
        $this->AddScript('wpformbuilder','js/dist/WPFormBuilder_bundle.js',array('jquery', 'wp-element','@builder','regenerator-runtime'));
    }

    public function GetPurchaseURL()
    {
        return 'http://pdfbuilder.rednao.com/get-it-gravity/';
    }
}