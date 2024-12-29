<?php

namespace Malik12tree\ZATCA\Utils\Encoding;



class Crypto
{
    public static function uuid4()
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0F | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public static function isUUID($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }

    public static function hashSHA256($data)
    {
        return hash('sha256', $data, true);
    }

    public static function hashSHA256HighNibble($data)
    {
        return pack('H*', hash('sha256', $data, true));
    }

    public static function signSHA256($data, $privateKey)
    {
        openssl_sign($data, $signature, $privateKey, 'sha256');

        return $signature;
    }

    public static function generateSecp256k1KeyPair()
    {
        $res = openssl_pkey_new([
            'config' => getenv('OPENSSL_CONF'),
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp256k1',
        ]);

        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [$privateKey, $publicKey, $res];
    }

    public static function getCertificateSignature($certificate)
    {
        $res = openssl_x509_read($certificate);
        openssl_x509_export($res, $out, false);

        $out = explode('Signature Algorithm:', $out);
        $out = explode('-----BEGIN CERTIFICATE-----', $out[2]);
        $out = explode("\n", $out[0]);
        $out = $out[1].$out[2].$out[3].$out[4].$out[5];
        $out = str_replace([':', ' ', 'SignatureValue'], '', $out);

        return pack('H*', $out);
    }





    public static function getCertificateInfo($certificate)
    {
        $certificate = self::setCertificateTitle($certificate, 'CERTIFICATE');
        $certificateHash = base64_encode(bin2hex(self::hashSHA256(self::cleanCertificate($certificate))));

        /** @var mixed */
        $x509 = openssl_x509_parse($certificate);

        $res = openssl_get_publickey($certificate);
        $cert = openssl_pkey_get_details($res);

        $issuer = self::formatIssuer($x509['issuer']);

       
        $serialNumber = self::hexToDecimal($x509['serialNumberHex']);
        //$serialNumber = 12323;
        $publicKey = base64_decode(self::cleanCertificate($cert['key']));

        $signature = self::getCertificateSignature($certificate);

        return [
            'hash' => $certificateHash,
            'issuer' => $issuer,
            'serialNumber' => $serialNumber,
            'publicKey' => $publicKey,
            'signature' => $signature,
            'birthDate' => $x509['validFrom_time_t'],
            'expiryDate' => $x509['validTo_time_t'],
        ];
    }

    
    public static function hexToDecimal($hex) {
        $hex_digits = '0123456789abcdef';
        $hex = strtolower($hex); // Convert to lowercase for consistency
        $decimal = '0';
    
        // Iterate over each digit of the hexadecimal string
        for ($i = 0; $i < strlen($hex); $i++) {
            // Find the decimal value of the current hexadecimal digit
            $digit = strpos($hex_digits, $hex[$i]);
            if ($digit === false) {
                continue; // Skip invalid characters
            }
    
            // Multiply the current decimal value by 16 (shift left by one hexadecimal place)
            $decimal = bcmul($decimal, '16');
            
            // Add the decimal value of the current digit
            $decimal = bcadd($decimal, (string)$digit);
        }
        return $decimal;
    }

    public static function formatIssuer($issuer)
    {
        $issuer = array_reverse($issuer, true);

        $issuerString = '';
        foreach ($issuer as $key => $value) {
            if (is_string($value)) {
                if (!empty($issuerString)) {
                    $issuerString .= ', ';
                }

                $issuerString .= $key.'='.$value;
            } elseif (is_array($value)) {
                $value = array_reverse($value);
                foreach ($value as $subValue) {
                    if (!empty($issuerString)) {
                        $issuerString .= ', ';
                    }

                    $issuerString .= $key.'='.$subValue;
                }
            }
        }

        return $issuerString;
    }

    public static function cleanCertificate($certificate)
    {
        return trim(preg_replace('/(-----[^-]+)-----/', '', $certificate));
    }

    public static function wrapCertificate($certificate, $title)
    {
        return "-----BEGIN {$title}-----\n".$certificate."\n-----END {$title}-----";
    }

    public static function setCertificateTitle($certificate, $title)
    {
        return self::wrapCertificate(self::cleanCertificate($certificate), $title);
    }
}
