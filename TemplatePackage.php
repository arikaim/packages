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
use Arikaim\Core\Arikaim;
use Arikaim\Core\Packages\Traits\ViewComponents;

/**
 * Template package 
*/
class TemplatePackage extends Package
{
    use ViewComponents;

    /**
     * Get package properties
     *
     * @param boolean $full
     * @return Collection
     */
    public function getProperties($full = false)
    {
        if ($full == true) {    
            $this->viewPath = $this->getPath() . $this->getName() . DIRECTORY_SEPARATOR;
            $this->properties->set('routes',$this->getRoutes());
            $this->properties['pages'] = $this->getPages();
            $this->properties['components'] = $this->getComponents();
            $this->properties['macros'] = $this->getMacros();
        }

        return $this->properties; 
    }

    /**
     * Install template package
     *
     * @return bool
     */
    public function install()
    {
        // clear cache
        $this->cache->clear();

        $result = Arikaim::options()->set('current.template',$this->getName());
        if ($result == false) {
            return false;
        }
        // delete all template routes
        Arikaim::routes()->deleteRoutes(['template_name' => '*']);

        $routes = $this->getRoutes();
        $routesCount = count($routes);

        // install template routes
        $routesAdded = 0;
      
        foreach ($routes as $route) {
            if (isset($route['path']) == false || isset($route['page']) == false) {             
                continue;
            }
          
            $handlerClass = Factory::getControllerClass("Controller"); 
            $pageName = $this->getName() . ":" . $route['page'];
            $result = Arikaim::routes()->saveTemplateRoute($route['path'],$handlerClass,null,$this->getName(),$pageName);
            if ($result != false) {
                $routesAdded++;
            }
        }
        
        return ($routesAdded == $routesCount);           
    }
    
    /**
     * Uninstall package
     *
     * @return bool
     */
    public function unInstall() 
    {
        // clear cached items
        $this->cache->clear();
        $result = Arikaim::routes()->deleteRoutes(['template_name' => $this->getName()]);
       
        return $result;
    }

    /**
     * Enable package
     *
     * @return bool
     */
    public function enable() 
    {
        // clear cached items
        $this->cache->clear();

        return true;
    }

    /**
     * Disable package
     *
     * @return bool
     */
    public function disable() 
    {
        // clear cached items
        $this->cache->clear();

        return true;
    }   

    /**
     * Get template routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->properties->getByPath('routes',[]);
    }
}
