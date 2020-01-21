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

use Arikaim\Core\Packages\Interfaces\PackageInterface;
use Arikaim\Core\Packages\Package;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Arikaim;
use Arikaim\Core\Packages\Traits\ViewComponents;

/**
 * Extension Package
*/
class ExtensionPackage extends Package implements PackageInterface
{
    use ViewComponents;

    /**
     *  Extension type
     */
    const USER   = 0;
    const SYSTEM = 1;

    /**
     * Extension types
     *
     * @var array
     */
    private $typeName = ['user','system'];

    /**
     * Get extension package properties
     *
     * @param boolean $full
     * @return Collection
     */
    public function getProperties($full = false)
    {
        $type = $this->properties->get('type','user');
        $this->properties['type'] = $this->getTypeId($type);
        $this->properties['class'] = ucfirst($this->getName());       
        $this->properties['installed'] = $this->packageRegistry->hasPackage($this->getName());       
        $this->properties['status'] = $this->packageRegistry->getPackageStatus($this->getName());
        $this->properties['admin_menu'] = $this->properties->get('admin-menu',null);
        $this->properties['primary'] = $this->packageRegistry->isPrimary($this->getName());

        if ($full == true) { 
            $this->properties['routes'] = Arikaim::routes()->getRoutes(['extension_name' => $this->getName()]);
            $this->properties['events'] = Arikaim::event()->getEvents(['extension_name' => $this->getName()]);
            $this->properties['subscribers'] = Arikaim::event()->getSubscribers(['extension_name' => $this->getName()]);
            $this->properties['database'] = $this->getModels();
            $this->properties['console_commands'] = $this->getConsoleCommands();
            $this->properties['jobs'] = $this->getExtensionJobs();
            $this->properties['pages'] = $this->getPages();
            $this->properties['components'] = $this->getComponents();
            $this->properties['macros'] = $this->getMacros();
        }
        
        return $this->properties; 
    }

    /**
     * Set package as primary
     *
     * @return boolean
     */
    public function setPrimary()
    {
        $result = $this->packageRegistry->setPrimary($this->getName());            
      
        return $result;       
    }

    /**
     * Get extension jobs
     *
     * @return array
     */
    public function getExtensionJobs()
    {
        $path = $this->getJobsPath();
        $result = [];
        if (File::exists($path) == false) {
            return [];
        }

        foreach (new \DirectoryIterator($path) as $file) {
            if (
                $file->isDot() == true || 
                $file->isDir() == true ||
                $file->getExtension() != 'php'
            ) continue;
          
            $item['base_class'] = str_replace(".php","",$file->getFilename());
            $job = Factory::createJob($item['base_class'],$this->getName());
            if (is_object($job) == true) {
                $item['name'] = $job->getName();
                array_push($result,$item);
            }
        }

        return $result;
    }

    /**
     * Get extension console commands
     *
     * @return array
     */
    public function getConsoleCommands()
    {
        $extension = $this->packageRegistry->getPackage($this->getName()); 
        if ($extension == false) {
            return [];
        }
        $result = [];
        foreach ($extension['console_commands'] as $class) {
            $command = Factory::createInstance($class);
            if (is_object($command) ==true) {
                $item['name'] = $command->getName();
                $item['title'] = $command->getDescription();      
                $item['help'] = "php cli " . $command->getName();         
                array_push($result,$item);
            }          
        } 

        return $result;      
    }

    /**
     * Get extension models.
     *
     * @return array
     */
    public function getModels()
    {      
        $path = $this->getModelsSchemaPath();
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
            $schema = Factory::createSchema($baseClass,$this->getName());

            if (is_subclass_of($schema,'Arikaim\Core\Db\Schema') == true) {               
                $item['name'] = $schema->getTableName();               
                array_push($result,$item);
            }
        }    

        return $result;
    }

    /**
     * Install extension package
     *
     * @param boolean|null $primary Primary package replaces routes or other params
     * @return mixed|true
     */
    public function install($primary = null)
    {
        $details = $this->getProperties(false);
        $extensionName = $this->getName();
        $extObj = Factory::createExtension($extensionName,$details->get('class'));
        if (is_object($extObj) == false) {
            return false;
        }
        
        $primary = (empty($primary) == true) ? $details['primary'] : $primary;
        // check for primary 
        if ($primary == true) {
            $extObj->setPrimary();
        }

        // delete extension routes
        Arikaim::routes()->deleteRoutes(['extension_name' => $extensionName]);

        // delete jobs 
        Arikaim::queue()->deleteJobs(['extension_name' => $extensionName]);

        // delete registered events
        Arikaim::event()->deleteEvents(['extension_name' => $extensionName]);     

        // delete registered events subscribers
        Arikaim::event()->deleteSubscribers(['extension_name' => $extensionName]);

        // run install extension      
        $extObj->install(); 
      
        // get console commands classes
        $details->set('console_commands',$extObj->getConsoleCommands());
      
        // register events subscribers        
        $this->registerEventsSubscribers();
                   
        $details->set('status',1);
        $this->packageRegistry->AddPackage($extensionName,$details->toArray());

        return ($extObj->hasError() == true) ? $extObj->getErrors() : true;        
    }

    /**
     * Uninstall extension package
     *
     * @return mixed|true
     */
    public function unInstall() 
    { 
        $details = $this->getProperties(true);
        $extensionName = $this->getName();
        $extObj = Factory::createExtension($extensionName,$details->get('class'));
        
        // delete registered routes
        Arikaim::routes()->deleteRoutes(['extension_name' => $extensionName]);

        // delete registered events
        Arikaim::event()->deleteEvents(['extension_name' => $extensionName]);

        // delete registered events subscribers
        Arikaim::event()->deleteSubscribers(['extension_name' => $extensionName]);

        // delete extension options
        Arikaim::options()->remove(null,$extensionName);

        // delete jobs 
        Arikaim::queue()->deleteJobs(['extension_name' => $extensionName]);
    
        // run extension unInstall
        $extObj->unInstall();        
        $this->packageRegistry->removePackage($extensionName);

        return ($extObj->hasError() == true) ? $extObj->getErrors() : true;     
    }

    /**
     * Enable extension
     *
     * @return bool
     */
    public function enable() 
    {
        $name = $this->getName();
        $this->packageRegistry->setPackageStatus($name,1);

        // enable extension routes
        Arikaim::routes()->setRoutesStatus(['extension_name' => $name],1);

        // enable extension events
        Arikaim::event()->setEventsStatus(['extension_name' => $name],1);  

        return true;
    }

    /**
     * Disable extension
     *
     * @return bool
     */
    public function disable() 
    {
        $name = $this->getName();
        $this->packageRegistry->setPackageStatus($name,0);

        // disable extension routes
        Arikaim::routes()->setRoutesStatus(['extension_name' => $name],0);         
        
        // disable extension events
        Arikaim::event()->setEventsStatus(['extension_name' => $name],0);  
        
        return true;
    }   

    /**
     * Register event subscribers
     *
     * @return integer
     */
    public function registerEventsSubscribers()
    {
        $count = 0;
        $name = $this->getName();
        $path = $this->getSubscribersPath($name);       
        if (File::exists($path) == false) {
            return $count;
        }

        foreach (new \DirectoryIterator($path) as $file) {
            if (($file->isDot() == true) || ($file->isDir() == true)) continue;
            if ($file->getExtension() != 'php') continue;
            
            $baseClass = str_replace(".php","",$file->getFilename());
            // add event subscriber to db table
            $result = Arikaim::event()->registerSubscriber($baseClass,$name);
            $count += ($result == true) ? 1 : 0;
        }     

        return $count;
    }

    /**
     * Return extension type id
     *
     * @param string|integer $typeName
     * @return integer|false
     */
    public function getTypeId($typeName)
    {
        return (is_string($typeName) == true) ? array_search($typeName,$this->typeName) : $typeName;          
    }

    /**
     * Get extension jobs path
     *   
     * @return string
     */
    public function getJobsPath()   
    {
        return $this->path . $this->getName() . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get extension models schema path
     *  
     * @return string
     */
    public function getModelsSchemaPath()   
    {
        return $this->getModelsPath() . 'schema' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get extension model path
     *   
     * @return string
     */
    public function getModelsPath()   
    {
        return $this->path . $this->getName() . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get extension subscribers path.
     *    
     * @return string
     */
    public function getSubscribersPath()   
    {
        return $this->path . $this->getName() . DIRECTORY_SEPARATOR . 'subscribers' . DIRECTORY_SEPARATOR;
    }
}
