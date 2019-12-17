<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Packages\Repository;

use Arikaim\Core\Interfaces\StorageInterface;
use Arikaim\Core\Interfaces\HttpClientInterface;
use Arikaim\Core\Packages\Interfaces\RepositoryInterface;

/**
 * Repository driver base class
*/
abstract class Repository implements RepositoryInterface
{

    /**
     * Install package
     *
     * @param string|null $version
     * @return boolean
     */
    abstract public function install($version = null);

    /**
     * Repository url
     *
     * @var string
     */
    protected $repositoryUrl;

    /**
     * Package name
     *
     * @var string
     */
    protected $packageName;

    /**
     * Repo name
     *
     * @var string
     */
    protected $repositoryName;

    /**
     * Local storage
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
     * Storage repository dir
     *
     * @var string
     */
    protected $repositoryDir;

    /**
     * Private or Public repo
     *
     * @var boolean
     */
    protected $private;

    /**
     * Temp directory
     *
     * @var string
     */
    protected $tempDir;

    /**
     * Constructor
     * 
     * @param string $repositoryUrl  
     */
    public function __construct($repositoryUrl, $private = false, $repositoryDir, StorageInterface $storage, HttpClientInterface $httpClient)
    {
        $this->repositoryUrl = $repositoryUrl;   
        $this->storage = $storage;  
        $this->httpClient = $httpClient; 
        $this->repositoryDir = $repositoryDir;    
        $this->private = $private;
        $this->tempDir = $storage->getFuillPath() . 'temp' . DIRECTORY_SEPARATOR;
        $this->resolvePackageName();
    }

    /**
     * Return true if repository is private
     *
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * Get repository url
     *
     * @return string
     */
    public function getRepositoryUrl()
    {
        return $this->repositoryUrl;
    }

    /**
     * Download package
     *
     * @return bool
     */
    public abstract function download($version = null);
    
    /**
     * Get package last version
     *
     * @return string
     */
    public abstract function getLastVersion();

    /**
     * Get package file name
     *
     * @param string $version
     * @return string
     */
    public function getPackageFileName($version)
    {
        $fileName = str_replace('/','_',$this->getPackageName());

        return $fileName . '-' . $version . '.zip';
    }
    
    /**
     * Get package name
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Get repository name
     *
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->repositoryName;
    }

    /**
     * Resolve package name and repository name
     *
     * @return void
    */
    protected function resolvePackageName()
    {
    }
}
