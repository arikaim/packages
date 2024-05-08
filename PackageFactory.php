<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     Packages
*/
namespace Arikaim\Core\Packages;

use Arikaim\Core\Interfaces\Packages\PackageFactoryInterface;
use Arikaim\Core\Packages\PackageManagerFactory;

/**
 * Package managers factory class
*/
class PackageFactory implements PackageFactoryInterface
{
    /**
     * Create package 
     *
     * @param string $packageType
     * @param string $name
     * @return PackageInterface
    */
    public function createPackage(string $packageType, string $name)
    {       
        $class = PackageManagerFactory::getPackageClass($packageType);
      
        $package =  new $class(
            PackageManagerFactory::getPackagePath($packageType),
            $name,
            PackageManagerFactory::createPackageRegistry($packageType),
            $packageType);

        $package->loadProperties();

        return $package;
    }
}
