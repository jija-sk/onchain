<?php

namespace Onchain;

use InvalidArgumentException;
class Formatter {
    /**
     * @desc 对方法名进行签名
     * @param string $method
     * @return string
     */
    public static function toMethodFormat(string $method): string {
        try {
            $method_sha3 = Utils::sha3($method);
        }catch (InvalidArgumentException $e){
            throw new InvalidArgumentException('method error: '.$e->getMessage());
        }
        return Utils::stripZero(substr($method_sha3, 0, 10));
    }

    /**
     * @desc 地址进行签名
     * @param string $address
     * @return string
     */
    public static function toAddressFormat(string $address): string {
        if (Utils::isAddress($address)) {
            $address = strtolower($address);

            if (Utils::isZeroPrefixed($address)) {
                $address = Utils::stripZero($address);
            }
        }
        return implode('', array_fill(0, 64 - strlen($address), 0)) . $address;
    }

    /**
     * @desc 数字进行签名
     * @param mixed $value
     * @param int $digit
     * @return string
     */
    public static function toIntegerFormat(mixed $value, int $digit = 64): string {
        $bn = Utils::toBn($value);
        $bnHex = $bn->toHex(true);
        $padded = mb_substr($bnHex, 0, 1);

        if ($padded !== 'f') {
            $padded = '0';
        }
        return implode('', array_fill(0, $digit - mb_strlen($bnHex), $padded)) . $bnHex;
    }

}