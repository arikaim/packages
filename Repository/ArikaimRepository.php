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

use Arikaim\Core\Packages\Interfaces\RepositoryInterface;
use Arikaim\Core\Packages\Repository\Repository;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\ZipFile;
use Arikaim\Core\Utils\Utils;

/**
 * Arikaim repository driver class
*/
class ArikaimRepository extends Repository implements RepositoryInterface
{
    /**
     * Download package
     *
     * @param string|null $version
     * @return bool
     */
    public function download(?string $version = null): bool
    {
        return false;
    }

    /**
     * Get package last version
     *
     * @return string|null
     */
    public function getLastVersion(): ?string
    {
        return null;
    }

    /**
     * Resolve package name and repository name
     *
     * @return void
     */
    protected function resolvePackageName(): void
    {
        $url = \parse_url($this->repositoryUrl);
        $path = \trim(\str_replace('.git','',$url['path']),'/');
        $tokens = \explode('/',$path);   

        $this->repositoryName = $tokens[1];    
        $this->packageName = $tokens[0] . '/' .  $this->repositoryName;       
    }

    /**
     * Install package
     *
     * @param string|null $version
     * @return boolean
     */
    public function install(?string $version = null): bool
    {
        $version = (empty($version) == true) ? $this->getLastVersion() : $version;
        $result = $this->download($version);

        if ($result == true) {
            $repositoryFolder = $this->extractRepository($version);
            if ($repositoryFolder == false) {
                // Error extracting zip repository file
                return false;
            }
            $json = $this->storage->read('temp/' . $repositoryFolder . '/arikaim-package.json');
            
            if (Utils::isJson($json) == true) {
                $packageProperties = \json_decode($json,true);
                $packageName = (isset($packageProperties['name']) == true) ? $packageProperties['name'] : false;
                if ($packageName != false) {   
                    $sourcePath = $this->tempDir . $repositoryFolder;
                    $destinatinPath = $this->installDir . $packageName;
                    $result = File::copy($sourcePath,$destinatinPath);
                    
                    return $result;
                }
                // Missing package name in arikaim-package.json file.
                return false;
            }
            // Not valid package
            return false;
        }

        // Can't download repository
        return false;
    }

    /**
     * Extract repositry zip file to  storage/temp folder
     *
     * @param string $version
     * @return string|false  Return packge folder
     */
    protected function extractRepository(string $version)
    {
        $repositoryName = $this->getRepositoryName();
        $repositoryFolder = $repositoryName . '-' . $version;
        $packageFileName = $this->getPackageFileName($version);
        $zipFile = $this->repositoryDir . $packageFileName;
    
        $this->storage->deleteDir('temp/' . $repositoryFolder);
        ZipFile::extract($zipFile,$this->tempDir);

        return ($this->storage->has('temp/' . $repositoryFolder) == true) ? $repositoryFolder : false;
    }
}
