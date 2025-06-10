<?php

namespace Onchain;

class Base58Check {
    const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    public static function tronHexToBase58($hexAddress)
    {
        $address = hex2bin($hexAddress);
        $hash0 = hash("sha256", $address, true);
        $hash1 = hash("sha256", $hash0, true);
        $checksum = substr($hash1, 0, 4);
        $base58 = self::base58_encode($address . $checksum);
        return $base58;
    }

    public static function base58_encode($string)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $out = '';
        $num = gmp_init(bin2hex($string), 16);
        while (gmp_cmp($num, 0) > 0) {
            list($num, $rem) = gmp_div_qr($num, 58);
            $out = $alphabet[gmp_intval($rem)] . $out;
        }
        return $out;
    }
    public static function encode(string $input): string
    {
        $checksum = substr(hash('sha256', hex2bin(hash('sha256', bin2hex($input)))), 0, 8);
        $full = bin2hex($input) . $checksum;
        $num = gmp_init($full, 16);
        $encode = '';

        while (gmp_cmp($num, 0) > 0) {
            list($num, $rem) = gmp_div_qr($num, 58);
            $encode = self::ALPHABET[gmp_intval($rem)] . $encode;
        }

        for ($i = 0; $i < strlen($input) && $input[$i] === "\x00"; $i++) {
            $encode = '1' . $encode;
        }

        return $encode;
    }
}