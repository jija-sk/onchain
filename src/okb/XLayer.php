<?php

namespace Onchain\okb;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Onchain\Utils;

class XLayer {
    private static string $base_url = '';

    public static $xlayer_client = null;
    public static string $contract_usdt_address = '';
    public static int $chain_id = 0;

    /**
     * @desc 初始化配置
     * @return void
     */
    public static function initConifg() {
        if(self::$contract_usdt_address === ''){
            self::$contract_usdt_address = '0x1E4a5963aBFD975d8c9021ce480b42188849D41d';
        }
        if(self::$chain_id == 0){
            self::$chain_id = 196;
        }
        if(self::$base_url == ''){
            self::$base_url = 'https://rpc.xlayer.tech';
        }
    }

    /**
     * @desc 初始化链接
     * @return Client
     */
    public static function initClient(): Client {
        self::initConifg();
        if (is_null(self::$xlayer_client)) {
            self::$xlayer_client = new Client([
                'verify' => false,
                'base_uri' => self::$base_url,
                'timeout' => 10, // 请求超时时间
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        return self::$xlayer_client;
    }
    /**
     * @desc 获取 nonce (转换为 16 进制)
     * @param string $address
     * @return false|string hex
     */
    public static function getNonce(string $address):false|string{
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

    public static function getGasPrice(int $id=1){
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_gasPrice',
                    'params' => [],
                    'id' => $id,
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

    public static function getGasLimit(int $id=1){
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_estimateGas',
                    'params' => [
                        [
                            'from'  => '0xd248cF748621B705B596A444D9f0c55BF14D92B5', // 发起者地址
                            'to'    => '0xe17c91ba8d6fb78acd1a7a0607fd32f70e5983d8', // 接收者地址
                            'value' => '0x0', // 十六进制 Wei，可用 gmp_strval(gmp_init($wei), 16) 转换
                            'data'  => '0x' // 合约调用数据，如果只是普通转账，可为 "0x"
                        ]
                    ],
                    'id' => $id,
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
            var_dump($data);
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
     * @desc 最新区块号
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
     * @desc 获取区块号详情
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
    /**
     * @desc 交易结果
     * @param string $tx_hash
     * @param int $id
     * @return array
     */
    public static function getTransactionReceipt(string $tx_hash, int $id = 1):array {
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
                'msg' => 'getTransactionReceipt is success',
            ];
        } catch (RequestException $e) {
            return [
                'code' => 400,
                'msg' => 'getTransactionReceipt RequestException error, tx_hash is ' . $tx_hash . ' error:' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'code' => 400,
                'msg' => 'getTransactionReceipt GuzzleException error, tx_hash is ' . $tx_hash . ' error:' . $e->getMessage(),
            ];
        }
    }

    /**
     * @desc 交易详情
     * @param string $tx_hash
     * @param int $id
     * @return array
     */
    public static function getTransactionByHash(string $tx_hash,int $id=1): array {
        $client = self::initClient();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_getTransactionByHash',
                    'params' => [$tx_hash],
                    'id' => $id,
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
                    'msg' => 'getTransactionByHash-tx_hash:is null',
                ];
            }
            if (is_null($data['result']['blockNumber'])) {
                return [
                    'code' => 400,
                    'msg' => 'getTransactionByHash-tx_hash: is pending',
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
}