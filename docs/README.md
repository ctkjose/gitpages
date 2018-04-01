## Git Pages ##

This is a small utility to server markdown pages directly from a GitHub repository or local folder.

To install just place the `/assets/` folder, the `.htaccess` file and the `index.php` file to a folder or document roor in your web server.

Open and edit the `config.php` inside the `/assets/` folder. Change the variable `$config['files']` to point to a url like this:

```
$config['files'] = 'https://raw.githubusercontent.com/ctkjose/gitpages/master/docs/';
```

To access a page just point your browser to the URL where you install gitpages and add the name of the markdown file like this:

```php
http://example.com/gitpages/README.md
```


### Costumize GitPages ###

You can edit the default `template.html` found in the `/assets/` folder. You may also want to play with the `site.css```.

Source code highlighting is done with prism.js, for more info go ahead and check [Prism](http://prismjs.com/index.html) out.
