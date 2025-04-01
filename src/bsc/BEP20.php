<?php

namespace Onchain\bsc;

use Onchain\Formatter;
use Onchain\PEMHelper;
use Onchain\ProxyApi;
use Onchain\Utils;
use Web3p\EthereumTx\Transaction;

class BEP20 extends Bnb {
    protected $contractAddress;
    protected $decimals;

    public function __construct(ProxyApi $proxyApi, array $config) {
        parent::__construct($proxyApi);

        $this->contractAddress = $config['contract_address'];
        $this->decimals = $config['decimals'];
    }

    /**
     * @desc 获取 bep20 余额
     * @param string $address
     * @return string
     */
    public function balance(string $address):string{
        $params = [];
        $params['to'] = $this->contractAddress;

        $method = 'balanceOf(address)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatAddress = Formatter::toAddressFormat($address);

        $params['data'] = "0x{$formatMethod}{$formatAddress}";

        $balance = $this->proxyApi->ethCall($params);
        return Utils::toDisplayAmount($balance, $this->decimals);
    }

    /**
     * @desc 转账
     * @param string $privateKey
     * @param string $to
     * @param float $value
     * @param string $gasPrice
     * @return void
     */
    public function transfer(string $privateKey,string $to,float $value,string $gasPrice='standard') {
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = $this->proxyApi->getNonce($from);
        if(!Utils::isHex($gasPrice)){
            $gasPrice = Utils::toHex(self::gasPriceOracle($gasPrice), true);
        }
        $params = [
            'nonce' => "$nonce",
            'from' => $from,
            'to' => $this->contractAddress,
            'gas' => '0xea60',
            'gasPrice' => "$gasPrice",
            'value' => Utils::NONE,
            'chainId' => self::getChainId($this->proxyApi->getNetwork()),
        ];
        $val = Utils::toMinUnitByDecimals("$value", $this->decimals);
        $method = 'transfer(address,uint256)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatAddress = Formatter::toAddressFormat($to);
        $formatInteger = Formatter::toIntegerFormat($val);
        $params['data'] = "0x{$formatMethod}{$formatAddress}{$formatInteger}";
        $transaction = new Transaction($params);
        $raw = $transaction->sign($privateKey);
        $res = $this->proxyApi->sendRawTransaction('0x' . $raw);
        return $res;
    }
}