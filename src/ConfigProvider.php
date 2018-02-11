<?php
/**
 * Enter description here...
 *
 * @category Stagem
 * @package Stagem_<package>
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 04.12.2016 18:47
 */
namespace Stagem\ZfcAction;

class ConfigProvider
{
    public function __invoke()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}