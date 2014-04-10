<?php
class ArRoute extends ArComponent {
    
    public function parse()
    {
        $requestUrl = $_SERVER['REQUEST_URI'];

        $phpSelf = $_SERVER['SCRIPT_NAME'];
        if (strpos($requestUrl, $phpSelf) !== false)
            $requestUrl = str_replace($phpSelf, '', $requestUrl);
        if (($pos = strpos($requestUrl, '?')) !== false)
            $requestUrl = substr($requestUrl, 0, $pos);


        if (($root = dirname($phpSelf)) != '/')
            $requestUrl = str_replace(trim($root, '/'), '', $requestUrl);

        $requestUrl = trim($requestUrl, '/');
        $pathArr = explode('/', $requestUrl);

        $c = array_shift($pathArr);
        $a = array_shift($pathArr);

        while ($gkey = array_shift($pathArr)) :
            $_GET[$gkey] = array_shift($pathArr);
        endwhile;
        return array('c' => $c, 'a' => $a);

    }

}