<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     Packages
*/
namespace Arikaim\Core\Packages\Type;

use Arikaim\Core\Packages\Type\Package;
use Arikaim\Core\Packages\Interfaces\PackageInterface;
use Arikaim\Core\Packages\Traits\ViewComponents;

/**
 * UI components library package class
*/
class ComponentsLibraryPackage extends Package implements PackageInterface
{ 
    use 
        ViewComponents;

    /**
     * Get library params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->properties->get('params',[]);
    }

    /**
     * Get extension package properties
     *
     * @param boolean $full
     * @return Collection
     */
    public function getProperties(bool $full = false)
    {
        if ($full == true) {          
            $this->properties['components'] = $this->getComponentsRecursive($this->getPath());
        }

        return $this->properties; 
    }
}
