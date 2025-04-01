<?php

namespace Onchain\bsc;

use Onchain\ProxyApi;
use Onchain\Utils;
use Exception;

class BscScanApi implements ProxyApi {
    protected $apiKey;
    protected $network;

    public function __construct(string $apiKey, $network = 'mainnet') {
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

    /**
     * @desc 交易状态 #TODO
     * @param string $txHash
     * @return bool|null
     */
    public function receiptStatus(string $txHash): ?bool {
        $res = $this->send('eth_getTransactionByHash', ['txHash' => $txHash]);
        if(!$res){
            return false;
        }
        if(!$res['blockNumber']){
            return null;
        }
        $params['module'] = 'transaction';
        $params['txhash'] = $txHash;
        $res =  $this->send('gettxreceiptstatus', $params);
        return $res['status'] == '1';
    }

    /**
     * @desc 转账结果查询 #TODO
     * @param string $txHash
     * @return array|false
     */
    public function getTransactionReceipt(string $txHash) {
        $res = $this->send('eth_getTransactionReceipt', ['txhash' => $txHash]);
        return $res;
    }

    /**
     * @desc 交易详情
     * @param string $txHash
     * @return array|false
     */
    public function getTransactionByHash(string $txHash) {
        return $this->send('eth_getTransactionByHash', ['txHash' => $txHash]);
    }

    /**
     * @desc 广播交易
     * @param $raw
     * @return array|false
     */
    public function sendRawTransaction($raw) {
        return $this->send('eth_sendRawTransaction', ['hex' => $raw]);
    }

    /**
     * @desc 获取 nonce
     * @param string $address
     * @return array|false
     */
    public function getNonce(string $address) {
        return $this->send('eth_getTransactionCount', ['address' => $address]);
    }

    /**
     * @desc eth_call
     * @param $params
     * @return array|false
     */
    public function ethCall($params) {
        return $this->send('eth_call', ['params' => $params, 'latest']);
    }

    /**
     * @desc  获取最新的交易块号
     * @return float|int
     */
    public function blockNumber() {
        return hexdec($this->send('eth_blockNumber'));
    }

    public function getBlockByNumber(int $blockNumber) {
        // TODO: Implement getBlockByNumber() method.
    }
}