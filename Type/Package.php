<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     Packages
*/
namespace Arikaim\Core\Packages\Type;

use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Packages\PackageValidator;
use Arikaim\Core\Packages\Interfaces\PackageInterface;
use Arikaim\Core\Packages\Interfaces\PackageRegistryInterface;
use Arikaim\Core\Collection\Interfaces\CollectionInterface;
use Arikaim\Core\Collection\Collection;

/**
 * Package base class
*/
class Package implements PackageInterface
{
    /**
     * Package properties
     *
     * @var CollectionInterface
     */
    protected $properties;

    /**
     * Package Registry Interface
     *
     * @var PackageRegistryInterface|null
     */
    protected $packageRegistry;

    /**
     * Package root path
     *
     * @var string
     */
    protected $path;

    /**
     * Package type
     *
     * @var string|null
     */
    protected $type;

    /**
     * Package name
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor
     *
     * @param string $path    
     * @param CollectionInterface $properties
     * @param PackageRegistryInterface|null $packageRegistry
     */
    public function __construct(
        string $path, 
        string $name, 
        ?PackageRegistryInterface $packageRegistry = null,
        ?string $type = null
    ) 
    {
        $this->path = $path;      
        $this->name = $name;      
        $this->properties = new Collection([]);        
        $this->packageRegistry = $packageRegistry;
        $this->type = $type;
    }

    /**
     * Load package descriptor file
     *
     * @return void
     */
    public function loadProperties(): void 
    {         
        $fileName = $this->getPath() . DIRECTORY_SEPARATOR . 'arikaim-package.json';
        $data = File::readJsonFile($fileName);
        if (\is_array($data) == true) {
            if (empty($data['name'] ?? null) == true) {
                $data['name'] = $this->name;
            }           
    
            $this->properties->withData($data);   
        }          
    }

    /**
     * Get drivers classes list
     *
     * @return array
     */
    public function getDrivers(): array
    {
        return [];
    }

    /**
     * Return true if package is installed
     *
     * @return boolean
     */
    public function isInstalled(): bool
    {
        return true;
    } 

    /**
     * Create package validator
     *
     * @return PackageValidator
     */
    public function validator()
    {
        return new PackageValidator($this->properties->get('require',[]));
    } 

    /**
     * Get package root path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path . $this->getName() . DIRECTORY_SEPARATOR;
    }

    /**
     * Get Package version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return Utils::formatVersion($this->properties->get('version','1.0.0'));   
    }

    /**
     * Get suppported languages
     *
     * @return array|null
     */
    public function getLanguages(): ?array
    {
        return $this->properties->get('languages',null);
    }

    /**
     * Set package as primary
     *
     * @return boolean
     */
    public function setPrimary(): bool
    {
        return true;
    }

    /**
     * Get package type
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return (empty($this->type) == true) ? $this->properties->get('package-type',null) : $this->type;
    }

    /**
     * Get install order
     *
     * @param mixed|null $default
     * @return mixed
     */
    public function getInstalOrder($default = null)
    {
        return $this->properties->get('install-order',$default);
    }

    /**
     * Return package name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return package properties
     *
     * @param boolean $full
     * @return CollectionInterface
     */
    public function getProperties(bool $full = false)
    {
        return $this->properties;
    }

    /**
     * Get require property
     *
     * @return CollectionInterface
     */
    public function getRequire()
    {
        $require = $this->properties->get('require',[]);

        return new Collection($require);
    }

    /**
     * Get package property
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getProperty(string $name, $default = null)
    {
        return $this->properties->get($name,$default);
    }

    /**
     * Validate package properties
     *
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * Install package.
     *
     * @param boolean|null $primary Primary package replaces routes or other params
     * @return mixed
     */
    public function install(?bool $primary = null)   
    {        
        return true;
    }

    /**
     * Run post install actions
     *     
     * @return boolean
     */
    public function postInstall(): bool
    {
        return true;
    }

    /**
     * UnInstall package
     *
     * @return bool
     */
    public function unInstall(): bool 
    {      
        return true;  
    }

    /**
     * Enable package
     *
     * @return bool
     */
    public function enable(): bool    
    {
        return false;
    }

    /**
     * Disable package
     *
     * @return bool
     */
    public function disable(): bool   
    {        
        return false;
    }  

    /**
     * Save package properties file 
     * 
     * @return bool
     */
    public function savePackageProperties(): bool 
    {         
        $fileName = $this->path . $this->getName() . DIRECTORY_SEPARATOR . 'arikaim-package.json';
        $data = $this->properties->toArray();
        if (File::isWritable($fileName) == false) {
            File::setWritable($fileName);
        }
        $result = File::write($fileName,Utils::jsonEncode($data));

        return $result;
    }
}
