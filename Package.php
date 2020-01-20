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

use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Packages\Interfaces\PackageInterface;
use Arikaim\Core\Packages\Interfaces\PackageRegistryInterface;
use Arikaim\Core\Collection\Interfaces\CollectionInterface;
use Arikaim\Core\Interfaces\CacheInterface;

/**
 * Package base class
*/
class Package implements PackageInterface
{
    /**
     * Package properties
     *
     * @var CollectionInterface
     */
    protected $properties;

    /**
     * Cache
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Package Registry Interface
     *
     * @var PackageRegistryInterface
     */
    protected $packageRegistry;

    /**
     * Package root path
     *
     * @var string
     */
    protected $path;

    /**
     * Constructor
     *
     * @param CollectionInterface $properties
     */
    public function __construct($path, CollectionInterface $properties, CacheInterface $cache, PackageRegistryInterface $packageRegistry = null) 
    {
        $this->path = $path;
        $properties['version'] = Utils::formatVersion($properties->get('version','1.0.0'));
        $this->properties = $properties;
        $this->cache = $cache;
        $this->packageRegistry = $packageRegistry;
    }

    /**
     * Get package root path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get Package version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->properties->get('version','1.0.0');
    }

    /**
     * Set package as primary
     *
     * @return boolean
     */
    public function setPrimary()
    {
        return true;
    }

    /**
     * Get package type
     *
     * @return string
     */
    public function getType()
    {
        return $this->properties->get('package-type',null);
    }

    /**
     * Return package name
     *
     * @return string
     */
    public function getName()
    {
        return $this->properties->get('name');
    }

    /**
     * Return package properties
     *
     * @param boolean $full
     * @return CollectionInterface
     */
    public function getProperties($full = false)
    {
        return $this->properties;
    }

    /**
     * Get package property
     *
     * @param srting $name
     * @param mixed $default
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        return $this->properties->get($name,$default);
    }

    /**
     * Validate package properties
     *
     * @return bool
     */
    public function validate()
    {
        return true;
    }

    /**
     * Install package.
     *
     * @param boolean|null $primary Primary package replaces routes or other params
     * @return bool
     */
    public function install($primary = null)   
    {        
        return false;
    }

    /**
     * UnInstall package
     *
     * @return bool
     */
    public function unInstall() 
    {      
        return false;  
    }

    /**
     * Enable package
     *
     * @return bool
     */
    public function enable()    
    {
        return false;
    }

    /**
     * Disable package
     *
     * @return bool
     */
    public function disable()   
    {        
        return false;
    }  
}
