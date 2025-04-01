<?php

namespace Onchain\bsc;

use Onchain\ProxyApi;
use Onchain\Utils;
use Exception;

class BscScanApi implements ProxyApi {
    protected $apiKey;
    protected $network;

    function __construct(string $apiKey, $network = 'mainnet') {
        $this->apiKey = $apiKey;
        $this->network = $network;
    }

    /**
     * @desc 发送请求
     * @param string $method
     * @param array $params
     * @return false|array
     */
    public function send(string $method, array $params = []):false|array{
        $defaultParams = [
            'module' => 'proxy',
            'tag' => 'latest',
        ];

        foreach ($defaultParams as $key => $val) {
            if (!isset($params[$key])) {
                $params[$key] = $val;
            }
        }

        $preApi = 'api';
        if ($this->network != 'mainnet') {
            $preApi .= '-' . $this->network;
        }

        $url = 'https://'.$preApi.'bscscan.com/api?action='.$method.'&apikey='.$this->apiKey;
        if ($params && count($params) > 0) {
            $strParams = http_build_query($params);
            $url .= "&{$strParams}";
        }

        try {
            $res = Utils::httpRequest('GET', $url);
        }catch (Exception $e) {
            return false;
        }
        if (isset($res['result'])) {
            return $res['result'];
        } else {
            return false;
        }
    }

    public function getNetwork(): string {
        return $this->network;
    }

    public function gasPrice() {
        return $this->send('eth_gasPrice');
    }

    public function BalanceBNB(string $address) {
        $params['module'] = 'account';
        $params['address'] = $address;

        $retDiv = Utils::fromWei($this->send('balance', $params), 'ether');
        if (is_array($retDiv)) {
            return Utils::divideDisplay($retDiv, 18);
        } else {
            return $retDiv;
        }
    }

    public function receiptStatus(string $txHash): ?bool {
        // TODO: Implement receiptStatus() method.
    }

    public function getTransactionReceipt(string $txHash) {
        // TODO: Implement getTransactionReceipt() method.
    }

    public function getTransactionByHash(string $txHash) {
        // TODO: Implement getTransactionByHash() method.
    }

    public function sendRawTransaction($raw) {
        // TODO: Implement sendRawTransaction() method.
    }

    public function getNonce(string $address) {
        // TODO: Implement getNonce() method.
    }

    public function ethCall($params) {
        // TODO: Implement ethCall() method.
    }

    public function blockNumber() {
        // TODO: Implement blockNumber() method.
    }

    public function getBlockByNumber(int $blockNumber) {
        // TODO: Implement getBlockByNumber() method.
    }
}