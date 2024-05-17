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
use Arikaim\Core\Packages\Repository\Repository;
use Arikaim\Core\Utils\File;

use Arikaim\Core\Utils\Utils;
use Arikaim\Core\App\ArikaimStore;
use Exception;

/**
 * Arikaim repository driver class
*/
class ArikaimRepository extends Repository implements RepositoryInterface
{
  
    /**
     * Return true if repo is private
     *
     * @return boolean
    */
    public function isPrivate(): bool
    {
        return true;
    }

    /**
     * Download package
     *
     * @param string|null $version
     * @return bool
     */
    public function download(?string $version = null): bool
    {
        global $arikaim;

        $version = $version ?? $this->getVersion();
        $url = $this->getPackageApiUrl('/api/repository/package/download/');
      
        File::makeDir($this->getRepositoryPath());
        File::setWritable($this->getRepositoryPath());

        $packageFileName = $this->getRepositoryPath() . $this->getPackageFileName($version);
      
        if (File::exists($packageFileName) == true) {
            File::delete($packageFileName);   
        }
       
        try {         
            $arikaim->get('http')->get($url,[
                'sink' => $packageFileName,
            ]);
        } catch (Exception $e) { 
            echo $e->getMessage();  
            return false;               
        }
      
        return File::exists($packageFileName);
    }

   
    /**
     * Get package last version
     *
     * @return string
     */
    public function getVersion(): string
    {       
        global $arikaim;

        $url = $this->getPackageApiUrl('/api/repository/package/version/');
        $json = $arikaim->get('http')->fetch($url);
        $data = \json_decode($json,true);

        return (\is_array($data) == true) ? $data['result']['version'] ?? '1.0.0' : '1.0.0';         
    }

    /**
     * Get package api url
     *
     * @param string $path
     * @return string
     */
    protected function getPackageApiUrl(string $path): string
    {
        return ArikaimStore::HOST . $path . $this->getPackageName() . '/' . $this->getPackageType();
    }

    /**
     * Install package
     *
     * @param string|null $version
     * @return boolean
     */
    public function install(?string $version = null): bool
    {
        global $arikaim;

        $version = (empty($version) == true) ? $this->getLastVersion() : $version;
        $result = $this->download($version);

        if ($result == true) {
            $repositoryName = $this->getRepositoryName();
            $repositoryFolder = $repositoryName . '-' . $version;
            $repositoryFolder = $this->extractRepository($version,$this->tempDir . $repositoryFolder);
            if ($repositoryFolder == false) {
                // Error extracting zip repository file
                return false;
            }
            $json = $arikaim->get('storage')->read('temp/' . $repositoryFolder . '/arikaim-package.json');
            
            if (Utils::isJson($json) == true) {
                $packageProperties = \json_decode($json,true);
                $packageName = $packageProperties['name'] ?? false;
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
}
