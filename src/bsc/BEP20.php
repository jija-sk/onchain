<?php

namespace Onchain\bsc;

use Onchain\Formatter;
use Onchain\ProxyApi;
use Onchain\Utils;

class BEP20 extends Bnb {
    protected $contractAddress;
    protected $decimals;

    public function __construct(ProxyApi $proxyApi, array $config) {
        parent::__construct($proxyApi);

        $this->contractAddress = $config['contract_address'];
        $this->decimals = $config['decimals'];
    }

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
}