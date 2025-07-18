<?php

namespace LegacyApi\Model;

use LegacyApi\Model\Base\Api as BaseApi;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Core\Security\Role\Role;
use Thelia\Core\Security\User\UserInterface;
use Thelia\Core\Security\User\UserPermissionsTrait;
use Thelia\Model\Lang;
use Thelia\Tools\Password;

/**
 * Skeleton subclass for representing a row from the 'api' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 */
class Api extends BaseApi implements UserInterface
{
    use UserPermissionsTrait;

    public function preInsert(ConnectionInterface $con = null)
    {
        if (null === $this->getApiKey()) {
            $this->setApiKey(Password::generateHexaRandom(25));
        }

        $this->generateSecureKey();

        return true;
    }

    public function postDelete(ConnectionInterface $con = null)
    {
        $fs = new Filesystem();
        $fs->remove($this->getKeyDir(). $this->getApiKey() . '.key');
    }

    private function getKeyDir()
    {
        $dir =__DIR__.DS.'..'.DS.'Config'.DS.'keys'.DS;

        if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        return $dir;
    }

    private function generateSecureKey()
    {
        $fs = new Filesystem();
        $dir = $this->getKeyDir();

        $file = $dir . $this->getApiKey().".key";
        $fs->touch($file);
        file_put_contents($file, Password::generateHexaRandom(48));
        $fs->chmod($file, 0600);
    }

    public function getSecureKey()
    {
        return file_get_contents($this->getKeyDir() . $this->getApiKey() . '.key');
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('USER');
     * }
     * </code>
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return [new Role('API')];
    }

    /**
     * Return the user unique name
     */
    public function getUsername()
    {
        throw new \RuntimeException("getUsername is not implemented");
    }

    /**
     * Return the user encoded password
     */
    public function getPassword()
    {
        throw new \RuntimeException("getPassword is not implemented");
    }

    /**
     * Check a string against a the user password
     */
    public function checkPassword($password)
    {
        throw new \RuntimeException("checkPassword is not implemented");
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     *
     * @return void
     */
    public function eraseCredentials(): void
    {
        throw new \RuntimeException("eraseCredentials is not implemented");
    }

    /**
     * return the user token (used by remember me authnetication system)
     */
    public function getToken()
    {
        throw new \RuntimeException("getToken is not implemented");
    }

    /**
     * Set a token in the user data (used by remember me authnetication system)
     */
    public function setToken($token)
    {
        throw new \RuntimeException("setToken is not implemented");
    }

    /**
     * return the user serial  (used by remember me authnetication system)
     */
    public function getSerial()
    {
        throw new \RuntimeException("getSerial is not implemented");
    }

    /**
     * Set a serial number int the user data  (used by remember me authnetication system)
     */
    public function setSerial($serial)
    {
        throw new \RuntimeException("setSerial is not implemented");
    }

    public function getLocale()
    {
        return Lang::getDefaultLanguage()->getLocale();
    }
}
