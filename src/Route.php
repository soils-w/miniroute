<?php

namespace Miniroute;

class Route
{
    protected static $config = array(
        // 默认控制器名
        'default_controller'    => 'Index',
        // 默认操作名
        'default_action'        => 'index',
        //路由规则
        'route_rule'            => array(),
        //伪静态后缀
        'url_html_suffix' => 'html',
        //控制器与路径中间的分隔符
        'base_path_separator' => '@',
        //控制器默认起始路径
        'base_path' => '',
        //控制器所在的目录
        'controller_path' => 'controller',
        //控制器后缀
        'controller_suffix' => 'php'
    );

    /**
     * @param $rule
     * @param $route
     * 以get方式调用路由规则
     */
    public static function get($rule, $route)
    {
        self::addRule($rule, $route, 'GET');
    }

    /**
     * @param $rule
     * @param $route
     * 以post方式调用路由规则
     */
    public static function post($rule, $route)
    {
        self::addRule($rule, $route, 'POST');
    }

    /**
     * @param $rule
     * @param $route
     * 以delete方式调用路由规则
     */
    public static function delete($rule, $route)
    {
        self::addRule($rule, $route, 'DELETE');
    }

    /**
     * @param $rule
     * @param $route
     * 以put方式调用路由规则
     */
    public static function put($rule, $route)
    {
        self::addRule($rule, $route, 'PUT');
    }

    /**
     * @param $rule
     * @param $route
     * 以head方式调用路由规则
     */
    public static function head($rule, $route)
    {
        self::addRule($rule, $route, 'HEAD');
    }

    /**
     * @param $rule
     * @param $route
     * 以options方式调用路由规则
     */
    public static function options($rule, $route)
    {
        self::addRule($rule, $route, 'OPTIONS');
    }

    /**
     * @param $rule
     * @param $route
     * 以options方式调用路由规则
     */
    public static function any($rule, $route)
    {
        self::addRule($rule, $route, '*');
    }

    /**
     * @param string $rule
     * @param $route
     * @param $method
     * @return array
     * 添加路由规则
     */
    public static function addRule( $rule, $route, $method)
    {
        if(0 !== strpos($rule,'/')) {
            $rule = '/'.$rule;
        }
        //判断有没有设置这个规则，没有就设置，有就pass
        $route_rule = self::$config['route_rule'];
        if(!isset($route_rule[$method][$rule])) {
            self::$config['route_rule'][$method][$rule] = $route;
        }
        return self::$config['route_rule'];
    }

    public static function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);//返回地址中的path
        if(false != strpos($uri, self::$config['url_html_suffix'])) {
            $uri = str_replace(self::$config['url_html_suffix'], '', $uri);
            $uri = rtrim($uri, '.');
        }
        $method = $_SERVER['REQUEST_METHOD'];

        //判断path 有没有设置对应的路由
        $route_rule = self::$config['route_rule'];
        $is_rule = false;
        if(!isset($route_rule[$method])) {
            $method = '*';//any方法
        }
        if(isset($route_rule[$method])) {
            $method_rule = $route_rule[$method];
            //判断get里面有没有设置这个对应的路由规则
            if(isset($method_rule[$uri])) {
                $is_rule = true;//走定义的路由规则

                $ruleuri = $method_rule[$uri];

                if(is_object($ruleuri)) {//判断是闭包函数
                    //回调闭包函数
                    call_user_func($ruleuri);
                } else {
                    self::exec($ruleuri);
                }
            }
        }

        //不走定义的路由规则
        if(!$is_rule) {
            self::exec($uri);
        }
    }

    /**
     * @param string $cmname
     * @return mixed
     * 执行控制器方法
     */
    public static function exec($cmname)
    {
        $info = self::get_path_c_m($cmname);
        $path = $info['path'];
        $controllername = $info['controller'];
        $methodname = $info['action'];

        if(!class_exists($path.$controllername)){ //控制器不存在
            header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
            echo "Uncaught Error: Class '".$path.$controllername."' not found";die();
        }

        $class = $path.$controllername;
        $controller = new $class();

        //判断控制器中的方法是否存在
        if(!method_exists($controller, $methodname)){
            header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
            echo "Fatal error: Uncaught Error: Call to undefined method ".$controller."::".$methodname."() ";die();
        }
        $controller->$methodname();

        return $controller;
    }

    /**
     * @param string $cmname
     * @return array
     * 获取控制器的路径 控制器名称 方法名
     */
    public static function get_path_c_m($cmname)
    {
        $base_path_separator = self::$config['base_path_separator']?self::$config['base_path_separator']:'@';
        $controller_path = self::$config['controller_path'];
        $default_controller = self::$config['default_controller'];
        $default_action = self::$config['default_action'];

        //判断有没有用到@，有用到就自己定义了全路径，比如app\controller@Index\index
        if(false !== strpos($cmname, $base_path_separator)) {
            $patharr = explode($base_path_separator, $cmname);
            if($patharr[0]) {
                $path = $patharr[0].DIRECTORY_SEPARATOR;
            }
            $cmname =  $patharr[1]?$patharr[1]:'';
        }

        if(!$cmname){//没有写控制器和方法名，走默认
            if(!$default_controller && !$default_action) {//没有默认控制器和方法
                header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
                echo "Uncaught Error: Class not found";die();
            }
            $cmname = $default_controller.'/'.$default_action;
        }
        //只有一个index的情况就是到默认控制器
        if(trim($cmname, '/') == 'index') {
            $cmname = $default_controller.'/'.$default_action;
        }

        $cmname = str_replace('/', '\\', $cmname);

        $cmarr = explode('\\', $cmname);
        $cmarr = array_values(array_filter($cmarr));

        $appname = '';
        //多应用
        if(count($cmarr) == 3) {
            $appname = $cmarr[0].DIRECTORY_SEPARATOR;
            $controllername = $cmarr[1];
            $actionname = $cmarr[2];
        } elseif(count($cmarr) == 2) {
            $controllername = $cmarr[0];
            $actionname = $cmarr[1];
        } else {
            header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
            echo "Uncaught Error: Class not found";die();
        }

        //开始处理路径
        if(isset($path)) {//指定的路由规则
            $path = $path.$appname;
        } else {
            if( is_dir($appname.$controller_path) ) {
                $path = $appname.$controller_path.DIRECTORY_SEPARATOR;
            }
        }
        if(!isset($path)) {
            $path = 'app'.DIRECTORY_SEPARATOR.$appname.$controller_path.DIRECTORY_SEPARATOR;
        }
        self::$config['base_path'] = $path;

        //控制器首字母大写，下划线转为驼峰
        $controllername = self::convertUnderline($controllername);
        //方法 驼峰转下划线
        $actionname = self::humpToLine($actionname);

        return array(
            'controller' => $controllername,
            'action' => $actionname,
            'path' => $path
        );
    }

    /**
     * @param $str
     * @param bool $isucfirst
     * @return string|string[]|null
     */
    public static function convertUnderline($str, $isucfirst = false)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        if($isucfirst) {
            return ucfirst($str);
        } else {
            return $str;
        }

    }

    /*
     * 驼峰转下划线
     */
    public static function humpToLine($str)
    {
        $str = str_replace("_", "", $str);
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return strtolower(ltrim($str, "_"));
    }
}