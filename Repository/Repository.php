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
     * Package ref
     *
     * @var object
     */
    protected $package;

    /**
     * Local storage
     *
     * @var StorageInterface|null
     */
    protected $storage = null;

    /**
     * Http client
     *
     * @var HttpClientInterface|null
     */
    protected $httpClient = null;

    /**
     * Temp directory
     *
     * @var string|null
     */
    protected $tempDir = null;

    /**
    * Constructor
    *
    * @param object $package
    * @param StorageInterface|null $storage
    * @param HttpClientInterface|null $httpClient
    */
    public function __construct(object $package, ?StorageInterface $storage = null, ?HttpClientInterface $httpClient = null)
    {
        $this->package = $package;
        $this->storage = $storage;  
        $this->httpClient = $httpClient; 
        $this->tempDir = (empty($storage) == false) ? $storage->getFullPath() . 'temp' . DIRECTORY_SEPARATOR : null;
    }

    /**
     * Install package
     *
     * @param string|null $version
     * @return boolean
     */
    abstract public function install(?string $version = null): bool;

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
    abstract public function getVersion(): ?string;

    /**
     * Return true if repo is private
     *
     * @return boolean
     */
    abstract public function isPrivate(): bool;

    /**
     * Get package file name
     *
     * @param string $version
     * @return string
     */
    public function getPackageFileName(string $version): string
    {
        return \str_replace('/','_',$this->getPackageName()) . '-' . $version . '.zip';
    }
    
    /**
     * Get package name
     *
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->package->getName();
    }

    /**
     * Get package type
     *
     * @return string
     */
    public function getPackageType(): string
    {
        return $this->package->getType();
    }
}
