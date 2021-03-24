miniroute
=====

### Install

If you have Composer, just include miniroute as a project dependency in your `composer.json`. If you don't just install it by downloading the .ZIP file and extracting it to your project directory.

.htaccess(Apache):

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
