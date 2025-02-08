<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Http\Request;
use Greenter\See;

class InvoiceController extends Controller
{
    public function send(){
        $see = new See();
        $see->setCertificate(file_get_contents(__DIR__.'/certificate.pem'));
        $see->setService(SunatEndpoints::FE_BETA);
        $see->setClaveSOL('20000000001', 'MODDATOS', 'moddatos');

        return $see;
    }
}
