<?php
namespace Riesenia\SpsWebship;

/**
 * API client for getting shipment status.
 *
 * @author Tomas Saghy <segy@riesenia.com>
 */
class Tracker
{
    /** @var int */
    protected $customer;

    /** @var int */
    protected $customerType;

    /** @var string */
    protected $language;

    /** @var string */
    protected $wsdl = 'https://t-t.sps-sro.sk/service_soap.php?wsdl';

    /** @var string */
    protected $httpsWsdl = 'https://t-t.sps-sro.sk/service_soap.php?wsdl';

    protected \SoapClient $soap;

    /**
     * Constructor.
     *
     * @param string $language
     * @param int $customer
     * @param int $customerType
     * @param bool $useHttpsWsdl
     * @throws \SoapFault
     */
    public function __construct(string $language, int $customer, int $customerType = 1, bool $useHttpsWsdl = false)
    {
        $this->language = $language;
        $this->customer = $customer;
        $this->customerType = $customerType;

        $this->soap = new \SoapClient(!$useHttpsWsdl ? $this->wsdl : $this->httpsWsdl);
    }

    /**
     * Get shipment status history by shipment number.
     *
     * @param string $number
     *
     * @return \stdClass[]
     */
    public function getStatusHistory(string $number): array
    {
        if (!($shipmentNumber = $this->parseShipmentNumber($number))) {
            throw new \InvalidArgumentException('Invalid shipment number format!');
        }

        try {
            $response = $this->soap->__call('getParcelStatus', [
                ...$shipmentNumber,
                'langi' => $this->language
            ]);
        } catch (\SoapFault $e) {
            return [];
        }

        return $response ?? [];
    }

    /**
     * Get shipment by shipment number.
     *
     * @param string $number
     *
     * @return \stdClass|null
     */
    public function getShipment(string $number): ?\stdClass
    {
        if (!($shipmentNumber = $this->parseShipmentNumber($number))) {
            throw new \InvalidArgumentException('Invalid shipment number format!');
        }

        try {
            $response = $this->soap->__call('getShipment', [
                ...$shipmentNumber,
                'langi' => $this->language
            ]);
        } catch (\SoapFault $e) {
            return null;
        }

        return $response;
    }

    /**
     * Get shipments by reference number.
     *
     * @param string $reference
     * @param string $date
     *
     * @return \stdClass[]
     */
    public function getShipments(string $reference, string $date = ''): array
    {
        try {
            $response = $this->soap->__call('getListOfShipments', [
                'kundenr' => $this->customer,
                'verknr' => $reference,
                'km_mandr' => $this->customerType,
                'versdat' => $date,
                'langi' => $this->language
            ]);
        } catch (\SoapFault $e) {
            return [];
        }

        return (array) $response;
    }

    /**
     * Parse shipment number.
     *
     * @param string $number
     *
     * @return null|array {
     *   landnr: string,
     *   mandnr: string,
     *   lfdnr: string
     * }
     */
    protected function parseShipmentNumber(string $number): ?array
    {
        if (\preg_match('/[0-9]+/', $number)) {
            return [
                'landnr' => '',
                'mandnr' => '',
                'lfdnr' => $number
            ];
        }

        if (\preg_match('/([0-9]{3})-([0-9]{3})-([0-9]+)/', $number, $m)) {
            return [
                'landnr' => $m[1],
                'mandnr' => $m[2],
                'lfdnr' => $m[3]
            ];
        }

        return null;
    }
}
