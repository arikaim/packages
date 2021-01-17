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
 * UI Library Package class
*/
class LibraryPackage extends Package implements PackageInterface
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

    /**
     * Return true if library is framework (DEPRECATED)
     *
     * @return boolean
     */
    public function isFramework()
    {       
        return $this->properties->get('framework',false);
    }

    /**
     * Get theme file (DEPRECATED)
     *
     * @param string $theme  
     * @return string
     */
    public function getThemeFile($theme): string
    {
        return $this->properties->getByPath('themes/' . $theme . '/file','');
    }

    /**
     * Disable library
     *
     * @return bool
     */
    public function disable(): bool
    {
        $this->properties->set('disabled',true);      

        return true;
    } 

    /**
     * Enable library
     *
     * @return void
     */
    public function enable(): bool
    {
        $this->properties->set('disabled',false);   

        return true; 
    } 

    /**
     * Set library status (enabled, disbled)
     *
     * @param bool $status
     * @return void
     */
    public function setStatus(bool $status): void
    {
        $this->properties->set('disabled',!$status);      
    }
}
