<?php


namespace App\Services;

use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;

class SunatService
{
    public function getSee($company)
    {
        $certificate = Storage::get($company->cert_path);

        // return $company;
        $see = new See();
        $see->setCertificate($certificate);
        $see->setService($company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);
        $see->setClaveSOL($company->ruc, $company->sol_user, $company->sol_pass);
        return $see;
    }

    public function getInvoice()
    {
        // Venta
        return (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101') // Venta - Catalog. 51
            ->setTipoDoc('01') // Factura - Catalog. 01
            ->setSerie('F001')
            ->setCorrelativo('1')
            ->setFechaEmision(new DateTime()) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda('PEN') // Sol - Catalog. 02
            ->setCompany($this->getCompany())
            ->setClient($this->getClient())
            ->setMtoOperGravadas(100.00)
            ->setMtoIGV(18.00)
            ->setTotalImpuestos(18.00)
            ->setValorVenta(100.00)
            ->setSubTotal(118.00)
            ->setMtoImpVenta(118.00)
            ->setDetails($this->getDetails())
            ->setLegends($this->getLegends());
    }

    public function getCompany()
    {

        return (new Company())
            ->setRuc('20123456789')
            ->setRazonSocial('GREEN SAC')
            ->setNombreComercial('GREEN')
            ->setAddress($this->getAddress());
    }

    public function getClient()
    {

        // Cliente
        return (new Client())
            ->setTipoDoc('6')
            ->setNumDoc('20000000001')
            ->setRznSocial('EMPRESA X');
    }

    public function getAddress()
    {
        // Emisor
        return (new Address())
            ->setUbigueo('150101')
            ->setDepartamento('LIMA')
            ->setProvincia('LIMA')
            ->setDistrito('LIMA')
            ->setUrbanizacion('-')
            ->setDireccion('Av. Villa Nueva 221')
            ->setCodLocal('0000'); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.


    }

    public function getDetails()
    {
        $item = (new SaleDetail())
            ->setCodProducto('P001')
            ->setUnidad('NIU') // Unidad - Catalog. 03
            ->setCantidad(2)
            ->setMtoValorUnitario(50.00)
            ->setDescripcion('PRODUCTO 1')
            ->setMtoBaseIgv(100)
            ->setPorcentajeIgv(18.00) // 18%
            ->setIgv(18.00)
            ->setTipAfeIgv('10') // Gravado Op. Onerosa - Catalog. 07
            ->setTotalImpuestos(18.00) // Suma de impuestos en el detalle
            ->setMtoValorVenta(100.00)
            ->setMtoPrecioUnitario(59.00);


        return [$item];
    }

    public function getLegends()
    {
        $legend = (new Legend())
            ->setCode('1000') // Monto en letras - Catalog. 52
            ->setValue('SON DOSCIENTOS TREINTA Y SEIS CON 00/100 SOLES');
        return [$legend];
    }
    public function sunatResponse($result)
    {
        $response['success'] = $result->isSuccess();

        // Verificamos que la conexión con SUNAT fue exitosa.
        if (!$result->isSuccess()) {

            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];

            // Mostrar error al conectarse a SUNAT.

            return $response;
        }


        file_put_contents('R-' . $this->getInvoice()->getName() . '.zip', $result->getCdrZip());
        $response['cdrZip'] = base64_encode($result->getCdrZip());


        $cdr = $result->getCdrResponse();

        $response['cdrResponse'] = [
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes(),

        ];
        return $response;
        // $code = (int)$cdr->getCode();

        // if ($code === 0) {
        //     echo 'ESTADO: ACEPTADA' . PHP_EOL;
        //     if (count($cdr->getNotes()) > 0) {
        //         echo 'OBSERVACIONES:' . PHP_EOL;
        //         // Corregir estas observaciones en siguientes emisiones.
        //         var_dump($cdr->getNotes());
        //     }
        // } else if ($code >= 2000 && $code <= 3999) {
        //     echo 'ESTADO: RECHAZADA' . PHP_EOL;
        // } else {
        //     /* Esto no debería darse, pero si ocurre, es un CDR inválido que debería tratarse como un error-excepción. */
        //     /*code: 0100 a 1999 */
        //     echo 'Excepción';
        // }

        // echo $cdr->getDescription() . PHP_EOL;
    }
}
