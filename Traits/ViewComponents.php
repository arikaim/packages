<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Packages\Traits;

use Arikaim\Core\Utils\File;

/**
 * View components trait
*/
trait ViewComponents 
{
    /**
     * Get view path
     *    
     * @return string
     */
    public function getViewPath()
    {
        return (empty($this->viewPath) == true) ? $this->getPath() . $this->getName() . DIRECTORY_SEPARATOR . "view" . DIRECTORY_SEPARATOR : $this->viewPath;
    }

    /**
     * Get components path
     *   
     * @return string
     */
    public function getComponentsPath()  
    {
        return $this->getViewPath() . 'components' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get pages path
     *    
     * @return string
     */
    public function getPagesPath()  
    {
        return $this->getViewPath() . 'pages' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get macros path
     *
     * @return string
     */
    public function getMacrosPath()
    {
        return $this->getViewPath() . "macros" . DIRECTORY_SEPARATOR;
    }

    /**
     * Scan directory and return macros list
     *
     * @param string|null $path
     * @return array
     */
    public function getMacros($path = null)
    {       
        $path = (empty($path) == true) ? $this->getMacrosPath() : $path;

        if (File::exists($path) == false) {
            return [];
        }
        $items = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot() == true || $file->isDir() == true) continue;
            
            $fileExt = $file->getExtension();
            if ($fileExt != "html" && $fileExt != "htm") continue;           
            
            $item['name'] = str_replace(".$fileExt",'',$file->getFilename());
            array_push($items,$item);            
        }

        return $items;
    }

    /**
     * Scan directory and return pages list
     *
     * @param string|null $path
     * @return array
     */
    public function getPages($path = null)
    {
        $path = (empty($path) == true) ? $this->getPagesPath() : $path;

        if (File::exists($path) == false) {
            return [];
        }
        $items = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot() == true) continue;
            if ($file->isDir() == true) {
                $item['name'] = $file->getFilename();
                array_push($items,$item);
            }
        }

        return $items;
    }

    /**
     * Scan directory and return components list
     *
     * @param string|null $path
     * @return array
     */
    public function getComponents($path = null)
    {       
        $path = (empty($path) == true) ? $this->getComponentsPath() : $path;
        if (File::exists($path) == false) {
            return [];
        }
        
        $items = [];
        $dir = new \RecursiveDirectoryIterator($path,\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir,\RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if ($file->isDir() == true) {
                $item['name'] = $file->getFilename();   
                $item['path'] = $file->getPathname();
                
                $componentPath = str_replace($path,'',$file->getRealPath());                
                $componentPath = str_replace(DIRECTORY_SEPARATOR,'.',$componentPath);
               
                $item['full_name'] = $componentPath;
                array_push($items,$item);
            }
        }

        return $items;
    }
}
