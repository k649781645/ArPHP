<?php
/**
 * class Db default classPDO
 *
 * @author assnr <ycassnr@gmail.com>
 */

/**
 * abstract Db class.
 */
class ArFile extends ArCache {

    public $cachePath;

    // static protected $config = array();

    static public function init($config = array(), $class = __CLASS__)
    {
        $obj = parent::init($config, $class);

        $obj->cachePath = empty(self::$config['cachePath']) ? arCfg('PATH.CACHE') : self::$config['cachePath'];

        if(!is_dir($obj->cachePath))
            mkdir($obj->cachePath, 0777, true);

        return $obj;

    }

    public function cacheFile($key)
    {
        return $this->cachePath . $this->generateUniqueKey($key) . '.cache';

    }

    public function get($key)
    {
        $cacheFile = $this->cacheFile($key);

        if (is_file($cacheFile)) :
            if ($this->checkExpire($cacheFile)) :
                $data = null;
                unlink($cacheFile);
            endif;
            $data = $this->decrypt(file_get_contents($cacheFile, false, null, 10));
        else :
            $data = null;
        endif;

        return $data;

    }

    public function set($key, $value, $expire = 0)
    {
        if ($expire == 0) :
            $timeExpire = '0000000000';
        else :
            $timeExpire = time() + $expire;
        endif;

        return file_put_contents($this->cacheFile($key), $timeExpire . $this->encrypt($value));

    }

    public function checkExpire($file)
    {
        $timeExpire = file_get_contents($file, false, null, 0, 10);

        return $timeExpire == 0 ? false : ($timeExpire < time());

    }

    public function flush($force = false)
    {
        $source = opendir($this->cachePath);

        while ($file = readdir($source)) :
            $file = $this->cachePath . $file;
            if (is_file($file)) :
                if ($force || $this->checkExpire($file))
                    unlink($file);
            endif;
        endwhile;

        closedir($source);

    }

}