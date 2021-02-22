<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Packages;

use Arikaim\Core\Packages\Package;
use Arikaim\Core\Packages\Interfaces\PackageInterface;

/**
 * UI components library package class
*/
class ComponentsLibraryPackage extends Package implements PackageInterface
{ 
    /**
     * Get library params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->properties->get('params',[]);
    }
}
