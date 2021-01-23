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
use Arikaim\Core\Packages\Interfaces\PackageRegistryInterface;
use Arikaim\Core\Packages\Repository\GitHubRepository;
use Arikaim\Core\Packages\Repository\ArikaimRepository;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Utils\ZipFile;

/**
 * Package managers base class
*/
class PackageManager implements PackageManagerInterface
{
    /**
     *  Package type
     */
    const EXTENSION_PACKAGE = 'extension';
    const TEMPLATE_PACKAGE  = 'template';
    const MODULE_PACKAGE    = 'module';
    const LIBRARY_PACKAGE   = 'library';
    
    /**
     *  Repository type
    */
    const GITHUB_REPOSITORY         = 'github';
    const GITHUB_PRIVATE_REPOSITORY = 'private-github';
    const ARIKAIM_REPOSITORY        = 'arikaim';
   
    /**
     * Cache save time
     *
     * @var integer
     */
    public static $cacheSaveTime = 4;

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

        Self::$cacheSaveTime = \defined('CACHE_SAVE_TIME') ? \constant('CACHE_SAVE_TIME') : Self::$cacheSaveTime;
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
     * @return PackageInterface|null
    */
    public function createPackage(string $name)
    {      
        $propertes = Self::loadPackageProperties($name,$this->path);
        if (empty($propertes->get('name')) == true) {
            $propertes->set('name',$name);
        }
        $class = $this->packageClass;
        $package = null;
        if (\class_exists($class) == true) {
            $package = new $class($this->path,$propertes,$this->packageRegistry);
        }

        return $package;
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
     * @param string|null $accessKey
     * @return RepositoryInterface|null
     */
    public function getRepository(string $packageName, ?string $accessKey = null)
    {
        $properties = Self::loadPackageProperties($packageName,$this->path);
        $repositoryUrl = $properties->get('repository',null);
       
        return (empty($repositoryUrl) == false) ? $this->createRepository($repositoryUrl,$accessKey) : null;
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
        $result = ($cached == true) ? $this->cache->fetch($this->packageType . '.list') : null;
        if (\is_array($result) == false) {
            $result = $this->scan($filter);
            $this->cache->save($this->packageType . '.list',$result,Self::$cacheSaveTime);
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
     * @param string $path
     * @return Collection
     */
    public static function loadPackageProperties(string $name, ?string $path) 
    {         
        $fileName = $path . $name . DIRECTORY_SEPARATOR . 'arikaim-package.json';
        $data = File::readJsonFile($fileName);
        $data = (\is_array($data) == true) ? $data : [];

        $properties = new Collection($data);    
        if (empty($properties->name) == true) {
            $properties->set('name',$name);
        }           

        return $properties;
    }

    /**
     * Explore packages root directory
     *
     * @param mixed $filter
     * @return array
     */
    protected function scan($filter = null): array
    {
        $items = [];
        foreach (new \DirectoryIterator($this->path) as $file) {
            if ($file->isDot() == true || $file->isDir() == false) {
                continue;
            }
            $name = $file->getFilename();
            if (\is_array($filter) == true) {
                $package = $this->createPackage($name);
                if (empty($package) == false) {
                    $properties = $package->getProperties();                
                    foreach ($filter as $key => $value) {                
                        if ($properties->get($key) == $value) {
                            \array_push($items,$name);   
                        }
                    }
                }
            } else {
                \array_push($items,$name);        
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
            $properties = Self::loadPackageProperties($name,$this->path);
            if ($properties->get($param) == $value) {
                return $this->createPackage($name);
            }
        }

        return false;
    }

    /**
     * Install all packages
     *
     * @param Closure|null $onProgress
     * @param Closure|null $onProgressError
     * @return bool
     */
    public function installAllPackages($onProgress = null, $onProgressError = null): bool
    {
        $this->cache->clear();
        $errors = 0;
        $packages = $this->getPackages();

        foreach ($packages as $name) {             
            $result = $this->installPackage($name);   
            if ($result == true) {               
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
     * @return bool
     */
    public function installPackage(string $name): bool
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
        $package = $this->createPackage($name);
        if (empty($package) == true) {
            return false;
        }

        $fileName = $package->getName() . '-' . $package->getVersion() . '.zip';
        $sourcePath = $this->getPath() . $name . DIRECTORY_SEPARATOR;
        $destinationPath = Path::STORAGE_BACKUP_PATH . $package->getType() . DIRECTORY_SEPARATOR;
        if (File::exists($destinationPath) == false) {
            File::makeDir($destinationPath);
            File::setWritable($destinationPath);
        }
        
        $zipFile = $destinationPath . $fileName;
        if (File::exists($zipFile) == true) {
            File::delete($zipFile);
        }
        
        return ZipFile::create($sourcePath,$zipFile,['.git']);
    }

    /**
     * Create repository driver
     *
     * @param string $repositoryUrl
     * @param string|null $accessKey
     * @param string|null $type
     * @return mixed
     */
    public function createRepository(string $repositoryUrl, ?string $accessKey = null, ?string $type = null)
    {
        if (Utils::isValidUrl($repositoryUrl) == false) {
            $repositoryUrl = Self::createRepositoryUrl($repositoryUrl,$type);
        }
        $type = $this->resolveRepositoryType($repositoryUrl);

        switch ($type) {
            case Self::GITHUB_REPOSITORY:           
                return new GitHubRepository(
                    $repositoryUrl,
                    Path::STORAGE_REPOSITORY_PATH,
                    $this->path,
                    $this->storage,
                    $this->httpClient,
                    $accessKey
                );
            case Self::ARIKAIM_REPOSITORY:
                return new ArikaimRepository(
                    $repositoryUrl,
                    Path::STORAGE_REPOSITORY_PATH,
                    $this->path,
                    $this->storage,
                    $this->httpClient,
                    $accessKey
                );
        }

        return null;
    }

    /**
     * Create repository url
     *
     * @param string $packageName
     * @param string $type
     * @return string|null
     */
    public static function createRepositoryUrl(string $packageName, string $type): ?string
    {
        switch ($type) {
            case Self::GITHUB_REPOSITORY:           
                return 'http://github.com/' . $packageName . '.git';
            case Self::ARIKAIM_REPOSITORY:
                return $packageName;
        }

        return null;
    }

    /**
     * Resolve package repository type
     *   
     * @param string $repositoryUrl   
     * @return string|null
     */
    protected function resolveRepositoryType(string $repositoryUrl): ?string
    {
        if (empty($repositoryUrl) == true) {
            return null;
        }
        if ($repositoryUrl == 'arikaim') {
            return Self::ARIKAIM_REPOSITORY;
        }
        $url = \parse_url($repositoryUrl);
        $host = $url['host'] ?? null;

        if (($host == 'github.com') || ($host == 'www.github.com')) {
            return Self::GITHUB_REPOSITORY;
        }

        return Self::ARIKAIM_REPOSITORY;       
    }   
}
