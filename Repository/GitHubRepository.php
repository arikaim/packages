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
use Arikaim\Core\Utils\Utils;
use Exception;

/**
 * GitHub repository driver class
*/
class GitHubRepository extends Repository implements RepositoryInterface
{
    /**
     * Get last version url
     *
     * @return string
     */
    public function getLastVersionUrl(): string
    {
        return 'https://api.github.com/repos/' . $this->getPackageName() . '/releases/latest';
    }
    
    /**
     * Get download repo url
     *
     * @param string $version
     * @return string
     */
    public function getDownloadUrl(string $version): string
    {
        return 'https://github.com/' . $this->getPackageName() . '/archive/' . $version . '.zip';
    }

    /**
     * Download package
     * 
     * @param string|null $version
     * @return bool
     */
    public function download(?string $version = null): bool
    {
        $version = $version ?? $this->getLastVersion();
        $url = $this->getDownloadUrl($version); 
      
        File::setWritable($this->repositoryDir);
        $packageFileName = $this->repositoryDir . $this->getPackageFileName($version); 

        if ($this->storage->has('repository/' . $this->getPackageFileName($version)) == true) {
            try {         
                $this->storage->delete('repository/' . $this->getPackageFileName($version),false);
            } catch (Exception $e) {                   
                return false;
            }
        }
       
        try {         
            $this->httpClient->get($url,['sink' => $packageFileName]);
        } catch (Exception $e) {              
        }
      
        return $this->storage->has('repository/' . $this->getPackageFileName($version));
    }

    /**
     * Get package last version
     *
     * @return string
     */
    public function getLastVersion(): string
    {
        $url = $this->getLastVersionUrl();
        $json = $this->httpClient->fetch($url);
        $data = \json_decode($json,true);

        return (\is_array($data) == true) ? $data['tag_name'] ?? '' : '';         
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
            if ($repositoryFolder === false) {
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
}
