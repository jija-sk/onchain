<?php

namespace Onchain\okx;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Onchain\Utils;

class OKTC {
    private static string $base_url = '';

    public static $oktc_client = null;
    public static string $contract_usdt_address = '';
    public static int $chain_id = 0;
    public static function initConifg() {
        if(self::$contract_usdt_address === ''){
            self::$contract_usdt_address = '0x382bb369d343125bfb2117af9c149795c6c65c50';
        }
        if(self::$chain_id == 0){
            self::$chain_id = 66;
        }
        if(self::$base_url == ''){
            self::$base_url = 'https://exchainrpc.okex.org';
        }
    }

    public static function initClient(): Client {
        self::initConifg();
        if (is_null(self::$oktc_client)) {
            self::$oktc_client = new Client([
                'verify' => false,
                'base_uri' => self::$base_url,
                'timeout' => 10, // 请求超时时间
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        return self::$oktc_client;
    }

    /**
     * @desc 获取 nonce (转换为 16 进制)
     * @param string $address
     * @return false|string hex
     */
    public static function getNonce(string $address): false|string {
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_getTransactionCount',
                    'params' => [$address, 'pending'],
                    'id' => 1,
                ]
            ]);
        }catch (RequestException|GuzzleException $e){
            return false;
        }
        $data = json_decode($response->getBody(), true);
        if (!Utils::isHex($data['result'])) {
            return false;
        }
        return (string)$data['result'];
    }

    /**
     * @desc 燃料费 (转换为 16 进制)
     * @param string $level
     * @return false|string
     */
    public static function gasPriceOracle(string $level = 'standard'): false|string {
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_gasPrice',
                    'params' => [],
                    'id' => 1,
                ]
            ]);
        }catch (RequestException $e){
            return false;
        } catch (GuzzleException $e) {
            var_dump('异常:' . $e->getMessage());
            return false;
        }
        $data = json_decode($response->getBody(), true);
        if (!Utils::isHex($data['result'])) {
            var_dump('非 hex' . $data['result']);
            return false;
        }
        $baseGasPrice = hexdec($data['result']);
        $multiplier = match ($level) {
            'fast' => 1.2,
            'rapid' => 1.5,
            default => 1.0,
        };

        $price_oracle = bcmul($baseGasPrice, $multiplier, 0);
        return Utils::toHex($price_oracle, true);
    }

    /**
     * @desc 判断是否存在链上广播
     * @param string $tx_hash
     * @return array
     */
    public static function getTransactionByHash(string $tx_hash): array {
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_getTransactionByHash',
                    'params' => [$tx_hash],
                    'id' => 1,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['result'])) {
                return [
                    'code' => 400,
                    'msg' => 'tx_hash is empty',
                ];
            }
            if(is_null($data['result'])){
                return [
                    'code' => 400,
                    'msg' => 'tx_hash is null',
                ];
            }
            if (is_null($data['result']['blockNumber'])) {
                return [
                    'code' => 400,
                    'msg' => 'tx_hash is pending',
                ];
            }
        }catch (RequestException $e){
            return [
                'code' => 400,
                'msg' => 'getTransactionByHash RequestException error, tx_hash is ' . $tx_hash .' error:' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'code' => 400,
                'msg' => 'getTransactionByHash GuzzleException error, tx_hash is ' . $tx_hash .' error:' . $e->getMessage(),
            ];
        }
        return [
            'code' => 200,
            'data' => $data['result'],
            'msg' => 'getTransactionByHash block_num is ' . $data['result']['blockNumber'] .' tx_hash is ' . $tx_hash,
        ];
    }

    /**
     * @desc 交易结果
     * @param string $tx_hash
     * @param int $id
     * @return array
     */
    public static function getTransactionReceipt(string $tx_hash, int $id = 1):array{
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_getTransactionReceipt',
                    'params' => [$tx_hash],
                    'id' => $id,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['result']) || is_null($data['result'])) {
                return [
                    'code' => 400,
                    'msg' => 'tx_hash is empty',
                ];
            }

            return [
                'code' => 200,
                'data' => $data['result'],
                'msg' => 'success',
            ];
//            if(!isset($data['result']['status']) || !Utils::isHex($data['result']['status'])){
//                return [
//                    'code'=>400,
//                    'msg'=> 'hash status is error',
//                ];
//            }
//            $status = hexdec($data['result']['status']);
//            if($status !=1){
//                return [
//                    'code'=>201,
//                    'msg'=> 'hash status is error',
//                ];
//            }
//            return [
//                'code'=>200,
//                'msg'=> 'hash status is success',
//            ];
        }catch (RequestException $e){
            return [
                'code' => 400,
                'msg' => 'getTransactionReceipt RequestException error, tx_hash is ' . $tx_hash .' error:' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'code' => 400,
                'msg' => 'getTransactionReceipt GuzzleException error, tx_hash is ' . $tx_hash .' error:' . $e->getMessage(),
            ];
        }
    }

    /**
     * @desc 广播交易
     * @param string $raw
     * @return array
     */
    public static function sendRaw(string $raw): array {
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_sendRawTransaction',
                    'params' => [$raw],
                    'id' => 1,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['result'])) {
                return [
                    'code' => 400,
                    'msg' => $data['error']['message'] ?? 'send raw transaction failed',
                ];
            }

        }catch (RequestException $e){
            return [
                'code' => 400,
                'msg' => 'sendRaw RequestException error, raw is ' . $raw .' error:' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'code' => 400,
                'msg' => 'sendRaw GuzzleException error, raw is ' . $raw .' error:' . $e->getMessage(),
            ];
        }
        return [
            'code' => 200,
            'data' => [
                'tx_hash' => $data['result'],
            ],
            'msg' => 'success',
        ];
    }

    /**
     * @desc 获取最新区块号
     * @return false|int
     */
    public static function getNewBlockNumber(): false|int {
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_blockNumber',
                    'params' => [],
                    'id' => 1,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['result']) || !Utils::isHex($data['result'])) {
                return false;
            }
            return hexdec($data['result']);
        }catch (RequestException|GuzzleException $e){
            return false;
        }
    }

    /**
     * @desc 获取区块详情
     * @param int|string $number
     * @param bool $fullTxObj
     * @return array
     */
    public static function getBlockInfoByNumber(int|string $number, bool $fullTxObj = false): array {
        $is_hex = Utils::isHex($number);
        if (!$is_hex) {
            $hex_number = Utils::toHex($number, true);
        } else {
            $hex_number = $number;
            $number = hexdec($number);
        }
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_getBlockByNumber',
                    'params' => [$hex_number, $fullTxObj],
                    'id' => 1,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['result'])) {
                return [
                    'code' => 400,
                    'msg' => 'getBlockInfoByNum failed: ' . $number,
                ];
            }
            return [
                'code' => 200,
                'data' => $data['result'],
            ];
        }catch (RequestException $e){
            return [
                'code' => 400,
                'msg' => 'getBlockInfoByNumber RequestException error, block_num: ' . $number .' error:' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'code' => 400,
                'msg' => 'getBlockInfoByNumber GuzzleException error, block_num: ' . $number .' error:' . $e->getMessage(),
            ];
        }

    }

    private static function getAPIKey() {

    }
}