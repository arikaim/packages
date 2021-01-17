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
    abstract public function install(?string $version = null): bool;

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
     * Package install dir
     *
     * @var string
     */
    protected $installDir;

    /**
    * Constructor
    *
    * @param string $repositoryUrl
    * @param boolean $private
    * @param string $repositoryDir
    * @param string $installDir
    * @param StorageInterface $storage
    * @param HttpClientInterface $httpClient
    */
    public function __construct(
        string $repositoryUrl, 
        bool $private = false, 
        string $repositoryDir, 
        string $installDir, 
        StorageInterface $storage,
        HttpClientInterface $httpClient
    )
    {
        $this->repositoryUrl = $repositoryUrl;   
        $this->storage = $storage;  
        $this->httpClient = $httpClient; 
        $this->repositoryDir = $repositoryDir;  
        $this->installDir = $installDir;  
        $this->private = $private;
        $this->tempDir = $storage->getFullPath() . 'temp' . DIRECTORY_SEPARATOR;
        $this->resolvePackageName();
    }

    /**
     * Return true if repository is private
     *
     * @return boolean
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * Get repository url
     *
     * @return string
     */
    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    /**
     * Download package
     *
     * @param string|null $version
     * @return bool
     */
    public abstract function download(?string $version = null): bool;
    
    /**
     * Get package last version
     *
     * @return string
     */
    public abstract function getLastVersion(): ?string;

    /**
     * Get package file name
     *
     * @param string $version
     * @return string
     */
    public function getPackageFileName(string $version): string
    {
        $fileName = \str_replace('/','_',$this->getPackageName());

        return $fileName . '-' . $version . '.zip';
    }
    
    /**
     * Get package name
     *
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * Get repository name
     *
     * @return string
     */
    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    /**
     * Resolve package name and repository name
     *
     * @return void
    */
    protected function resolvePackageName(): void
    {
    }
}
