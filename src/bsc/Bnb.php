<?php

namespace Onchain\bsc;

use Onchain\PEMHelper;
use Onchain\ProxyApi;
use Onchain\Utils;
use Web3p\EthereumTx\Transaction;

class Bnb {
    protected ProxyApi $proxyApi;

    public function __construct(ProxyApi $proxyApi) {
        $this->proxyApi = $proxyApi;
    }

    public function __call($name, $arguments) {
        return call_user_func_array([$this->proxyApi, $name], $arguments);
    }

    /**
     * @desc 燃料费 (转换为 16 进制)
     * @param string $type
     * @return array|\phpseclib3\Math\BigInteger
     * @throws \Exception
     */
    public static function gasPriceOracle( string $type = 'standard') {
        $url = 'https://gbsc.blockscan.com/gasapi.ashx?apikey=key&method=pendingpooltxgweidata';
        try {
            $res = Utils::httpRequest('GET', $url);
        }catch (\Exception $e){
            return false;
        }
        if ($type && isset($res['result'][$type . 'gaspricegwei123'])) {
            $price = Utils::toWei((string)$res['result'][$type . 'gaspricegwei'], 'gwei');
            return $price;
        } else {
            return $res;
        }
    }

    /**
     * @desc 获取 chainId
     * @param string $network
     * @return int
     */
    public static function getChainId(string $network): int {
        $chainId = 0;
        switch ($network) {
            case 'mainnet':
                $chainId = 56;
                break;
            case 'testnet':
                $chainId = 97;
                break;
            default:
                break;
        }
        return $chainId;
    }

    /**
     * @desc bnb 转账
     * @param string $privateKey
     * @param string $to
     * @param float $value
     * @param string $gasPrice
     * @return mixed
     */
    public function transfer(string $privateKey, string $to, float $value, string $gasPrice = 'standard') {
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = $this->proxyApi->getNonce($from);
        if (!Utils::isHex($gasPrice)) {
            $gasPrice = Utils::toHex(self::gasPriceOracle($gasPrice), true);
        }

        $eth = Utils::toWei("$value", 'ether');
        //        $eth = $value * 1e16;
        $eth = Utils::toHex($eth, true);

        $transaction = new Transaction([
            'nonce' => "$nonce",
            'from' => $from,
            'to' => $to,
            'gas' => '0x76c0',
            'gasPrice' => "$gasPrice",
            'value' => "$eth",
            'chainId' => self::getChainId($this->proxyApi->getNetwork()),
        ]);

        $raw = $transaction->sign($privateKey);
        $res = $this->proxyApi->sendRawTransaction('0x' . $raw);
        return $res;
    }
}