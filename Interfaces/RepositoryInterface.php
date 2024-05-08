<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     Packages
*/
namespace Arikaim\Core\Packages\Interfaces;

/**
 * Repositorydriver interface
 */
interface RepositoryInterface 
{  
    /**
     * Get access token for private repo
     *
     * @return string|null
     */
    public function getAccessToken(): ?string;

    /**
     * Download package
     *
     * @param string|null $version
     * @return bool
     */
    public function download(?string $version = null): bool;

    /**
     * Get package version
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get package name
     *
     * @return string
     */
    public function getPackageName(): string;

    /**
     * Get package type
     *
     * @return string
     */
    public function getPackageType(): string;

    /**
     * Install repository
     *
     * @param string|null $version
     * @return boolean
     */
    public function install(?string $version = null): bool;
}
