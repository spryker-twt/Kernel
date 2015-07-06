<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Unit\SprykerEngine\Zed\Kernel\Communication\Fixtures\AbstractPlugin;

use SprykerEngine\Zed\Kernel\Communication\Factory as CommunicationFactory;

class Factory extends CommunicationFactory
{

    protected $classNamePattern =
        '\\Unit\\{{namespace}}\\Zed\\{{bundle}}{{store}}\\Communication\\Fixtures\\AbstractPlugin\\'
    ;

}