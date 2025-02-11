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

    public function getInvoice($data)
    {
        // Venta
        return (new Invoice())
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoOperacion($data['tipoOperacion'] ?? null) // Venta - Catalog. 51
            ->setTipoDoc($data['tipoDoc'] ?? null) // Factura - Catalog. 01
            ->setSerie($data['serie'] ?? null)
            ->setCorrelativo($data['correlativo'] ?? null)
            ->setFechaEmision(new DateTime($data['fechaEmision'] ?? null)) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda($data['tipoMoneda'] ?? null) // Sol - Catalog. 02
            ->setCompany($this->getCompany($data['company']))
            ->setClient($this->getClient($data['client']))

            //Mto Operaciones
            ->setMtoOperGravadas($data['mtoOperGravadas'] ?? null)
            // ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?? null)
            // ->setMtoOperInafectas($data['mtoOperInafectas'] ?? null)
            // ->setMtoOperExportacion($data['mtoOperExportacion'] ?? null)
            // ->setMtoOperGratuitas($data['mtoOperGratuitas'] ?? null)

            //Impuestos
            ->setMtoIGV($data['mtoIGV'])
            // ->setMtoIGVGratuitas($data['mtoIGVGratuitas'])
            // ->setIcbper($data['icbper'])
            ->setTotalImpuestos($data['totalImpuestos'])

            //Totales
            ->setValorVenta($data['valorVenta'])
            ->setSubTotal($data['subTotal'])
            // ->setRedondeo($data['redondeo'])
             ->setMtoImpVenta($data['mtoImpVenta'])

            //Productos
            ->setDetails($this->getDetails($data['details']))

            //Leyendas
            ->setLegends($this->getLegends($data['legends']));
    }

    public function getCompany($company)
    {

        return (new Company())
            ->setRuc($company['ruc'] ?? null)
            ->setRazonSocial($company['razonSocial'] ?? null)
            ->setNombreComercial($company['nombreComercial'] ?? null)
            ->setAddress($this->getAddress($company['address']) ?? null);
    }

    public function getClient($client)
    {

        // Cliente
        return (new Client())
            ->setTipoDoc($client['tipoDoc'] ?? null) // DNI - Catalog. 06
            ->setNumDoc($client['numDoc'] ?? null)
            ->setRznSocial($client['rznSocial'] ?? null);
    }

    public function getAddress($address)
    {
        // Emisor
        return (new Address())
            ->setUbigueo($address['ubigeo'] ?? null)
            ->setDepartamento($address['departamento'] ?? null)
            ->setProvincia($address['provincia'] ?? null)
            ->setDistrito($address['distrito'] ?? null)
            ->setUrbanizacion($address['urbanizacion'] ?? null)
            ->setDireccion($address['direccion'] ?? null)
            ->setCodLocal($address['codLocal'] ?? null); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.


    }

    public function getDetails($details)
    {
        $green_details = [];

        foreach ($details as $detail) {
            $green_details[] = (new SaleDetail())
                ->setCodProducto($detail['codProducto'] ?? null)
                ->setUnidad($detail['unidad'] ?? null) // Unidad - Catalog. 03
                ->setCantidad($detail['cantidad'] ?? null)
                ->setMtoValorUnitario($detail['mtoValorUnitario'] ?? null)
                ->setDescripcion($detail['descripcion'] ?? null)
                ->setMtoBaseIgv($detail['mtoBaseIgv'] ?? null)
                ->setPorcentajeIgv($detail['porcentajeIgv'] ?? null) // 18%
                ->setIgv($detail['igv'] ?? null)
                // ->setFactorIcbper($detail['factorIcbper'] ?? null) // 0.3%
                // ->setIcbper($detail['icbper'] ?? null)
                ->setTipAfeIgv($detail['tipAfeIgv'] ?? null) // Gravado Op. Onerosa - Catalog. 07
                ->setTotalImpuestos($detail['totalImpuestos'] ?? null) // Suma de impuestos en el detalle
                ->setMtoValorVenta($detail['mtoValorVenta'] ?? null)
                ->setMtoPrecioUnitario($detail['mtoPrecioUnitario'] ?? null);
        }
        return $green_details;
    }
    public function getLegends($legends)
    {
        $green_legends = [];

        foreach ($legends as $legend) {
            $green_legends[] = (new Legend())
                ->setCode($legend['code'] ?? null) // Monto en letras - Catalog. 52
                ->setValue($legend['value'] ?? null);
        }

        return $green_legends;
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
