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
use Arikaim\Core\Packages\Repository\GitHubPrivateRepository;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
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
    const BITBUCKET_REPOSITORY      = 'bitbucket';
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
     * Constructor
     *
     * @param string $path
     * @param CacheInterface $cache
     * @param object $storage
     * @param object $httpClient
     */
    public function __construct(
        $packagePath, 
        $packageType, 
        $packageClass, 
        CacheInterface $cache, 
        StorageInterface $storage, 
        HttpClientInterface $httpClient, 
        PackageRegistryInterface $packageRegistry = null
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
     * @return PackageInterface
    */
    public function createPackage($name)
    {      
        $propertes = Self::loadPackageProperties($name,$this->path);
        if (empty($propertes->get('name')) == true) {
            $propertes->set('name',$name);
        }
        $class = $this->packageClass;

        return new $class($this->path,$propertes,$this->cache,$this->packageRegistry);
    }

    /**
     * Get package repository
     *
     * @param string $packageName
     * @return RepositoryInterface
     */
    public function getRepository($packageName)
    {
        $properties = Self::loadPackageProperties($packageName,$this->path);
        $repositoryUrl = $properties->get('repository',null);
        $private = $properties->get('private-repository',false);
       
        return $this->createRepository($repositoryUrl,$private);
    }

    /**
     * Get packages list
     *
     * @param boolean $cached
     * @param mixed $filter
     * @return array
     */
    public function getPackages($cached = false, $filter = null)
    {
        $result = ($cached == true) ? $this->cache->fetch($this->packageType . '.list') : null;
        if (is_array($result) == false) {
            $result = $this->scan($filter);
            $this->cache->save($this->packageType . '.list',$result,5);
        } 
        
        return $result;
    }

    /**
     * Return packages path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Load package properties file 
     *
     * @param string $name
     * @return Collection
     */
    public static function loadPackageProperties($name, $path) 
    {         
        $fileName = $path . $name . DIRECTORY_SEPARATOR . 'arikaim-package.json';
        $data = File::readJsonFile($fileName);
        $data = (is_array($data) == true) ? $data : [];

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
    protected function scan($filter = null)
    {
        $items = [];
        foreach (new \DirectoryIterator($this->path) as $file) {
            if ($file->isDot() == true || $file->isDir() == false || substr($file->getFilename(),0,1) == '.') {
                continue;
            }
            $name = $file->getFilename();
            if (is_array($filter) == true) {
                $package = $this->createPackage($name);
                $properties = $package->getProperties();                
                foreach ($filter as $key => $value) {                
                    if ($properties->get($key) == $value) {
                        array_push($items,$name);   
                    }
                }
            } else {
                array_push($items,$name);        
            }
        }  
        
        return $items;
    }

    /**
     * Get package properties
     *
     * @param string $name
     * @param boolean $full
     * @return Collection
     */
    public function getPackageProperties($name, $full = false)
    {
        $package = $this->createPackage($name);

        return $package->getProperties($full);
    }

    /**
     * Find package
     *
     * @param string $param
     * @param mixed $value
     * @return PackageInterface|false
     */
    public function findPackage($param, $value)
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
     * @return bool
     */
    public function installAllPackages()
    {
        $errors = 0;
        $packages = $this->getPackages();
        foreach ($packages as $name) {
            $errors += ($this->installPackage($name) == false) ? 1 : 0;
        }

        return ($errors == 0);
    }

    /**
     * Install package
     *
     * @param string $name
     * @return bool
     */
    public function installPackage($name)
    {
        $package = $this->createPackage($name);

        return $package->install();
    }

    /**
     * Uninstall package
     *
     * @param string $name
     * @return bool
     */
    public function unInstallPackage($name)
    {
        $package = $this->createPackage($name);

        return $package->unInstall();
    }

    /**
     * Enable package
     *
     * @param string $name
     * @return bool
     */
    public function enablePackage($name)
    {
        $package = $this->createPackage($name);

        return $package->enable();
    }

    /**
     * Disable package
     *
     * @param string $name
     * @return bool
     */
    public function disablePackage($name)
    {
        $package = $this->createPackage($name);

        return $package->disable();
    }

    /**
     * Get installed packages.
     *
     * @param integer|null $status
     * @param string|integer $type
     * @return array
     */
    public function getInstalled($status = null, $type = null)
    {
        return [];
    }

    /**
     * Create zip arhive with package files and save to storage/backup/
     *
     * @param string $name
     * @return boolean
     */
    public function createBackup($name)
    {
        $package = $this->createPackage($name);

        $fileName = $package->getName() . '-' . $package->getVersion() . '.zip';
        $sourcePath = $this->getPath() . $name . DIRECTORY_SEPARATOR;
        $destinationPath = Path::STORAGE_BACKUP_PATH . $package->getType() . DIRECTORY_SEPARATOR;
        File::makeDir($destinationPath);

        return ZipFile::create($sourcePath,$destinationPath . $fileName,['.git']);
    }

    /**
     * Create repository driver
     *
     * @param string $repositoryUrl
     * @param boolean $private
     * @return void
     */
    protected function createRepository($repositoryUrl, $private)
    {
        $type = $this->resolveRepositoryType($repositoryUrl,$private);
        switch ($type) {
            case Self::GITHUB_REPOSITORY:           
                return new GitHubRepository($repositoryUrl,$private,Path::STORAGE_REPOSITORY_PATH,$this->path,$this->storage,$this->httpClient);
            case Self::GITHUB_PRIVATE_REPOSITORY:
                return new GitHubPrivateRepository($repositoryUrl,$private,Path::STORAGE_REPOSITORY_PATH,$this->path,$this->storage,$this->httpClient);
        }

        return null;
    }

    /**
     * Resolve package repository type
     *   
     * @param string $repositoryUrl
     * @param boolean $private
     * @return string|null
     */
    protected function resolveRepositoryType($repositoryUrl, $private)
    {
        if (empty($repositoryUrl) == true) {
            return null;
        }
        if ($repositoryUrl == 'arikaim') {
            return Self::ARIKAIM_REPOSITORY;
        }
        if (substr($repositoryUrl,0,8) == 'composer') {
            return Self::COMPOSER_REPOSITORY;
        }
        $url = parse_url($repositoryUrl);

        if ($url['host'] == 'github.com' || $url['host'] == 'www.github.com') {
            return ($private == false) ? Self::GITHUB_REPOSITORY : Self::GITHUB_PRIVATE_REPOSITORY;
        }

        if ($url['host'] == 'bitbucket.org' || $url['host'] == 'www.bitbucket.org') {
            return Self::BITBUCKET_REPOSITORY;
        }

        return null;       
    }   
}
