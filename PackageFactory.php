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

use Arikaim\Core\Interfaces\Packages\PackageFactoryInterface;
use Arikaim\Core\Interfaces\CacheInterface;
use Arikaim\Core\Packages\PackageManager;
use Arikaim\Core\Packages\PackageManagerFactory;

/**
 * Package managers factory class
*/
class PackageFactory implements PackageFactoryInterface
{
    /**
     * Cache
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;  
    }

    /**
     * Create package 
     *
     * @param string $name
     * @return PackageInterface
    */
    public function createPackage($packageType, $name)
    {      
        $path = PackageManagerFactory::getPackagePath($packageType);
        $propertes = PackageManager::loadPackageProperties($name,$path);
        $class = PackageManagerFactory::getPackageClass($packageType);
        $registry = PackageManagerFactory::createPackageRegistry($packageType);

        return new $class($path,$propertes,$this->cache,$registry);
    }
}
