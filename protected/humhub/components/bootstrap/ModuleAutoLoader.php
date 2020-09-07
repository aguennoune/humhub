<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\components\bootstrap;

use humhub\components\Application;
use Yii;
use yii\base\BootstrapInterface;
use yii\helpers\FileHelper;

/**
 * ModuleAutoLoader automatically searches for config.php files in module folder an executes them.
 *
 * @author luke
 */
class ModuleAutoLoader implements BootstrapInterface
{
    const CACHE_ID = 'module_configs';
    const CONFIGURATION_FILE = 'config.php';

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     * @throws \yii\base\InvalidConfigException
     */
    public function bootstrap($app)
    {
        $modules = self::locateModules();
        Yii::$app->moduleManager->registerBulk($modules);
    }

    /**
     * Find available modules
     * @return array
     */
    public static function locateModules()
    {
        $modules = Yii::$app->cache->get(self::CACHE_ID);

        if ($modules === false || YII_DEBUG) {
            $modules = static::findModules(Yii::$app->params['moduleAutoloadPaths']);
            Yii::$app->cache->set(self::CACHE_ID, $modules);
        }

        return $modules;
    }

    /**
     * Find all modules with configured paths
     * @param array $paths
     * @return array
     */
    private static function findModules($paths)
    {
        $folders = [];
        foreach ($paths as $path) {
            $folders = array_merge($folders, self::findModulesByPath($path));
        }

        $modules = [];
        foreach ($folders as $folder) {
            try {
                /** @noinspection PhpIncludeInspection */
                $modules[$folder] = require $folder . DIRECTORY_SEPARATOR . self::CONFIGURATION_FILE;
            } catch (\Exception $e) {
                Yii::error($e);
            }
        }

        return $modules;
    }

    /**
     * Find all directories with a configuration file inside
     * @param string $path
     * @return array
     */
    private static function findModulesByPath($path)
    {
        $hasConfigurationFile = function ($path) {
            return is_file($path . DIRECTORY_SEPARATOR . self::CONFIGURATION_FILE);
        };

        try {
            return FileHelper::findDirectories(
                Yii::getAlias($path, true),
                ['filter' => $hasConfigurationFile, 'recursive' => false]
            );
        } catch (yii\base\InvalidArgumentException $e) {
            return [];
        }
    }
}
