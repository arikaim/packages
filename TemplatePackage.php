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
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Arikaim;
use Arikaim\Core\Packages\Traits\ViewComponents;
use Arikaim\Core\Collection\Collection;
use DirectoryIterator;

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
        $this->properties['icon'] = $this->properties->get('icon','file alternate outline'); 
        if ($full == true) {              
            $this->viewPath = $this->getPath() . $this->getName() . DIRECTORY_SEPARATOR;
            $this->properties['routes'] = Arikaim::routes()->getRoutes(['template_name' => $this->getName()]);
            $this->properties['pages'] = $this->getPages();
            $this->properties['components'] = $this->getComponents();
            $this->properties['macros'] = $this->getMacros();

            $primaryTemplate = Arikaim::options()->get('primary.template',null);
            $this->properties['primary'] = ($primaryTemplate == $this->getName());
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
        Arikaim::options()->set('primary.template',$this->getName());
        
        return true;
    }

    /**
     * Install template package
     *
     * @param boolean|null $primary Primary package replaces routes or other params
     * @return bool
     */
    public function install($primary = null)
    {
        $routes = $this->getRoutes();
        $routesCount = count($routes);

        // install template routes
        $routesAdded = 0;
        $primaryTemplate = Arikaim::options()->get('primary.template',null);
        $primary = (empty($primary) == true) ? ($this->getName() == $primaryTemplate) : $primary;

        foreach ($routes as $item) {
            $route = Collection::create($item);
            if ($route->isEmpty('path') == true) {             
                continue;
            }

            if ($route->isEmpty('handler') == false) {    
                $handlerClass = Factory::getExtensionControllerClass($route->getByPath('handler/extension'),$route->getByPath('handler/class'));
            } else {
                $handlerClass = Factory::getControllerClass("Controller");
            }
            //       
            $handlerParams = $route->getByPath('handler/params',null); 
            $handlerMethod = $route->getByPath('handler/method',null);
            $pageName = ($route->isEmpty('page') == false) ? $this->getName() . ":" . $route['page'] : null;
            $auth = $route->getByPath('access/auth',null);
            $auth = Arikaim::access()->resolveAuthType($auth);
            $redirect = $route->getByPath('access/redirect',null);
            $languagePath = $route->get('language-path',false); 
            $pattern = $route['path']; 
            // Route type
            $type = ($route->get('home',false) == false) ? 1 : 3; 

            $result = Arikaim::routes()->saveTemplateRoute($pattern,$handlerClass,$handlerMethod,$this->getName(),$pageName,$auth,$primary,$redirect,$type,$languagePath);
            if ($result != false) {
                $routesAdded++;
                if (empty($handlerParams) == false) {
                    Arikaim::routes()->saveRouteOptions('GET',$pattern,$handlerParams);
                }
            }
        }
        // build assets
        $this->buildAssets();

        return ($routesAdded == $routesCount);           
    }
    
    /**
     * Build css template files
     *
     * @return boolean
     */
    public function buildAssets()
    {        
        $cssPath = Path::TEMPLATES_PATH . $this->getName() . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR;
        $twig = Arikaim::view()->createEnvironment($cssPath);
        $params = [
            'template_url' => Url::TEMPLATES_URL . '/' . $this->getName() . '/'
        ];

        if (File::isWritable($cssPath) == false) {
            File::setWritable($cssPath);
        }
        
        foreach (new DirectoryIterator($cssPath) as $file) {
            if ($file->isDot() == true || $file->isDir() == true) {
                continue;
            }            
            $fileName = $file->getFilename();
            $tokens = explode('.',$fileName);
          
            if (isset($tokens[1]) == true) {
                if ($tokens[1] == 'html') {
                    $code = $twig->render($fileName,$params);
                    $cssfileName = str_replace('.html','',$fileName);
                    File::write($cssPath . $cssfileName,$code);
                }
            }
           
        }

        return true;
    }
    
    /**
     * Uninstall package
     *
     * @return bool
     */
    public function unInstall() 
    {
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
        return true;
    }

    /**
     * Disable package
     *
     * @return bool
     */
    public function disable() 
    {
        return true;
    }   

    /**
     * Get template routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->properties->get('routes',[]);
    }
}
