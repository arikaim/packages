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
        $tokens = \explode('~',$this->getName());
        $componentPath = \str_replace('.',DIRECTORY_SEPARATOR,$tokens[1]);

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
