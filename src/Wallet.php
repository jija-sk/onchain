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
     * @desc 生成秘钥账户(只支持兼容 evm)
     * @return array
     */
    public static function create_account_with_private(): array {
        $privateKey = PEMHelper::generateNewPrivateKey();
        $address = PEMHelper::privateKeyToAddress($privateKey);
        if($address ===''){
            return [];
        }
        return [
            "address" => $address,
            "private_key" => $privateKey,
        ];
    }

    /**
     * @desc 生成秘钥账户带助记词(只支持兼容 evm)
     * @param string $passphrase
     * @param string $path
     * @return false|array
     */
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

    public static function recover_account_from_mem(string $mnemonic, string $passphrase = '', string $path = self::DEFAULT_PATH): false|array {
        try {

            $mnemonicArr = preg_split('/\s+/', trim($mnemonic));
            var_dump($mnemonicArr);
            // 1. 验证助记词合法性
            $mnemonicObj = BIP39::fromMnemonic($mnemonicArr, English::getInstance());

            // 2. 生成 seed
            $seed = $mnemonicObj->generateSeed($passphrase);

            // 3. 从 seed 派生 HD 钱包 (BIP44)
            $HDKey = BIP44::fromMasterSeed(bin2hex($seed))->derive($path);

            // 4. 取出私钥
            $privateKey = $HDKey->privateKey;

            // 5. 生成地址（依赖你的 PEMHelper 工具）
            $address = PEMHelper::privateKeyToAddress($privateKey);

            return [
                "address" => $address,
                "private_key" => $privateKey,
            ];
        } catch (Bip39MnemonicException|Bip39EntropyException|Exception $e) {
            return false;
        }
    }
}