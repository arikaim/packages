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

use Arikaim\Core\Interfaces\Packages\PackageManagerInterface;
use Arikaim\Core\Interfaces\StorageInterface;
use Arikaim\Core\Interfaces\HttpClientInterface;
use Arikaim\Core\Interfaces\CacheInterface;
use Arikaim\Core\Collection\Collection;
use Arikaim\Core\Packages\Composer;
use Arikaim\Core\Packages\Interfaces\PackageRegistryInterface;
use Arikaim\Core\Packages\Repository\ArikaimRepository;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Utils\ZipFile;
use Closure;
use Exception;

/**
 * Package managers base class
*/
class PackageManager implements PackageManagerInterface
{
    /**
     *  Package type
     */
    const EXTENSION_PACKAGE          = 'extension';
    const TEMPLATE_PACKAGE           = 'template';
    const MODULE_PACKAGE             = 'module';
    const LIBRARY_PACKAGE            = 'library';
    const COMPOSER_PACKAGE           = 'composer';
    const COMPONENTS_LIBRARY_PACKAGE = 'components';
    const SERVICE_PACKAGE            = 'service';
    const HTML_COMPONENT_PACKAGE     = 'component';

    /**
     *  Repository type
    */
    const ARIKAIM_REPOSITORY        = 'arikaim';
    const COMPOSER_REPOSITORY       = 'composer';

    /**
     * Package type
     *
     * @var string
     */
    protected $packageType;

    /**
     * Path to packages
     *
     * @var string
     */
    protected $path;
    
    /**
     * Cache
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Local Storage
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    protected $httpClient;
    
    /**
     * Package Registry
     *
     * @var PackageRegistryInterface
     */
    protected $packageRegistry;

    /**
     * Package class
     *
     * @var string
     */
    protected $packageClass;

    /**
     * Constructor
     *
     * @param string $packagePath
     * @param string $packageType
     * @param string $packageClass
     * @param CacheInterface $cache
     * @param StorageInterface $storage
     * @param HttpClientInterface $httpClient
     * @param PackageRegistryInterface|null $packageRegistry
     */
    public function __construct(
        string $packagePath, 
        string $packageType, 
        string $packageClass, 
        CacheInterface $cache, 
        StorageInterface $storage, 
        HttpClientInterface $httpClient, 
        ?PackageRegistryInterface $packageRegistry = null
    )
    {
        $this->path = $packagePath;
        $this->packageType = $packageType;
        $this->cache = $cache;
        $this->storage = $storage;
        $this->httpClient = $httpClient;
        $this->packageClass = $packageClass;      
        $this->packageRegistry = $packageRegistry;
    }

    /**
     * Get packages registry
     *
     * @return PackageRegistryInterface
     */
    public function getPackgesRegistry()
    {
        return $this->packageRegistry;
    } 

    /**
     * Create package 
     *
     * @param string $name
     * @param bool $loadProperties
     * @return PackageInterface|null
    */
    public function createPackage(string $name, bool $loadProperties = true): ?object
    {      
        $propertes = ($loadProperties == true) ? 
            Self::loadPackageProperties($name,$this->path,$this->packageType) :
            new Collection();
        
        if (empty($propertes->get('name')) == true) {
            $propertes->set('name',$name);
        }

        return new ($this->packageClass)($this->path,$propertes,$this->packageRegistry,$this->packageType);
    }

    /**
     * Return tru if package exists
     *
     * @param string $name
     * @return boolean
     */
    public function hasPackage(string $name): bool
    {
        $fileName = $this->path . $name . DIRECTORY_SEPARATOR . 'arikaim-package.json';
        
        return File::exists($fileName);
    }

    /**
     * Get package repository
     *
     * @param string $packageName
     * @return RepositoryInterface|null
     */
    public function getRepository(string $packageName): ?object
    {
        return new ArikaimRepository(
            $packageName,
            $this->packageType,
            $this->storage,
            $this->httpClient);          
    }

    /**
     * Get packages list
     *
     * @param boolean $cached
     * @param mixed $filter
     * @return mixed
     */
    public function getPackages(bool $cached = false, $filter = null)
    {
        $result = ($cached == true) ? $this->cache->fetch($this->packageType . '.list') : false;
        if ($result === false) {
            $result = $this->scan($filter);
            $this->cache->save($this->packageType . '.list',$result);
        } 
        
        return $result;
    }

    /**
     * Return packages path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Load package properties file 
     *
     * @param string $name
     * @param string|null $path
     * @param string|null $packageType
     * @throws Exception
     * @return Collection
     */
    public static function loadPackageProperties(string $name, ?string $path = null, ?string $packageType = null) 
    {         
        if ($path === null) {
            $path = PackageManagerFactory::getPackagePath($packageType);
        }

        if ($packageType == Self::COMPOSER_PACKAGE) {
            $data = Composer::getInstalledPackageInfo($name);
            $data = (\is_array($data) == true) ? $data : [];
            $data['repository-type'] = Self::COMPOSER_REPOSITORY;
        } else {
            $fileName = $path . $name . DIRECTORY_SEPARATOR . 'arikaim-package.json';
            $data = File::readJsonFile($fileName);
            if (\is_array($data) == false) {
                throw new Exception('Not valid package description file for package: ' . $name, 1);             
            }           
        }
       
        $properties = new Collection($data);    
        if (empty($properties->name) == true) {
            $properties->set('name',$name);
        }           

        return $properties;
    }

    /**
     * Explore packages root directory
     *
     * @param array|null $filter
     * @return array
     */
    protected function scan(?array $filter = null): array
    {
        if ($this->packageType == Self::COMPOSER_PACKAGE) {
            $packages = Composer::readInstalledPackages();
            $packages = $packages['packages'] ?? $packages;
            
            return (\is_array($packages) == true) ? $packages : []; 
        }

        $items = [];
        foreach (new \DirectoryIterator($this->path) as $file) {
            if ($file->isDot() == true || $file->isDir() == false) {
                continue;
            }
            $name = $file->getFilename();
            if (\is_array($filter) == true) {
                $package = $this->createPackage($name);
                if ($package != null) {
                    $properties = $package->getProperties();                
                    foreach ($filter as $key => $value) {                
                        if ($properties->get($key) == $value) {
                            $items[] = $name;   
                        }
                    }
                }
            } else {
                $items[] = $name;        
            }
        }  
        
        return $items;
    }

    /**
     * Get package properties
     *
     * @param string $name
     * @param boolean $full
     * @return Collection|null
     */
    public function getPackageProperties(string $name, bool $full = false)
    {
        $package = $this->createPackage($name);

        return (empty($package) == false) ? $package->getProperties($full) : null;
    }

    /**
     * Find package
     *
     * @param string $param
     * @param mixed $value
     * @return PackageInterface|false
     */
    public function findPackage(string $param, $value)
    {
        $packages = $this->getPackages();
        foreach ($packages as $name) {
            $properties = Self::loadPackageProperties($name,$this->path,$this->packageType);
            if ($properties->get($param) == $value) {
                return $this->createPackage($name);
            }
        }

        return false;
    }

    /**
     * Sort packages by 'install-order' property
     *
     * @param array $packages
     * @return array
     */
    public function sortPackages(array $packages): array
    {
        $result = [];
        foreach ($packages as $name) { 
            $package = $this->createPackage($name);
            $type = $package->getProperty('type',0);
            $installOrder = ($type == 'system') ? 0 : $package->getProperty('install-order',1000);
            $result[] = [ 
                'name' => $name, 
                'order' => (int)$installOrder
            ];           
        }
       
        usort($result, function($a,$b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;            
        });

        return \array_column($result,'name');
    } 

    /**
     * Install all packages
     *
     * @param Closure|null $onProgress
     * @param Closure|null $onProgressError
     * @param bool $skipErrors
     * @return bool
     */
    public function installAllPackages(?Closure $onProgress = null, ?Closure $onProgressError = null, bool $skipErrors = true): bool
    {
        $this->cache->clear();
        $errors = 0;
        $packages = $this->getPackages();
        $packages = $this->sortPackages($packages);
        // 
        foreach ($packages as $name) {       
            $result = $this->installPackage($name);   
            if (($result == true) || ($skipErrors == true)) {               
                if (\is_callable($onProgress) == true) {
                    $onProgress($name);
                }
            } else {
                if (\is_callable($onProgressError) == true) {
                    $onProgressError($name);
                }
                $errors += 1;
            }         
        }

        return ($errors == 0);
    }

    /**
     * Run post install actions on all packages
     *
     * @return bool
     */
    public function postInstallAllPackages(): bool
    {
        $this->cache->clear();
        $errors = 0;

        $packages = $this->getPackages();
        foreach ($packages as $name) {           
            $errors += ($this->postInstallPackage($name) == false) ? 1 : 0;
        }

        return ($errors == 0);
    }

    /**
     * Install package
     *
     * @param string $name
     * @return mixed
     */
    public function installPackage(string $name)
    {
        $package = $this->createPackage($name);

        return (empty($package) == false) ? $package->install() : false;
    }

    /**
     * Run post install actions on package
     *
     * @param string $name
     * @return mixed
     */
    public function postInstallPackage(string $name)
    {
        $package = $this->createPackage($name);

        return (empty($package) == false) ? $package->postInstall() : false;
    }

    /**
     * Uninstall package
     *
     * @param string $name
     * @return bool
     */
    public function unInstallPackage(string $name): bool
    {
        $package = $this->createPackage($name);

        return (empty($package) == false) ? $package->unInstall() : false;
    }

    /**
     * Enable package
     *
     * @param string $name
     * @return bool
     */
    public function enablePackage(string $name): bool
    {
        $package = $this->createPackage($name);

        return (empty($package) == false) ? $package->enable() : false;
    }

    /**
     * Disable package
     *
     * @param string $name
     * @return bool
     */
    public function disablePackage(string $name): bool
    {
        $package = $this->createPackage($name);

        return (empty($package) == false) ? $package->disable() : false;
    }

    /**
     * Get installed packages.
     *
     * @param integer|null $status
     * @param string|integer $type
     * @return array
     */
    public function getInstalled($status = null, $type = null): array
    {
        return [];
    }

    /**
     * Create zip arhive with package files and save to storage/backup/
     *
     * @param string $name
     * @return boolean
     */
    public function createBackup(string $name): bool
    {
        if (File::isWritable(Path::STORAGE_BACKUP_PATH) == false) {
            File::setWritable(Path::STORAGE_BACKUP_PATH);
        }

        if ($this->hasPackage($name) == false) {
            // package not exists
            return false;
        }
        
        $package = $this->createPackage($name);
        if (empty($package) == true) {
            return false;
        }

        $fileName = $package->getName() . '-' . $this->packageType . '-' . $package->getVersion() . '.zip';
      
       
        if (File::exists($this->getPath()) == false) {
            // source path not exist
            return false;
        }
        $zipFile = Path::STORAGE_BACKUP_PATH . $fileName;
        if (File::exists($zipFile) == true) {
            File::delete($zipFile);
        }
        
        return ZipFile::create($this->getPath(),$zipFile,['.git']);       
    }
}
