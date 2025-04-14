<?php

namespace Onchain\okx;

use GuzzleHttp\Exception\GuzzleException;
use kornrunner\Ethereum\Transaction;
use kornrunner\Keccak;
use Onchain\Formatter;
use Onchain\PEMHelper;
use Onchain\Utils;
use Exception;

class KIP20 extends OKTC {
    /**
     * @desc 获取余额
     * @param string $address
     * @param int $id
     * @return string
     */
    public static function getBalance(string $address, int $id = 1): string {
        $client = self::initClient();
        try {
            $data = '0x' . substr(Keccak::hash('balanceOf(address)', 256), 0, 8) .
                str_pad(substr(strtolower($address), 2), 64, '0', STR_PAD_LEFT);
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_call',
                    'params' => [
                        [
                            'to' => OKTC::$contract_usdt_address,
                            'data' => $data
                        ],
                        'latest'
                    ],
                    'id' => $id
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['result'])) {
                return '';
            }
            return Utils::toDisplayAmount($data['result'], 18);
        } catch (GuzzleException|Exception $e) {
            return '';
        }
    }

    /**
     * @desc 转账
     * @param string $privateKey
     * @param string $to
     * @param float $value
     * @param string $gasPrice
     * @return array
     */
    public static function transfer(string $privateKey, string $to, float $value, string $gasPrice = 'standard'): array {
        self::initConifg();
        $privateKey = strtolower($privateKey);
        if (str_starts_with($privateKey, '0x')) {
            $privateKey = substr($privateKey, 2);
        }
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = OKTC::getNonce($from);
        if ($nonce === false) {
            return [
                'code' => 400,
                'msg' => 'getNonce failed',
            ];
        }
        $gasPrice = OKTC::gasPriceOracle();
        if ($gasPrice === false) {
            return [
                'code' => 400,
                'msg' => 'gasPrice failed',
            ];
        }
        $value = Utils::toMinUnitByDecimals((string)$value, 18);
        $data = '0xa9059cbb' . Formatter::toAddressFormat($to) . Formatter::toIntegerFormat($value);
        $transaction = new Transaction(
            $nonce,
            $gasPrice,
            '0xea60',
            OKTC::$contract_usdt_address,
            Utils::NONE,
            $data);
        $raw = $transaction->getRaw($privateKey, OKTC::$chain_id);
        $send_raw_result = OKTC::sendRaw('0x' . $raw);
        if (!isset($send_raw_result['code']) || $send_raw_result['code'] != 200) {
            return [
                'code' => 400,
                'msg' => 'sendRaw failed : ' . $send_raw_result['msg'],
            ];
        }
        return [
            'code' => 200,
            'data' => [
                'tx_hash' => $send_raw_result['data']['tx_hash'],
            ],
            'msg' => 'okt kip20 transfer success',
        ];
    }

    public static function gasLimit(string $from,string $to) {
        $client = self::initClient();
        try {
            $data = '0xa9059cbb' . Formatter::toAddressFormat($to) . Formatter::toIntegerFormat(1);
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_estimateGas',
                    'params' => [
                        [
                            'from' => $from,
                            'to' => $to,
                            'data' => $data
                        ],
                    ],
                    'id' => 1
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            var_dump($data);
            if (!isset($data['result'])) {
                var_dump('111');
                return '';
            }
            return Utils::toDisplayAmount($data['result'], 18);
        } catch (GuzzleException|Exception $e) {
            var_dump('2234');
            var_dump($e->getMessage());
            return '';
        }
    }
}