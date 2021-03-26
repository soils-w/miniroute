miniroute
=====

### Install

```
composer require soils-w/miniroute
```

### Example

```
use Miniroute\Route;

Route::get('', 'app\controller@Index\index');
Route::get('/hei',function(){
    echo "单身的程序员们啊，找老婆就联系我吧，哈哈哈哈哈哈";
});

 Route::dispatch();
```
控制器名称使用驼峰式，首字母大写（下划线格式会自动转化为驼峰式）
方法名用小写加下划线

.htaccess(Apache)：

```
RewriteEngine On
RewriteBase /

# Allow any files or directories that exist to be displayed directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^(.*)$ index.php?$1 [QSA,L]
```

.htaccess(Nginx):

```
rewrite ^/(.*)/$ /$1 redirect;

if (!-e $request_filename){
	rewrite ^(.*)$ /index.php break;
}

```
