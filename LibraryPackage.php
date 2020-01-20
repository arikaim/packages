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
use Arikaim\Core\Packages\Interfaces\PackageInterface;
use Arikaim\Core\Utils\Text;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Arikaim;

/**
 * UI Library Package class
*/
class LibraryPackage extends Package implements PackageInterface
{ 
    /**
     * Return library files
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->properties->get('files',[]);
    }

    /**
     * Get library params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->properties->get('params',[]);
    }

    /**
     * Resolve library params
     *
     * @param array $params
     * @return array
     */
    public function resolveParams()
    {      
        $params = $this->getParams();
        $vars = [
            'domian'    => DOMAIN,
            'base_url'  => Url::BASE_URL
        ];

        $options = Arikaim::options()->get('library.params');
        $libraryParams = (isset($options[$this->getName()]) == true) ? $options[$this->getName()] : [];
        $vars = array_merge($vars,$libraryParams);

        return Text::renderMultiple($params,$vars);    
    }

    /**
     * Return true if library is framework
     *
     * @return boolean
     */
    public function isFramework()
    {       
        return $this->properties->get('framework',false);
    }

    /**
     * Get theme file
     *
     * @param string $theme
     * @return string
     */
    public function getThemeFile($theme)
    {
        return $this->properties->getByPath("themes/$theme/file","");
    }
}
