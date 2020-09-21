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

use Arikaim\Core\Utils\File;
use Arikaim\Core\Packages\PackageManagerFactory;
use Arikaim\Core\System\Composer;

/**
 * Package validator class
*/
class PackageValidator 
{
    /**
     * Package properties
     *
     * @var array
     */
    protected $requires;

    /**
     * Constructor
     *
     * @param array $requires
     */
    public function __construct(array $requires) 
    {
        $this->requires = $requires;
    }

    /**
     * Validate package requirements
     *
     * @return array
     */
    public function validate()
    {
        $result['library'] = $this->validateItems('library','library');
        $result['extensions'] = $this->validateItems('extension','extensions');
        $result['modules'] = $this->validateItems('module','modules');
        $result['themes'] = $this->validateItems('template','themes');
        $result['composer'] = $this->validateComposerPackages();
        $result['count'] = count($result['library']) + 
            count($result['extensions']) + 
            count($result['modules']) + 
            count($result['themes']) + 
            count($result['composer']); 
        
        return $result;
    }

    /**
     * Parse item name 
     *
     * @param string $name
     * @return array
     */
    protected function parseItemName($name)
    {
        $tokens = \explode(':',$name);
        $version = (isset($tokens[1]) == true) ? $tokens[1] : null;
        $option = (isset($tokens[2]) == true) ? $tokens[2] : $version;
        $optinal = ($option == 'optional') ? true : false;
        $version = ($version == 'optional') ? null : $version;
        
        return [$tokens[0],$version,$optinal];
    }

    /**
     * Validate composer packages
     *
     * @return array
     */
    public function validateComposerPackages() 
    {
        $result = [];
        $items = (isset($this->requires['composer']) == true) ? $this->requires['composer'] : [];
        if (count($items) == 0) {
            return [];
        }
        $packageInfo = Composer::getLocalPackagesInfo(ROOT_PATH . BASE_PATH,$items);

        foreach ($items as $item) {
            list($name,$version,$optional) = $this->parseItemName($item);
            $valid = (isset($packageInfo[$name]) == true);
            $packageVersion = (isset($packageInfo[$name]['version']) == true) ? $packageInfo[$name]['version'] : null;
            
            $warning = ($valid == true) ? (\version_compare($packageVersion,$version) == -1) : false;
              
            $result[] = [
                'name'            => $name,
                'version'         => $version,
                'package_version' => $packageVersion,
                'warning'         => $warning,
                'optional'        => $optional,
                'valid'           => $valid
            ];
        }
        
        return $result;
    }

    /**
     * Validate required items
     *
     * @param string $packageType
     * @param string $requireItemKey
     * @return array
     */
    protected function validateItems($packageType, $requireItemKey)
    {
        $items = (isset($this->requires[$requireItemKey]) == true) ? $this->requires[$requireItemKey] : [];
        $result = [];

        foreach ($items as $item) {
            list($name,$version,$optional) = $this->parseItemName($item);
            $fileName = PackageManagerFactory::getPackageDescriptorFileName($packageType,$name);
            $valid = (File::exists($fileName) == true);
          
            if ($valid == true) {
                $properties = File::readJsonFile($fileName);
                $packageVersion = (isset($properties['version']) == true) ? $properties['version'] : null;
                $warning = (empty($version) == false && \version_compare($packageVersion,$version) == -1);
            } else {
                $warning = false;
                $packageVersion = null;
            }
           
            $result[] = [
                'name'            => $name,
                'version'         => $version,
                'package_version' => $packageVersion,
                'warning'         => $warning,
                'optional'        => $optional,
                'valid'           => $valid
            ];
        }

        return $result;
    }
}
