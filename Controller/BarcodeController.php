<?php

namespace Mopa\BarcodeBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Mopa\BarcodeBundle\Model\BarcodeTypes;

class BarcodeController extends Controller
{
    /**
     * @Route("/barcode/playground", name="_partner_barcode_playground")
     * @Template()
     */
    public function playgroundAction(Request $request)
    {
        $types = BarcodeTypes::getTypes();
        $errors = array();
        $form = $this->createFormBuilder()
            ->add('text')
            ->add('type', 'choice', array(
                'empty_value' => 'Choose an option',
                'choices' => $types          
            ))
        ->getForm();
        ;
        $webfile = false;
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            $data = $form->getData();
            $text = $data['text'];
            $type = $data['type'];
            if($type){
                $webfile = "/images/barcode_playground/".session_id()."-barcode_test.png";
                $savename =  __DIR__.'/../../../../web'.$webfile;
                try{
                    $bmanager = $this->get('mopa_barcode.barcode_service');
                    $bmanager->saveAs($type, $text, $savename);
                }
                catch(\Exception $e){
                    $errors[] = $e->getMessage();
                }
            }else{
                $errors[] = "Please select a option";
            }
            if(count($errors)){
                
                $webfile = false;
            }
        }
        return array(
            'form'=>$form->createView(),
            'barcode'=>$webfile, 
            'errors'=>$errors,
        );
    }
    
}
