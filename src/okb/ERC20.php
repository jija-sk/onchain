<?php
namespace Onchain\okb;

use GuzzleHttp\Exception\GuzzleException;
use kornrunner\Ethereum\Transaction;
use kornrunner\Keccak;
use Onchain\Formatter;
use Onchain\PEMHelper;
use Onchain\Utils;

class ERC20 extends XLayer {
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
                            'to' => XLayer::$contract_usdt_address,
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
            return Utils::toDisplayAmount($data['result'], 6);
        } catch (GuzzleException|Exception $e) {
            return '';
        }
    }

    public static function transfer(string $privateKey, string $to, float $value, string $gasPrice = 'standard'): array {
        self::initConifg();
        $privateKey = strtolower($privateKey);
        if (str_starts_with($privateKey, '0x')) {
            $privateKey = substr($privateKey, 2);
        }
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = XLayer::getNonce($from);
        if ($nonce === false) {
            return [
                'code' => 400,
                'msg' => 'getNonce failed',
            ];
        }
        $gasPrice = XLayer::getGasPrice();
        if($gasPrice === false){
            return [
                'code' => 400,
                'msg' =>'gasPrice failed',
            ];
        }
        $data = '0xa9059cbb' . Formatter::toAddressFormat($to) . Formatter::toIntegerFormat((string)$value);
        $transaction = new Transaction(
            $nonce,
            $gasPrice,
            '0xea60',
            XLayer::$contract_usdt_address,
            Utils::NONE,
            $data);
        $raw = $transaction->getRaw($privateKey, XLayer::$chain_id);
        $send_raw_result = XLayer::sendRaw('0x' . $raw);
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
}