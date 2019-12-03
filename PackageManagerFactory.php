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

use Arikaim\Core\Interfaces\StorageInterface;
use Arikaim\Core\Interfaces\HttpClientInterface;
use Arikaim\Core\Interfaces\Packages\PackageManagerInterface;
use Arikaim\Core\Interfaces\Packages\PackageManagerFactoryInterface;
use Arikaim\Core\Interfaces\CacheInterface;
use Arikaim\Core\Packages\PackageManager;
use Arikaim\Core\Utils\Path;

/**
 * Package managers factory class
*/
class PackageManagerFactory implements PackageManagerFactoryInterface
{
    /**
     * Custom package classes
     *
     * @var array
     */
    private static $packageClass = [
        PackageManager::EXTENSION_PACKAGE => CORE_NAMESPACE . "\\Packages\\ExtensionPackage",
        PackageManager::LIBRARY_PACKAGE   => CORE_NAMESPACE . "\\Packages\\LibraryPackage",
        PackageManager::TEMPLATE_PACKAGE  => CORE_NAMESPACE . "\\Packages\\TemplatePackage",
        PackageManager::MODULE_PACKAGE    => CORE_NAMESPACE . "\\Packages\\ModulePackage"
    ];

    /**
     * Packages path
     *
     * @var array
     */
    private static $packagePath = [
        PackageManager::EXTENSION_PACKAGE => Path::EXTENSIONS_PATH,
        PackageManager::LIBRARY_PACKAGE   => Path::LIBRARY_PATH,
        PackageManager::TEMPLATE_PACKAGE  => Path::TEMPLATES_PATH,
        PackageManager::MODULE_PACKAGE    => Path::MODULES_PATH
    ];

    /**
     * Package registry casses
     *
     * @var array
     */
    private static $packageRegistryClass = [
        PackageManager::EXTENSION_PACKAGE => CORE_NAMESPACE . "\\Models\\Extensions",
        PackageManager::LIBRARY_PACKAGE   => null,
        PackageManager::TEMPLATE_PACKAGE  => null,
        PackageManager::MODULE_PACKAGE    => CORE_NAMESPACE . "\\Models\\Modules",
    ];

    /**
     * Cache
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Local storage
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * Constructor
     */
    public function __construct(CacheInterface $cache, StorageInterface $storage, HttpClientInterface $httpClient)
    {
        $this->cache = $cache;
        $this->storage = $storage;
        $this->httpClient = $httpClient;
    }

    /**
     * Create package manager
     *
     * @param string $packageType
     * @return PackageManagerInterface|null
     */
    public function create($packageType)
    {
        $packageClass = Self::getPackageClass($packageType);
        $path = Self::getPackagePath($packageType);
        $registry = Self::createPackageRegistry($packageType);

        return new PackageManager($path,$packageType,$packageClass,$this->cache,$this->storage,$this->httpClient,$registry);
    }

    /**
     * Create package registry
     *
     * @param string $packageType
     * @return object|null
     */
    public static function createPackageRegistry($packageType)
    {
        $class = (isset(Self::$packageRegistryClass[$packageType]) == true) ? Self::$packageRegistryClass[$packageType] : null;
        
        return (empty($class) == false) ? new $class() : null;
    }

    /**
     * Get package path
     *
     * @param string $packageType
     * @return string|null
     */
    public static function getPackagePath($packageType)
    {
        return (isset(Self::$packagePath[$packageType]) == true) ? Self::$packagePath[$packageType] : null;
    }

    /**
     * Get package path
     *
     * @param string $packageType
     * @return string|null
     */
    public static function getPackageClass($packageType)
    {
        return (isset(Self::$packageClass[$packageType]) == true) ? Self::$packageClass[$packageType] : null;
    }

    /**
     * Set package path
     *
     * @param string $packageType
     * @param string $path
     * @return void
     */
    public static function setPackagePath($packageType, $path)
    {
        Self::$packagePath[$packageType] = $path;
    }

    /**
     * Set package class
     *
     * @param string $packageType
     * @param string $class
     * @return void
     */
    public static function setPackageClass($packageType, $class)
    {
        Self::$packageClass[$packageType] = $class;
    }

    /**
     * Set package registry class
     *
     * @param string $packageType
     * @param string $class
     * @return void
     */
    public static function setPackageRegistryClass($packageType, $class)
    {
        Self::$packageRegistryClass[$packageType] = $class;
    }
}
