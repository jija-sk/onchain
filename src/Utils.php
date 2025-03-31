<?php

namespace Onchain;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use kornrunner\Keccak;
use phpseclib3\Math\BigInteger;
use InvalidArgumentException;
use Exception;

class Utils {
    const NONE = '0x0';
    const SHA3_NULL_HASH = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';
    const UNITS = [
        'noether' => '0',
        'wei' => '1',
        'kwei' => '1000',
        'Kwei' => '1000',
        'babbage' => '1000',
        'femtoether' => '1000',
        'mwei' => '1000000',
        'Mwei' => '1000000',
        'lovelace' => '1000000',
        'picoether' => '1000000',
        'gwei' => '1000000000',
        'Gwei' => '1000000000',
        'shannon' => '1000000000',
        'nanoether' => '1000000000',
        'nano' => '1000000000',
        'szabo' => '1000000000000',
        'microether' => '1000000000000',
        'micro' => '1000000000000',
        'finney' => '1000000000000000',
        'milliether' => '1000000000000000',
        'milli' => '1000000000000000',
        'ether' => '1000000000000000000',
        'kether' => '1000000000000000000000',
        'grand' => '1000000000000000000000',
        'mether' => '1000000000000000000000000',
        'gether' => '1000000000000000000000000000',
        'tether' => '1000000000000000000000000000000'
    ];

    /**
     * 将数值转换为 16 进制字符串
     * @param string|int|BigInteger $value
     * @param bool $isPrefix 是否添加 "0x" 前缀
     * @return string 16 进制字符串
     */
    public static function toHex(string|int|BigInteger $value, bool $isPrefix = false): string {
        $hex = match (true) {
            is_numeric($value) => self::toBn($value)->toHex(true),
            is_string($value) => bin2hex(self::stripZero($value)),
            $value instanceof BigInteger => $value->toHex(true),
            default => throw new InvalidArgumentException('Unsupported value type for toHex.')
        };
        $hex = ltrim($hex, '0'); // 去掉前导零
        return $isPrefix ? '0x' . $hex : $hex;
    }



    /**
     * @desc 将 16 进制字符串转换为二进制
     * @param string $value
     * @return false|string
     */
    public static function hexToBin(string $value): false|string {
        $value = self::stripZero($value);
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException("Invalid hex string: $value");
        }
        return hex2bin($value) ?: '';
    }

    /**
     * 校验以太坊地址格式
     * @param string $value 以太坊地址
     * @return bool
     */
    public static function isAddress(string $value): bool {
        $value = strtolower($value);
        return preg_match('/^(0x)?[a-f0-9]{40}$/', $value) && (ctype_lower($value) || ctype_upper($value) || self::isAddressChecksum($value));
    }

    /**
     * @desc 校验以太坊地址的 Checksum
     * @param string $value 以太坊地址
     * @return bool
     */
    private static function isAddressChecksum(string $value): bool {
        $hash = self::sha3(substr($value, 2));
        for ($i = 0; $i < 40; $i++) {
            if ((intval($hash[$i], 16) > 7 && strtoupper($value[$i]) !== $value[$i]) ||
                (intval($hash[$i], 16) <= 7 && strtolower($value[$i]) !== $value[$i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @desc 计算 Keccak-256 哈希（sha3）
     * @param string $value
     * @return string|null
     * @throws Exception
     */
    public static function sha3(string $value): ?string {
        return ($hash = Keccak::hash(self::hexToBin($value) ?: $value, 256)) === self::SHA3_NULL_HASH ? null : '0x' . $hash;
    }

    /**
     * @desc 将数值转换为指定单位的 Wei 值
     * @param BigInteger|string|int $number
     * @param string $unit 单位（如 'ether', 'gwei'）
     * @return BigInteger wei 值
     */
    public static function toWei(BigInteger|string|int $number, string $unit): BigInteger {
        return self::convertUnit($number, $unit, true);
    }

    /**
     * @desc 将 wei 单位转换为指定单位
     * @param BigInteger|string|int $number 要转换的数值
     * @param string $unit 目标单位
     * @return array [BigInteger, BigInteger] 返回商和余数
     * @throws InvalidArgumentException 当输入格式错误或单位不支持时抛出异常
     */
    public static function fromWei(BigInteger|string|int $number, string $unit): array {
        return self::convertUnit($number, $unit, false);
    }
    /**
     * @desc 将不同格式的数字转换为 BigInteger
     * @param BigInteger|string|int|float $number
     * @return BigInteger|array  如果是整数或十六进制，则返回 BigInteger，否则返回 [整数部分, 小数部分, 小数位数, 负数标记]
     * @throws InvalidArgumentException 当输入格式错误时抛出异常
     */
    public static function toBn(BigInteger|string|int|float $number): BigInteger|array {
        return match (true) {
            $number instanceof BigInteger => $number,
            is_numeric($number) => new BigInteger((string)$number),
            default => throw new InvalidArgumentException('Invalid number format.')
        };
    }

    /**
     * 发送 HTTP 请求
     * @param string $method 请求方法（GET, POST, PUT, DELETE）
     * @param string $url 请求 URL
     * @param array $options 请求选项
     * @return array 解析后的 JSON 结果
     * @throws Exception
     */
    public static function httpRequest(string $method, string $url, array $options = []): array {
        $client = new Client(['timeout' => 15]);
        try {
            $response = $client->request($method, $url, $options)->getBody();
        } catch (GuzzleException $e) {
            throw new Exception("Request to $url failed: " . $e->getMessage());
        }
        return json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @desc 16进制格式和长度验证
     * @param string $hex
     * @param int $expectedLength
     * @param string $errorMessage
     * @return void
     */
    public static function validateHex(string $hex, int $expectedLength, string $errorMessage): void {
        if (!ctype_xdigit($hex) || strlen($hex) !== $expectedLength) {
            throw new InvalidArgumentException($errorMessage);
        }
    }
    /**
     * @desc 去除 "0x" 前缀（如果存在）
     * @param mixed $value
     * @return string
     */
    public static function stripZero(string $value): string {
        return self::isZeroPrefixed($value) ? substr($value, 2) : $value;
    }

    /**
     * @desc 判断是否是以 0x 开头 16 进制
     * @param string $value
     * @return bool
     */
    private static function isZeroPrefixed(string $value): bool {
        return str_starts_with($value, '0x');
    }

    /**
     * @desc 进行单位换算
     * @param BigInteger|string $number
     * @param string $unit
     * @param bool $toWei
     * @return BigInteger|array
     */
    private static function convertUnit(BigInteger|string $number, string $unit, bool $toWei): BigInteger|array {
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException("Unsupported unit: $unit.");
        }
        $bn = self::toBn($number);
        $bnt = new BigInteger(self::UNITS[$unit]);
        return $toWei ? $bn->multiply($bnt) : $bn->divide($bnt);
    }

}