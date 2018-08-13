<?php

namespace Core;

class Load
{
    /**
     * 类名映射
     * @var array
     */
    protected static $classMap = [];

    /**
     * 类别名
     * @var array
     */
    protected static $classAlias = [];

    /**
     * psr-4
     * @var array
     */
    private static $prefixLengthsPsr4 = [];
    private static $prefixDirsPsr4 = [];
    private static $fallbackDirsPsr4 = [];

    /**
     * psr-0
     * @var array
     */
    private static $prefixesPsr0 = [];
    private static $fallbackDirsPsr0 = [];

    /**
     * 加载的文件
     * @var array
     */
    private static $files = [];

    /**
     * composer路径
     * @var string
     */
    private static $composerPath;

    /**
     * 注册自动加载机制
     * @param string $autoload
     */
    public static function register($autoload = '')
    {
        // 自动加载
        spl_autoload_register($autoload ?: "Core\Library\Load::autoload", true, true);

        $rootPath = self::getRootPath();

        self::$composerPath = $rootPath . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR;

        // 加载composer
        if (is_dir(self::$composerPath)) {
            if (is_file(self::$composerPath . 'autoload_static.php')) {
                require self::$composerPath . 'autoload_static.php';

                // 获取已定义类, 由于刚刚加载了composer, 所以array_pop拿到的是composer的类
                $declaredClass = get_declared_classes();
                $composerClass = array_pop($declaredClass);

                foreach (['prefixLengthsPsr4', 'prefixDirsPsr4', 'fallbackDirsPsr4', 'prefixesPsr0', 'fallbackDirsPsr0', 'classMap', 'files'] as $attr) {
                    if (property_exists($composerClass, $attr)) {
                        self::${$attr} = $composerClass::${$attr};
                    }
                }
            } else {
                self::registerComposerLoader(self::$composerPath);
            }
        }

        // 注册命名空间定义
        self::addNamespace([
            'Core' => dirname(__DIR__),
        ]);

        if (is_file($rootPath . 'runtime' . DIRECTORY_SEPARATOR . 'classmap.php')) {
            self::addClassMap(__include_file($rootPath . 'runtime' . DIRECTORY_SEPARATOR . 'classmap.php'));
        }

        // 自动加载extend目录
        self::addAutoLoadDir($rootPath . 'extend');
    }

    /**
     * 自动加载
     * @param $class
     * @return bool
     */
    public static function autoload($class)
    {
        if (isset(self::$classAlias[$class])) {
            return class_alias(self::$classAlias[$class], $class);
        }

        if ($file = self::findFile($class)) {

            // win 环境下需要严格区分大小写
            if (strpos(PHP_OS, 'WIN') !== false &&
                pathinfo($file, PATHINFO_FILENAME) != pathinfo(realpath($file), PATHINFO_FILENAME)) {
                return false;
            }

            __include_file($file);
            return true;
        }
    }

    /**
     * 注册类 别名
     * @param $alias
     * @param null $class
     */
    public static function addClassAlias($alias, $class = null): void
    {
        if (is_array($alias)) {
            self::$classAlias = array_merge(self::$classAlias, $alias);
        } else {
            self::$classAlias[$alias] = $class;
        }
    }

    /**
     * 获取应用根目录 TODO: 这个函数还有很大的问题
     * @return string
     */
    public static function getRootPath()
    {
        if ('cli' == PHP_SAPI) {
            $scriptName = realpath($_SERVER['argv'][0]);
        } else {
            $scriptName = $_SERVER['SCRIPT_FILENAME'];
        }

        $path = realpath(dirname($scriptName));

        return $path . DIRECTORY_SEPARATOR;
    }

    /**
     * 注册classMap
     * @param $class
     * @param string $map
     */
    public static function addClassMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$classMap = array_merge(self::$classMap, $class);
        } else {
            self::$classMap[$class] = $map;
        }
    }

    /**
     * 注册命名空间
     * @param $namespace
     * @param string $path
     */
    public static function addNamespace($namespace, $path = '')
    {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $paths) {
                self::addPsr4($prefix . '\\', rtrim($paths, DIRECTORY_SEPARATOR), true);
            }
        } else {
            self::addPsr4($namespace . '\\', rtrim($path, DIRECTORY_SEPARATOR), true);
        }
    }

    /**
     * 注册自动加载类库目录
     * @param $path
     */
    public static function addAutoLoadDir($path)
    {
        self::$fallbackDirsPsr4[] = $path;
    }

    public static function registerComposerLoader($composerPath)
    {
        if (is_file($composerPath . 'autoload_namespaces.php')) {
            $map = require $composerPath . 'autoload_namespaces.php';
            foreach ($map as $namespace => $path) {
                self::addPsr0($namespace, $path);
            }
        }

        if (is_file($composerPath . 'autoload_psr4.php')) {
            $map = require $composerPath . 'autoload_psr4.php';
            foreach ($map as $namespace => $path) {
                self::addPsr4($namespace, $path);
            }
        }

        if (is_file($composerPath . 'autoload_classmap.php')) {
            $classMap = require $composerPath . 'autoload_classmap.php';
            if ($classMap) {
                self::addClassMap($classMap);
            }
        }

        if (is_file($composerPath . 'autoload_files.php')) {
            self::$files = require $composerPath . 'autoload_files.php';
        }
    }

    private static function findFile($class)
    {
        if (!empty(self::$classMap[$class])) {
            return self::$classMap[$class];
        }

    }

    private static function addPsr0($prefix, $paths, $prepend = false)
    {

    }

    private static function addPsr4($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            if ($prepend) {
                self::$fallbackDirsPsr4 = array_merge(
                    (array) $paths,
                    self::$fallbackDirsPsr4
                );
            } else {
                self::$fallbackDirsPsr4 = array_merge(
                    self::$fallbackDirsPsr4,
                    (array) $paths
                );
            }
        } elseif (!isset(self::$fallbackDirsPsr4[$prefix])) {


        }
    }
}

/**
 *
 *
 * @param $file
 * @return mixed
 */
function __include_file($file)
{
    return include $file;
}

function __require_file($file)
{
    return require $file;
}