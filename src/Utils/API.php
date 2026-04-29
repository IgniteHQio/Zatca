<?php

namespace Malik12tree\ZATCA\Utils;

use Malik12tree\ZATCA\Exceptions\APIException;
use Malik12tree\ZATCA\Exceptions\ComplianceException;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;

class API
{
    public const APIS = [
        'sandbox' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal',
        'simulation' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation',
        'production' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core',
    ];
    public const VERSION = 'V2';
    public static $allowWarnings = false;

    private $url;

    public function __construct($env = 'sandbox')
    {
        if (!self::isEnvValid($env)) {
            throw new \Exception('EGS Environment is not valid. Valid environments are '.implode(' | ', array_keys(API::APIS)));
        }
        $this->url = API::APIS[$env];
    }

    public static function isEnvValid($env)
    {
        return array_key_exists($env, API::APIS);
    }

    public function issueComplianceCertificate($csr, $otp)
    {
        $response = $this->post(
            '/compliance',
            [
                'Accept-Version: '.API::VERSION,
                'OTP: '.$otp,
                'Content-Type: application/json',
            ],
            [
                'csr' => base64_encode($csr),
            ],
            'E_COMPLIANCE_CERTIFICATE'
        );

        $payload = $this->extractCertificatePayload($response, 'issue compliance certificate');
        $issuedCertificate = $payload->issued_certificate;
        $apiSecret = $payload->api_secret;
        $requestId = $payload->request_id;

        return (object) [
            'issued_certificate' => $issuedCertificate,
            'api_secret' => $apiSecret,
            'request_id' => $requestId,
        ];
    }

    public function checkInvoiceCompliance($certificate, $secret, $signedInvoice, $invoiceHash, $uuid)
    {
        try {
            return $this->post(
                '/compliance/invoices',
                [
                    'Accept-Version: '.API::VERSION,
                    'Accept-Language: en',
                    'Content-Type: application/json',
                    ...$this->getAuthHeaders($certificate, $secret),
                ],
                [
                    'invoiceHash' => $invoiceHash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode($signedInvoice),
                ],
                'E_COMPLIANCE_CHECK'
            );
        } catch (APIException $e) {
            // Will only throw if applicable
            ComplianceException::throwFromModel(!API::$allowWarnings, $e->getResponse());

            throw $e;
        }
    }

    public function issueProductionCertificate($certificate, $secret, $complianceRequestId)
    {
        $response = $this->post(
            '/production/csids',
            [
                'Accept-Version: '.API::VERSION,
                'Content-Type: application/json',
                ...$this->getAuthHeaders($certificate, $secret),
            ],
            [
                'compliance_request_id' => $complianceRequestId,
            ],
            'E_PRODUCTION_CERTIFICATE'
        );

        $payload = $this->extractCertificatePayload($response, 'issue production certificate');
        $issuedCertificate = $payload->issued_certificate;
        $apiSecret = $payload->api_secret;
        $requestId = $payload->request_id;

        return (object) [
            'issued_certificate' => $issuedCertificate,
            'api_secret' => $apiSecret,
            'request_id' => $requestId,
        ];
    }

    public function renewProductionCertificate($certificate, $secret, $csr, $otp)
    {
        $response = $this->patch(
            '/production/csids',
            [
                'Accept-Version: '.API::VERSION,
                'Content-Type: application/json',
                'OTP: '.$otp,
                ...$this->getAuthHeaders($certificate, $secret),
            ],
            [
                'csr' => base64_encode($csr),
            ],
            'E_RENEW_PRODUCTION_CERTIFICATE'
        );

        $payload = $this->extractCertificatePayload($response, 'renew production certificate');
        $issuedCertificate = $payload->issued_certificate;
        $apiSecret = $payload->api_secret;
        $requestId = $payload->request_id;

        return (object) [
            'issued_certificate' => $issuedCertificate,
            'api_secret' => $apiSecret,
            'request_id' => $requestId,
        ];
    }

    public function reportInvoice($certificate, $secret, $signedInvoice, $invoiceHash, $egsUuid)
    {
        //echo $signedInvoice;
        return $this->post(
            '/invoices/reporting/single',
            [
                'Accept-Version: '.API::VERSION,
                'Accept-Language: en',
                'Content-Type: application/json',
                'Clearance-Status: 0',
                ...$this->getAuthHeaders($certificate, $secret),
            ],
            [
                'invoiceHash' => $invoiceHash,
                'uuid' => $egsUuid,
                'invoice' => base64_encode($signedInvoice),
            ],
            'E_REPORT_INVOICE'
        );
    }

    public function clearanceInvoice($certificate, $secret, $signedInvoice, $invoiceHash, $egsUuid)
    {
        return $this->post(
            '/invoices/clearance/single',
            [
                'Accept-Version: '.API::VERSION,
                'Accept-Language: en',
                'Content-Type: application/json',
                'Clearance-Status: 1',
                ...$this->getAuthHeaders($certificate, $secret),
            ],
            [
                'invoiceHash' => $invoiceHash,
                'uuid' => $egsUuid,
                'invoice' => base64_encode($signedInvoice),
            ],
            'E_CLEARANCE_INVOICE'
        );
    }

    private function getAuthHeaders($certificate, $secret)
    {
        if ($certificate && $secret) {
            $certificate = Crypto::cleanCertificate($certificate);
            $certificate = base64_encode($certificate);

            $basic = base64_encode($certificate.':'.$secret);

            return [
                "Authorization: Basic {$basic}",
            ];
        }

        return [];
    }

    private function request($method, $path, $headers, $data, $errorMessage)
    {
        $curl = curl_init($this->url.$path);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawResponse = curl_exec($curl);
       //echo $response;
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_error($curl)) {
            throw new APIException(curl_error($curl), 0);
        }

        curl_close($curl);

        $response = json_decode($rawResponse, true);
       
        $isSuccess =
            API::$allowWarnings
                ? 200 === $httpCode || 202 === $httpCode
                : 200 === $httpCode;

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new APIException(
                "{$errorMessage}: Failed to decode API response JSON. HTTP {$httpCode}. Raw response: {$rawResponse}",
                $httpCode,
                [
                    'http_code' => $httpCode,
                    'path' => $path,
                    'raw_response' => $rawResponse,
                ]
            );
        }

        if (!is_array($response)) {
            throw new APIException(
                "{$errorMessage}: API returned an unexpected payload type. HTTP {$httpCode}.",
                $httpCode,
                [
                    'http_code' => $httpCode,
                    'path' => $path,
                    'response' => $response,
                    'raw_response' => $rawResponse,
                ]
            );
        }

        if (!$isSuccess) {
            $message = "{$errorMessage}: API request failed with HTTP {$httpCode}.";
            if (isset($response['message']) && is_string($response['message'])) {
                $message .= " Message: {$response['message']}";
            }
            throw new APIException($message, $httpCode, $response);
        }

        return $response;
    }

    private function extractCertificatePayload($response, $operation)
    {
        $requiredKeys = ['binarySecurityToken', 'secret', 'requestID'];
        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new APIException(
                "Failed to {$operation}: Missing expected keys in API response (".implode(', ', $missingKeys).').',
                0,
                $response
            );
        }

        return (object) [
            'issued_certificate' => "-----BEGIN CERTIFICATE-----\n".base64_decode($response['binarySecurityToken'])."\n-----END CERTIFICATE-----",
            'api_secret' => $response['secret'],
            'request_id' => $response['requestID'],
        ];
    }

    private function post($path, $headers, $data, $errorMessage)
    {
        return $this->request('POST', $path, $headers, $data, $errorMessage);
    }

    private function patch($path, $headers, $data, $errorMessage)
    {
        return $this->request('PATCH', $path, $headers, $data, $errorMessage);
    }
}
