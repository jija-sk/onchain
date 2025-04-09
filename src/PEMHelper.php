<?php

namespace Onchain;

use kornrunner\Keccak;
use Sop\CryptoTypes\Asymmetric\EC\ECPrivateKey;
use Sop\CryptoEncoding\PEM;
use RuntimeException;
use Elliptic\EC;
use Exception;

class PEMHelper {
    /**
     * Generate a new Private Key in HEX format.
     *
     * @return string Private Key (HEX)
     */
    public static function generateNewPrivateKey(): string {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp256k1'
        ];

        $res = openssl_pkey_new($config);
        if (!$res) {
            throw new RuntimeException('Failed to generate private key: ' . openssl_error_string());
        }

        // Extract private key
        openssl_pkey_export($res, $priv_key);
        if (!$priv_key) {
            throw new RuntimeException('Failed to export private key.');
        }

        // Convert to ASN.1 format and extract hex
        $priv_pem = PEM::fromString($priv_key);
        $ec_priv_key = ECPrivateKey::fromPEM($priv_pem);
        $priv_key_hex = bin2hex($ec_priv_key->toASN1()->at(1)->asOctetString()->string());

        return $priv_key_hex;
    }

    /**
     * Convert Public Key to Address.
     *
     * @param string $publicKey Public Key (HEX)
     * @return string Ethereum-compatible address
     */
    public static function publicKeyToAddress(string $publicKey): string {
        $publicKey = Utils::stripZero($publicKey);
        Utils::validateHex($publicKey, 130, 'Invalid public key format or length.');
        $hashed = self::sha3(hex2bin(substr($publicKey, 2)));

        return '0x' . substr($hashed, -40);
    }

    /**
     * Convert Private Key to Address.
     *
     * @param string $privateKey Private Key (HEX)
     * @return string Ethereum-compatible address
     */
    public static function privateKeyToAddress(string $privateKey): string {
        return self::publicKeyToAddress(self::privateKeyToPublicKey($privateKey));
    }

    /**
     * Convert Private Key to Public Key.
     *
     * @param string $privateKey Private Key (HEX)
     * @return string Public Key (HEX)
     */
    public static function privateKeyToPublicKey(string $privateKey): string {
        Utils::validateHex($privateKey, 64, 'Invalid private key format or length.');

        $secp256k1 = new EC('secp256k1');
        $keyPair = $secp256k1->keyFromPrivate($privateKey, 'hex');
        return '0x' . $keyPair->getPublic(false, 'hex');  // Uncompressed format
    }

    /**
     * Compute Keccak-256 (SHA3) hash.
     * @param string $value Input data
     * @return string|null Keccak-256 hash or null if empty
     * @throws Exception
     */
    public static function sha3(string $value): ?string {
        try {
            $hash = Keccak::hash($value, 256);
        }catch (Exception $e){
            throw new Exception('Unsupported Keccak Hash output size.');
        }
        return ($hash === 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470') ? null : $hash;
    }
}