<?php
/**
 * Enter description here...
 *
 * @category Stagem
 * @package Stagem_ZfcAction
 * @author Serhii Popov <popow.serhii@gmail.com>
 */
namespace Stagem\ZfcAction;

class ConfigProvider
{
    public function __invoke()
    {
        $config = include __DIR__ . '/../config/module.config.php';
        unset($config['router']);

        return $config;
    }
}