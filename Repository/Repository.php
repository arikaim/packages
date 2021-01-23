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
use Arikaim\Core\Utils\ZipFile;

/**
 * Repository driver base class
*/
abstract class Repository implements RepositoryInterface
{
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
     * Access key
     *
     * @var string|null
     */
    protected $accessKey;

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
    * @param string $repositoryDir
    * @param string $installDir
    * @param StorageInterface $storage
    * @param HttpClientInterface $httpClient
    * @param boolean $accessKey
    */
    public function __construct(
        string $repositoryUrl,      
        string $repositoryDir, 
        string $installDir, 
        StorageInterface $storage,
        HttpClientInterface $httpClient,
        ?string $accessKey = null
    )
    {
        $this->repositoryUrl = $repositoryUrl;   
        $this->storage = $storage;  
        $this->httpClient = $httpClient; 
        $this->repositoryDir = $repositoryDir;  
        $this->installDir = $installDir;  
        $this->accessKey = $accessKey;
        $this->tempDir = $storage->getFullPath() . 'temp' . DIRECTORY_SEPARATOR;
        $this->resolvePackageName();
    }

    /**
     * Install package
     *
     * @param string|null $version
     * @return boolean
     */
    abstract public function install(?string $version = null): bool;

    /**
     * Should return last version url
     *
     * @return string
     */
    abstract public function getLastVersionUrl(): string;

    /**
     * Should return download repo url
     *
     * @param string $version
     * @return string
     */
    abstract public function getDownloadUrl(string $version): string;

    /**
     * Download package
     *
     * @param string|null $version
     * @return bool
     */
    abstract public function download(?string $version = null): bool;
    
    /**
     * Get package last version
     *
     * @return string
     */
    abstract public function getLastVersion(): ?string;

    /**
     * Return true if repo is private
     *
     * @return boolean
     */
    abstract public function isPrivate(): bool;

    /**
     * Get access key for private repo
     *
     * @return string|null
     */
    public function getAccessKey(): ?string
    {
        return $this->accessKey;
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
     * Extract repositry zip file to  storage/temp folder
     *
     * @param string $version
     * @param string|null $targetDir
     * @return string|false  Return packge folder
    */
    protected function extractRepository(string $version, ?string $targetDir = null)
    {
        $targetDir =  $targetDir ?? $this->tempDir;
        $repositoryName = $this->getRepositoryName();
        $repositoryFolder = $repositoryName . '-' . $version;
        $packageFileName = $this->getPackageFileName($version);
        $zipFile = $this->repositoryDir . $packageFileName;
        
        $this->storage->deleteDir('temp/' . $repositoryFolder);
        ZipFile::extract($zipFile,$targetDir);

        return ($this->storage->has('temp/' . $repositoryFolder) == true) ? $repositoryFolder : false;
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
