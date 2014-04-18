<?php
/**
 * Ar for PHP .
 *
 * @author ycassnr<ycassnr@gmail.com>
 */

namespace Core;

/**
 * class Ar.
 */
class Ar {

    static private $_a = array();

    static private $_c = array();

    static private $_config = array();

    static public function init()
    {
        self::$_config = array_merge(
                Ar::import(CONFIG_PATH . 'default.config.php'),
                Ar::import(ROOT_PATH . 'CONF' . DS . 'public.config.php', true)
            );
        ArApp::run();

    }

    static public function setA($key, $val)
    {
        self::$_a[$key] = $val;

    }

    static public function getConfig($ckey = '', $rt = array())
    {
        if (empty($ckey)) :
            return self::$_config;
        else :
            if (isset(self::$_config[$ckey]))
                return self::$_config[$ckey];
            else 
                return $rt;
        endif;

    }

    static public function setConfig($ckey = '', $value = array())
    {
        if (!empty($ckey))
            self::$_config[$ckey] = $value;
        else
            self::$_config = $value;

    }

    static public function a($akey)
    {
        return isset(self::$_a[$akey]) ? self::$_a[$akey] : null;

    }

    static public function c($cname)
    {
        if (!isset(self::$_c[$cname])) :
            $cKey = strtolower($cname);

            $confC = self::getConfig('components');

            $cArr = explode('.', $cKey);

            $conf = self::getConfig(strtolower($cArr[0]));
            if (!empty($confC[$cArr[0]]) && !empty($confC[$cArr[0]]['config']))
                $config = $confC[$cArr[0]]['config'];
            else
                $config = array();
            self::setC($cname, $config);
        endif;

        return self::$_c[$cname];

    }

    static public function setC($component, $config = array())
    {
        $cKey = strtolower($component);

        $cArr = explode('.', $component);

        array_unshift($cArr, 'components');

        $cArr = array_map('ucfirst', $cArr);

        $className = 'Ar' . array_pop($cArr);

        $cArr[] = $className;

        $classFile = implode($cArr, '\\');

        if (!isset($_c[$cKey]))
            self::$_c[$cKey] = $classFile::init($config);

    }

    static public function autoLoader($class)
    {
        if (strpos($class, '\\') === false) :
            preg_match("#[A-Z]{1}[a-z0-9]+$#", $class, $match);
            $classFile = ROOT_PATH . Ar::getConfig('requestRoute')['m'] . DS . $match[0] . DS . $class . '.class.php';
        else :
            $classFile = FRAME_PATH . str_replace('\\', DS, $class) . '.class.php';
        endif;

        if (is_file($classFile))
            require_once $classFile;
        else
            throw new ArException('class : ' . $classFile . ' does not exist !');

        $m = self::getConfig('requestRoute');

        if (!empty($m['m'])) :

            $appMoudle = ROOT_PATH . $m['m'] . DS;

            $appConfigFile = $appMoudle . 'Conf' . DS . 'app.config.php';

            $appConfig = self::import($appConfigFile, true);

            if (is_array($appConfig)) :
                self::setConfig('', array_merge(self::getConfig(), $appConfig));
            endif;
        endif;

    }

    static public function import($path, $allowTry = false)
    {
        if (strpos($path, DS) === false)
            $fileName = str_replace(array('c.', 'ext.', 'app.', '.'), array('Controller.', 'Extensions.', rtrim(ROOT_PATH, DS) . '.', DS), $path) . '.class.php';
        else
            $fileName = $path;

        if (is_file($fileName)) :
            return require_once $fileName;
        else :
            if ($allowTry)
                return array();
            else
                throw new ArException('import not found file :' . $fileName);
        endif;

    }

    static public function exceptionHandler($e)
    {
        echo get_class($e) . ' : ' . $e->getMessage();

    }

    static public function errorHandler($errno, $errstr)
    {
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";

    }

    
}
