## Git Pages ##

This is a small utility to server markdown pages directly from a GitHub repository or local folder.

To install just place the `/assets/` folder, the `.htaccess` file and the `index.php` file to a folder or document roor in your web server.

Open and edit the `config.php` inside the `/assets/` folder. Change the variable `$config['files']` to point to a url like this:

```
$config['files'] = 'https://raw.githubusercontent.com/ctkjose/gitpages/master/docs/';
```
