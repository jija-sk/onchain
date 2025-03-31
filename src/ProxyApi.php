<?php

namespace Onchain;

interface ProxyApi {
    public function getNetwork(): string;

    public function send(string $method, array $params = []);

    public function gasPrice();

    public function Balance(string $address);

    public function receiptStatus(string $txHash): ?bool;

    public function getTransactionReceipt(string $txHash);

    public function getTransactionByHash(string $txHash);

    public function sendRawTransaction($raw);

    public function getNonce(string $address);

    public function ethCall($params);

    public function blockNumber();

    public function getBlockByNumber(int $blockNumber);
}