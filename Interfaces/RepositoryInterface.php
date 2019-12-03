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
 * Repositorydriver interface
 */
interface RepositoryInterface 
{  
    /**
     * Return true if repository is private
     *
     * @return boolean
     */
    public function isPrivate();

    /**
     * Download package
     *
     * @return bool
     */
    public function download($version = null);

    /**
     * Get package last version
     *
     * @return string|null
     */
    public function getLastVersion();

    /**
     * Get package name
     *
     * @return string
     */
    public function getPackageName();

    /**
     * Get repository name
     *
     * @return string
     */
    public function getRepositoryName();

    /**
     * Install repository
     *
     * @param string|null $version
     * @return boolean
     */
    public function install($version = null);

    /**
     * Get repository url
     *
     * @return string
     */
    public function getRepositoryUrl();
}
