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
 * GitHub repository driver class
*/
class GitHubRepository extends Repository implements RepositoryInterface
{
    /**
     * Download package
     *
     * @return bool
     */
    public function download($version = null)
    {
        $version = (empty($version) == true) ? $this->getLastVersion() : $version;
        $url = "http://github.com/" . $this->getPackageName() . "/archive/" . $version . ".zip";
      
        $packageFileName = $this->repositoryDir . $this->getPackageFileName($version); 
        $this->storage->delete('repository/' . $this->getPackageFileName($version));

        try {         
            $this->httpClient->get($url,['sink' => $packageFileName]);
        } catch (\Exception $e) {    
            return false;
        }
      
        return $this->storage->has('repository/' . $this->getPackageFileName($version));
    }

    /**
     * Get package last version
     *
     * @return string
     */
    public function getLastVersion()
    {
        $packageName = $this->getPackageName();
        $url = "http://api.github.com/repos/" . $packageName . "/releases/latest";
        $json = $this->httpClient->fetch($url);
        $data = \json_decode($json,true);
        if (is_array($data) == true) {
            return (isset($data['tag_name']) == true) ? $data['tag_name'] : '';
        }

        return '';
    }

    /**
     * Resolve package name and repository name
     *
     * @return void
     */
    protected function resolvePackageName()
    {
        $url = parse_url($this->repositoryUrl);
        $path = trim(str_replace('.git','',$url['path']),'/');
        $tokens = explode('/',$path);   

        $this->repositoryName = $tokens[1];    
        $this->packageName = $tokens[0] . '/' .  $this->repositoryName;       
    }

    /**
     * Install package
     *
     * @param string|null $version
     * @return boolean
     */
    public function install($version = null)
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
                $packageProperties = json_decode($json,true);
                $packageName = (isset($packageProperties['name']) == true) ? $packageProperties['name'] : false;

                exit();
                if ($packageName != false) {   
                    $sourcePath = $this->tempDir . $repositoryFolder;
                    $destinatinPath = $this->packagesDir . $packageName;

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
    protected function extractRepository($version)
    {
        $repositoryName = $this->getRepositoryName();
        $repositoryFolder = $repositoryName . "-" . $version;
        $packageFileName = $this->getPackageFileName($version);
        $zipFile = $this->repositoryDir . $packageFileName;
    
        $this->storage->deleteDir('temp/' . $repositoryFolder);

        ZipFile::extract($zipFile,$this->tempDir);

        return ($this->storage->has('temp/' . $repositoryFolder) == true) ? $repositoryFolder : false;
    }
}
