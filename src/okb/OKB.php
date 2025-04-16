<?php
namespace Onchain\okb;

use GuzzleHttp\Exception\GuzzleException;
use kornrunner\Ethereum\Transaction;
use Onchain\PEMHelper;
use Onchain\Utils;

class OKB extends XLayer {
    public static function getBalance(string $address):string{
        $client = self::initClient();
        try {
            $response = $client->post('',[
                'json' => [
                    'jsonrpc' => '2.0',
                    'method'  => 'eth_getBalance',
                    'params'  => [$address, 'latest'],
                    'id'      => 1,
                ]
            ]);
            $data = json_decode($response->getBody(),true);
        }catch (GuzzleException $e){
            return '';
        }
        return Utils::toDisplayAmount($data['result'], 18);
    }

    public static function transfer(string $privateKey, string $to, float $value, string $gasPrice = 'standard'):array{
        self::initConifg();
        $privateKey = strtolower($privateKey);
        if(str_starts_with($privateKey, '0x')){
            $privateKey = substr($privateKey, 2);
        }
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = XLayer::getNonce($from);
        if($nonce === false){
            return [
                'code' => 400,
                'msg' =>'getNonce failed',
            ];
        }
        $gasPrice = XLayer::getGasPrice();
        if($gasPrice === false){
            return [
                'code' => 400,
                'msg' =>'gasPrice failed',
            ];
        }
        $amountWei = Utils::toWei((string)$value,'ether');
        $valueHex = Utils::toHex($amountWei, true);
        $transaction = new Transaction($nonce,$gasPrice,'0x5208',$to,$valueHex,'0x');
        $raw = $transaction->getRaw($privateKey,XLayer::$chain_id);
        $send_raw_result = XLayer::sendRaw('0x'.$raw);
        if(!isset($send_raw_result['code']) || $send_raw_result['code'] != 200){
            return [
                'code' => 400,
                'msg' =>'sendRaw failed : '.$send_raw_result['msg'],
            ];
        }
        return [
            'code' => 200,
            'data' => [
                'tx_hash' => $send_raw_result['data']['tx_hash'],
            ],
            'msg' =>'okb transfer success',
        ];
    }
}