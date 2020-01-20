<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Packages\Interfaces;

/**
 * Package Registry Interface
 */
interface PackageRegistryInterface 
{  
    /**
     * Get package
     * 
     * @param string $name
     * @return array|false
     */
    public function getPackage($name);

    /**
     * Add package
     * 
     * @param string $name
     * @param array $data
     * @return boolean
     */
    public function addPackage($name, array $data);

    /**
     * Remove Package
     * 
     * @param string $name
     * @return boolean
     */
    public function removePackage($name);

    /**
     * Get package list
     *
     * @param array $filter
     * @return array
    */
    public function getPackagesList($filter = []);

    /**
     * Return true if package is installed
     *
     * @param string $name
     * @return boolean
     */
    public function hasPackage($name);

    /**
     * Set package status
     *
     * @param string $name
     * @param integer $status
     * @return boolean
    */
    public function setPackageStatus($name, $status);

    /**
     * Get package status
     *
     * @param string $name
     * @return integer
    */
    public function getPackageStatus($name);

    /**
     * Set package as primary
     *
     * @param string $name
     * @return boolean
    */
    public function setPrimary($name);

    /**
     * Return true if package is primary.
     *  
     * @param string $name
     * @return boolean
    */
    public function isPrimary($name);
}
