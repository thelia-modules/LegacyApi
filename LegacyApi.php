<?php

namespace LegacyApi;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;

class LegacyApi extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'legacyapi';


    public function postActivation(ConnectionInterface $con = null): void
    {
        if (! self::getConfigValue('is_initialized')) {
            $database = new Database($con);

            $database->insertSql(null, [__DIR__ . '/Config/TheliaMain.sql']);

            self::setConfigValue('is_initialized', true);
        }
    }

    /**
     * Defines how services are loaded in your modules
     *
     * @param ServicesConfigurator $servicesConfigurator
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }

    /**
     * Execute sql files in Config/update/ folder named with module version (ex: 1.0.1.sql).
     *
     * @param $currentVersion
     * @param $newVersion
     * @param ConnectionInterface $con
     */
    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        $updateDir = __DIR__.DS.'Config'.DS.'update';

        if (! is_dir($updateDir)) {
            return;
        }

        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in($updateDir);

        $database = new Database($con);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }
}
