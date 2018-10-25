<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2018 Stagem Team
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Stagem
 * @package Stagem_ZfcAction
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Stagem\ZfcAction\Page\Plugin;

use Stagem\ZfcAction\Page\ConnectivePage;
use Zend\Mvc\Controller\Plugin\Forward;

class GotoPlugin extends Forward
{
    /**
     * @param ConnectivePage $connectivePage
     */
    public function __construct(ConnectivePage $connectivePage)
    {
        $this->controllers = $connectivePage;
    }

    public function dispatch($name, array $params = null)
    {
        $this->controllers->setRouteParams($params);

        return parent::dispatch($name, $params);
    }
}