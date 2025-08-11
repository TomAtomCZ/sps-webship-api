<?php

namespace Riesenia\SpsWebship;

    use SoapClient;
use SoapFault;
use stdClass;

/**
 * API client for sending packages
 *
 * @author Tomas Saghy <segy@riesenia.com>
 */
class Api
{
    protected ?SoapClient $soap = null;

    protected string $username;

    protected string $password;

    protected string $wsdl = 'https://webship.sps-sro.sk/services/WebshipWebService?wsdl';

    protected string $messages = '';

    /**
     * Constructor.
     *
     * @param string $username
     * @param string $password
     * @throws SoapFault
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;

        $this->soap = new SoapClient($this->wsdl);
    }

    /**
     * Call createShipment method.
     *
     * @param array $shipment
     * @param integer $shipmentType (0 - print waybills, 1 - pickup order)
     *
     * @return bool
     */
    public function createShipment(array $shipment, int $shipmentType = 0): bool
    {
        $response = $this->soap->createShipment([
            'name' => $this->username,
            'password' => $this->password,
            'webServiceShipment' => $shipment,
            'webServiceShipmentType' => $shipmentType
        ]);

        if (isset($response->createShipmentReturn->errors) && $response->createShipmentReturn->errors) {
            $this->messages = (string)$response->createShipmentReturn->errors;

            return false;
        }

        if (isset($response->createShipmentReturn->warnings)) {
            $this->messages = (string)$response->createShipmentReturn->warnings;
        }

        return true;
    }


    /**
     * Call createCifShipment method.
     *
     * @param array $shipment
     * @param integer $shipmentType (0 - print waybills, 1 - pickup order)
     *
     * @return bool|stdClass false if creation failed, stdClass with packageInfo otherwise
     */
    public function createCifShipment(array $shipment, int $shipmentType = 0): bool|stdClass
    {
        $response = $this->soap->createCifShipment([
            'name' => $this->username,
            'password' => $this->password,
            'webServiceShipment' => $shipment,
            'webServiceShipmentType' => $shipmentType
        ]);

        if (isset($response->createCifShipmentReturn->result->errors) && $response->createCifShipmentReturn->result->errors) {
            $this->messages = (string)$response->createCifShipmentReturn->result->errors;

            return false;
        }

        if (isset($response->createCifShipmentReturn->result->warnings)) {
            $this->messages = (string)$response->createCifShipmentReturn->result->warnings;
        }

        if (!isset($response->createCifShipmentReturn->packageInfo)) {
            return false;
        }

        return $response->createCifShipmentReturn->packageInfo;
    }

    /**
     * Call printShipmentLabels method.
     *
     * @param array $options
     *
     * @return string
     */
    public function printShipmentLabels(array $options = []): string
    {
        if (!$options) {
            $response = $this->soap->printShipmentLabels([
                'aUserName' => $this->username,
                'aPassword' => $this->password
            ]);

            $returnKey = 'printShipmentLabelsReturn';
        } else {
            $response = $this->soap->printLabelsWithSettings([
                'aUserName' => $this->username,
                'aPassword' => $this->password,
                'aPrintingSettings' => $options
            ]);

            $returnKey = 'printLabelsWithSettingsReturn';
        }

        if (isset($response->{$returnKey}->errors) && $response->{$returnKey}->errors) {
            $this->messages = (string)$response->{$returnKey}->errors;

            return '';
        }

        return $response->{$returnKey}->documentUrl;
    }

    /**
     * Call printEndOfDay method.
     *
     * @return string
     */
    public function printEndOfDay(): string
    {
        $response = $this->soap->printEndOfDay([
            'aUserName' => $this->username,
            'aPassword' => $this->password
        ]);

        if (isset($response->printEndOfDayReturn->errors) && $response->printEndOfDayReturn->errors) {
            $this->messages = (string)$response->printEndOfDayReturn->errors;

            return '';
        }

        return $response->printEndOfDayReturn->documentUrl;
    }

    /**
     * Get error messages.
     *
     * @return string
     */
    public function getMessages(): string
    {
        return $this->messages;
    }
}
