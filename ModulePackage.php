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

use Arikaim\Core\Packages\Package;
use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Packages\Interfaces\PackageInterface;

/**
 * Module Package class
*/
class ModulePackage extends Package implements PackageInterface
{
    const SERVICE = 0;
    const PACKAGE = 1;
    const MIDDLEWARE = 2; 

    /**
     * Module type
     */
    const TYPE_NAME = ['service','package','middleware'];

    /**
     * Get module class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->properties->get('class',ucfirst($this->getName()));
    }

    /**
     * Get module package properties
     *
     * @param boolean $full
     * @return Collection
     */
    public function getProperties($full = false)
    {
        // set default values
        $this->properties['type'] = $this->properties->get('type','service');
        $this->properties['bootable'] = $this->properties->get('bootable',false);
        $this->properties['service_name'] = $this->properties->get('service_name',$this->properties->get('name'));

        if ($full == true) {          
            $this->properties->set('installed',$this->packageRegistry->hasPackage($this->getName()));
            $this->properties->set('status',$this->packageRegistry->getPackageStatus($this->getName()));

            $service = Factory::createModule($this->getName(),$this->getClass());
            $error = ($service == null) ? false : $service->getTestError();
            $this->properties->set('error',$error);            
        }

        return $this->properties; 
    }

     /**
     * Get module console commands class list.
     *
     * @return array
     */
    public function getConsoleCommands()
    {      
        $path = $this->getConsolePath();
        if (File::exists($path) == false) {
            return [];
        }
        $result = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if (
                $file->isDot() == true || 
                $file->isDir() == true ||
                $file->getExtension() != 'php'
            ) continue;
         
            $fileName = $file->getFilename();
            $baseClass = str_replace(".php","",$fileName);
            $class = Factory::getModuleConsoleClassName($this->getName(),$baseClass);          

            $command = Factory::createInstance($class);
            if (is_subclass_of($command,'Arikaim\Core\System\Console\ConsoleCommand') == true) {                                    
                array_push($result,$class);
            }
        }     
        
        return $result;
    }

    /**
     * Install module
     *
     * @param boolean|null $primary Primary package replaces routes or other params
     * @return bool
     */
    public function install($primary = null)
    {
        $data = $this->properties->toArray();

        $module = Factory::createModule($this->getName(),$this->getClass());
        if (is_object($module) == false) {
            return false;
        }
       
        $module->install();

        unset($data['requires']);
        unset($data['help']);
        unset($data['facade']);

        $details = [
            'facade_class'      => $this->properties->getByPath('facade/class',null),
            'facade_alias'      => $this->properties->getByPath('facade/alias',null),
            'type'              => Self::getTypeId($this->properties->get('type')),
            'category'          => $this->properties->get('category',null),
            'class'             => $this->getClass(),
            'console_commands'  => $this->getConsoleCommands()
        ];
        $data = array_merge($data,$details);
        $result = $this->packageRegistry->AddPackage($this->getName(),$data);

        return ($result !== false);
    }

    /**
     * Uninstall module
     *
     * @return bool
     */
    public function unInstall() 
    {
        $result = $this->packageRegistry->removePackage($this->getName());

        return ($result !== false);
    }

    /**
     * Enable module
     *
     * @return bool
     */
    public function enable() 
    {
        return $this->packageRegistry->setPackageStatus($this->getName(),1); 
    }

    /**
     * Disable module
     *
     * @return bool
     */
    public function disable() 
    {
        return $this->packageRegistry->setPackageStatus($this->getName(),0);  
    }   

    /**
     * Get type id
     *
     * @param string $typeName
     * @return integer
     */
    public static function getTypeId($typeName)
    {
        return array_search($typeName,Self::TYPE_NAME);
    }

    /**
     * Get module console commands path
     *    
     * @return string
     */
    public function getConsolePath()
    {
        return $this->path . $this->getName() . DIRECTORY_SEPARATOR . 'console' . DIRECTORY_SEPARATOR;
    }
}
