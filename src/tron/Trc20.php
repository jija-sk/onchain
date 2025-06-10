<?php

namespace Onchain\tron;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Onchain\Base58Check;
use Onchain\Utils;

class Trc20 {
    private static string $base_url = '';

    public static $tron_client = null;
    public static string $contract_usdt_address = '';
    public static string $contract_usdt_address_hex = '';
    public static int $chain_id = 0;
    public static function initConifg() {
        if(self::$contract_usdt_address === ''){
            self::$contract_usdt_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
            self::$contract_usdt_address_hex = '41a614f803b6fd780986a42c78ec9c7f77e6ded13c';
        }
        if(self::$chain_id == 0){
            self::$chain_id = 196;
        }
        if(self::$base_url == ''){
            self::$base_url = 'https://api.trongrid.io';
        }
    }
    public static function initClient():Client{
        self::initConifg();
        if(is_null(self::$tron_client)){
            self::$tron_client = new Client([
                'verify' => false,
                'base_uri' => self::$base_url,
                'timeout' => 10, // 请求超时时间
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        return self::$tron_client;
    }


    public static function getNewBlockNumber():false|int{
        self::initClient();
        try {
            $response = self::$tron_client->request('POST', '/wallet/getnowblock', [
                'json' => [
                    'visible' => false
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            if(isset($data['block_header']['raw_data']['number'])){
                if(is_int($data['block_header']['raw_data']['number'])){
                    return $data['block_header']['raw_data']['number'];
                }else{
                    return false;
                }
            }
            return false;
        }catch (RequestException|GuzzleException $e){
            return false;
        }
    }

    public static function getBlockInfoByNumber(int|string $number){
        self::initClient();
        try {
            $response = self::$tron_client->request('POST', '/wallet/getblockbynum', [
                'json' => [
                    'num' => $number
                ],
            ]);
            $block = json_decode($response->getBody(), true);
            $result = [];
            foreach ($block['transactions'] ?? [] as $tx) {
                $contractType = $tx['raw_data']['contract'][0]['type'];
                if ($contractType === 'TriggerSmartContract') {
                    $value = $tx['raw_data']['contract'][0]['parameter']['value'];
                    if($value['contract_address'] === self::$contract_usdt_address_hex){
                        $raw_parameter_value_data = $value['data'];
                        $methodId = substr($raw_parameter_value_data,0,8);
                        if ($methodId === 'a9059cbb') {
//                            $result[] = $tx;
                            $to = '41' . substr($raw_parameter_value_data, 32, 40);
                            $amountHex = substr($raw_parameter_value_data, 72,64);
                            $result[] = [
                                'hash'=>$tx['txID'],
                                'from_address'=> Base58Check::TronHexToBase58($value['owner_address']),
                                'to_address'=> Base58Check::TronHexToBase58($to),
                                'amount'=> hexdec($amountHex) / 1000000,
                            ];
                        }
                    }
                }
            }
            return $result;
        }catch (RequestException|GuzzleException $e){
            return false;
        }
    }
}