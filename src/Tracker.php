<?php

namespace Riesenia\SpsWebship;

use InvalidArgumentException;
use SoapClient;
use SoapFault;
use stdClass;

/**
 * API client for getting shipment status.
 *
 * @author Tomas Saghy <segy@riesenia.com>
 */
class Tracker
{
    protected int $customer;

    protected int $customerType;

    protected string $language;

    /**
     * Constructor.
     *
     * @param string $language
     * @param int $customer
     * @param int $customerType
     */
    public function __construct(string $language, int $customer, int $customerType = 1)
    {
        $this->language = $language;
        $this->customer = $customer;
        $this->customerType = $customerType;
    }

    /**
     * Get shipment status history by shipment number.
     *
     * @param string $number
     *
     * @return array
     */
    public function getStatusHistory(string $number): array
    {
        if (!($shipmentNumber = $this->parseShipmentNumber($number))) {
            throw new InvalidArgumentException('Invalid shipment number format!');
        }

        $payload = [
            'langi' => $this->language,
            'landnr' => (int) $shipmentNumber['landnr'],
            'mandnr' => $shipmentNumber['mandnr'],
            'lfdnr' => $shipmentNumber['lfdnr']
        ];

        $response = $this->makeRequest('GetParcelStatus', $payload);

        return $response ?? [];
    }

    /**
     * Get shipment by shipment number.
     *
     * @param string $number
     *
     * @return array|null
     */
    public function getShipment(string $number): ?array
    {
        if (!($shipmentNumber = $this->parseShipmentNumber($number))) {
            throw new InvalidArgumentException('Invalid shipment number format!');
        }

        $payload = [
            'langi' => $this->language,
            'landnr' => (int) $shipmentNumber['landnr'],
            'mandnr' => $shipmentNumber['mandnr'],
            'lfdnr' => $shipmentNumber['lfdnr']
        ];

        return $this->makeRequest('GetShipment', $payload);
    }

    /**
     * Get shipments by reference number.
     *
     * @param string $reference
     * @param string $date
     *
     * @return array
     */
    public function getShipments(string $reference, string $date = ''): array
    {
        $payload = [
            'kundenr' => (string) $this->customer,
            'verknr' => $reference,
            'km_mandr' => (string) $this->customerType,
            'versdat' => $date,
            'langi' => $this->language
        ];

        $response = $this->makeRequest('GetListOfShipments', $payload);

        return $response ?? [];
    }

    /**
     * Make HTTP POST request to REST API.
     *
     * @param string $endpoint
     * @param array  $payload
     *
     * @return array|null
     */
    protected function makeRequest(string $endpoint, array $payload): ?array
    {
        $url = 'https://trackandtraceapi.nesy.sps-sro.sk/api/sk/v1/TrackAndTrace/' . $endpoint . '?apikey=%20ea409786-31bd-4d22-9831-578551e743a6';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: text/plain'
                ],
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ]);

        $response = file_get_contents($url, false, $context);

        if (!$response) {
            return null;
        }

        $decoded = json_decode($response, true);

        if (!$decoded || !isset($decoded['resultCode']) || $decoded['resultCode'] !== 200) {
            return null;
        }

        return $decoded['payload'] ?? null;
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
        if (preg_match('/[0-9]+/', $number)) {
            return [
                'landnr' => '',
                'mandnr' => '',
                'lfdnr' => $number
            ];
        }

        if (preg_match('/([0-9]{3})-([0-9]{3})-([0-9]+)/', $number, $m)) {
            return [
                'landnr' => $m[1],
                'mandnr' => $m[2],
                'lfdnr' => $m[3]
            ];
        }

        return null;
    }
}
