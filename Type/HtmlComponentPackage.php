<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Packages\Type;

use Arikaim\Core\Packages\Type\Package;
use Arikaim\Core\Packages\Interfaces\PackageInterface;
use Arikaim\Core\Interfaces\ComponentInterface;

/**
 * Package base class
*/
class HtmlComponentPackage extends Package implements PackageInterface
{
    /**
     * Return true if package is installed
     *
     * @return boolean
     */
    public function isInstalled(): bool
    {
        return true;
    } 

    /**
     * Get package root path
     *
     * @return string
     */
    public function getPath(): string
    {
        $tokens = \explode(ComponentInterface::COMPONENTS_LIBRARY,$this->getName());
        $componentPath = \str_replace('.',DIRECTORY_SEPARATOR,$this->getName());

        return $this->path . $tokens[0] . DIRECTORY_SEPARATOR . $componentPath . DIRECTORY_SEPARATOR;
    }

    /**
     * Set package as primary
     *
     * @return boolean
     */
    public function setPrimary(): bool
    {
        return false;
    }

    /**
     * Validate package properties
     *
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * Install package.
     *
     * @param boolean|null $primary Primary package replaces routes or other params
     * @return mixed
     */
    public function install(?bool $primary = null)   
    {        
        return true;
    }
}
