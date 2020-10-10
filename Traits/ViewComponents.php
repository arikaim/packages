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
     * @param string|null $componentsType
     * @return string
     */
    public function getViewPath($componentsType = null)
    {
        if ($this->getType() == 'template') {
            $path = $this->getPath() . $this->getName() . DIRECTORY_SEPARATOR;
        } else {
            $path = (empty($this->viewPath) == true) ? $this->getPath() . $this->getName() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : $this->viewPath;
        }
        
        return (empty($componentsType) == true) ? $path : $path . $componentsType . DIRECTORY_SEPARATOR;
    }

    /**
     * Get components path
     *  
     * @return string
     */
    public function getComponentsPath()  
    {
        return $this->getViewPath('components');
    }

    /**
     * Get pages path
     *        
     * @return string
     */
    public function getPagesPath()  
    {
        return $this->getViewPath('pages');
    }

    /**
     * Get emails components path
     *
     * @return string
     */
    public function getEmailsPath()  
    {
        return $this->getViewPath('emails');
    }

    /**
     * Get macros path
     *
     * @return string
     */
    public function getMacrosPath()
    {
        return $this->getViewPath('macros');
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
            if ($fileExt != 'html' && $fileExt != 'htm') continue;           
            
            $item['name'] = \str_replace('.' . $fileExt,'',$file->getFilename());
            \array_push($items,$item);            
        }

        return $items;
    }

    /**
     * Scan directory and return pages list
     *
     * @param string $path
     * @return array
     */
    public function getPages($parent = '')
    {
        return $this->getComponents($parent,'pages');
    }

    /**
     * Scan directory and return emails list
     *
     * @param string $path
     * @return array
     */
    public function getEmails($parent = '')
    {
        return $this->getComponents($parent,'emails');
    }

    /**
     * Get component path
     *
     * @param string $componentName
     * @param string $type
     * @return string
     */
    public function getComponentPath($componentName, $type = 'components')
    {
        $componentPath = \str_replace('.',DIRECTORY_SEPARATOR,$componentName);
        $path = $this->getViewPath($type) . $componentPath;

        return $path;
    }

    /**
     * Scan directory and return components list
     *
     * @param string $parent
     * @param string $type
     * @return array
     */
    public function getComponents($parent = '', $type = 'components')
    {
        $path = $this->getComponentPath($parent,$type);
        if (File::exists($path) == false) {
            return [];
        }        

        $items = [];    

        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot() == true) continue;
            if ($file->isDir() == true) {
                $item['name'] = $file->getFilename();    
                if (\substr($item['name'],0,1) == '.') continue;
                
                $item['parent'] = $parent;                 
                $item['full_name'] = (empty($parent) == false) ? $item['parent'] . '.' . $item['name'] : $item['name'];  
                $fileId = (empty($parent) == false) ? $item['parent'] . '_' . $item['name'] : $item['name'];  
                $item['id'] = \str_replace('.','_',$fileId);

                \array_push($items,$item);
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
    public function getComponentsRecursive($path = null)
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
                
                $componentPath = \str_replace($path,'',$file->getRealPath());                
                $componentPath = \str_replace(DIRECTORY_SEPARATOR,'.',$componentPath);
               
                $item['full_name'] = $componentPath;
                \array_push($items,$item);
            }
        }

        return $items;
    }
}
