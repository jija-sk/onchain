<?php

namespace Onchain;

use BIP\BIP44;
use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\Exception\Bip39EntropyException;
use FurqanSiddiqui\BIP39\Exception\Bip39MnemonicException;
use FurqanSiddiqui\BIP39\Language\English;
use Random\RandomException;
use Exception;

class Wallet {
    const DEFAULT_PATH = "m/44'/60'/0'/0/0";

    /**
     * @desc 生成秘钥账户
     * @return array
     */
    public static function create_account_with_private(): array {
        $privateKey = PEMHelper::generateNewPrivateKey();
        $address = PEMHelper::privateKeyToAddress($privateKey);
        return [
            "address" => $address,
            "private_key" => $privateKey,
        ];
    }

    public static function create_account_with_mem(string $passphrase = '', string $path = self::DEFAULT_PATH): false|array {
        try {
            $mnemonic = BIP39::fromRandom(English::getInstance(), 12);
            $seed = $mnemonic->generateSeed($passphrase);
            $HDKey = BIP44::fromMasterSeed(bin2hex($seed))->derive($path);
            $privateKey = $HDKey->privateKey;
            $address = PEMHelper::privateKeyToAddress($privateKey);
        } catch (Bip39EntropyException|Bip39MnemonicException|RandomException|Exception $e) {
            return false;
        }
        return [
            "address" => $address,
            "private_key" => $privateKey,
            "mem" => implode(' ', $mnemonic->words),
        ];
    }
}