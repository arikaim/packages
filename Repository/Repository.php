<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     Packages
*/
namespace Arikaim\Core\Packages\Repository;

use Arikaim\Core\Packages\Interfaces\RepositoryInterface;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Utils\ZipFile;
use Arikaim\Core\Utils\File;
use Closure;

/**
 * Repository driver base class
*/
abstract class Repository implements RepositoryInterface
{
    /**
     * Package name
     *
     * @var string
     */
    protected $packageName;

    /**
     * Package type
     *
     * @var string
     */
    protected $packageType;

    /**
    * Constructor
    *
    * @param string $packageName
    * @param string $packageType
    */
    public function __construct(string $packageName, string $packageType)
    {
        $this->packageName = $packageName;
        $this->packageType = $packageType;
    }

    /**
     * Get access token for private repo
     *
     * @return string|null
    */
    public function getAccessToken(): ?string
    {
        return null;
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
    abstract public function getVersion(): string;

    /**
     * Return true if repo is private
     *
     * @return boolean
     */
    abstract public function isPrivate(): bool;

    /**
     * Extract package
     *
     * @param string       $destination
     * @param string|null  $version
     * @param Closure|null $callback
     * @return boolean
     */
    public function extractPackage(string $destination, ?string $version = null, ?Closure $callback = null): bool
    { 
        if (File::exists($destination) == false) {
            File::makeDir($destination);
        }

        $version = $version ?? $this->getVersion();
        $packageFileName = $this->getRepositoryPath() . $this->getPackageFileName($version);
        $files = $this->getPackageFiles($version);

        $errors = 0;
        foreach ($files as $file) {
            $extract = (\is_callable($callback) == true) ? $callback($file) : true;
            if ($extract == true) {
                $result = ZipFile::extract($packageFileName,$destination,$file);
                $errors += ($result == false) ? 1 : 0;
            }
        }

        return ($errors == 0);
    }

    /**
     * Get package files
     *
     * @param string|null $version
     * @return array|null
     */
    public function getPackageFiles(?string $version = null): ?array
    {
        $version = $version ?? $this->getVersion();
        $packageFileName = $this->getRepositoryPath() . $this->getPackageFileName($version);

        return ZipFile::getFiles($packageFileName);
    }

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
     * Get repository path
     *
     * @return string
     */
    public function getRepositoryPath(): string
    {
        return PATH::STORAGE_REPOSITORY_PATH . $this->getPackageType() . DIRECTORY_SEPARATOR;
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
     * Get package type
     *
     * @return string
     */
    public function getPackageType(): string
    {
        return $this->packageType;
    }
}
