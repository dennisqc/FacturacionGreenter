<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\SunatService;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company as CompanyCompany;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\XmlUtils;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Http\Request;
use Greenter\See;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Tymon\JWTAuth\Facades\JWTAuth;

class InvoiceController extends Controller
{
    public function send(Request $request)
    {

        $data = $request->all();
        $company = Company::where('user_id', JWTAuth::user()->id)->firstorFail();
        // return $company;

        $sunat = new SunatService();
        $see = $sunat->getSee($company);

        // $invoice->setDetails([$item])
        //     ->setLegends([$legend]);

        $invoice = $sunat->getInvoice($data);
        $result = $see->send($invoice);

        $response['xml'] = $see->getFactory()->getLastXml();
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']);
        $response['sunatResponse'] = $sunat->sunatResponse($result);

        // return $sunat->sunatResponse($result);

        return response()->json($response,200);

        // return $see;
    }
}
