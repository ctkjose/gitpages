## Markdown Butler ##

This is a small php utility to serve markdown pages directly from a GitHub repository or local folder.

To install just place the `/assets/` folder, the `.htaccess` file and the `index.php` file to a folder or document root in your web server.

Open and edit the `config.php` inside the `/assets/` folder. Change the variable `$config['files']` to point to a url like this:

```php
$config['files'] = 'https://raw.githubusercontent.com/ctkjose/gitpages/master/docs/';
```

To access a page just point your browser to the URL where you installed gitpages and add the name of the markdown file like this:

```
http://example.com/gitpages/README.md
```


### Costumize the pages served ###

You can edit the default `template.html` found in the `/assets/` folder. You may also want to play with the `site.css`.

Source code highlighting is done with prism.js, for more info go ahead and check [Prism](http://prismjs.com/index.html) out.

### Markdown support ###

This utility aims to supports the GitHub flavor of Markdown. While it supports all the basic styling of GitHub not everything is supported.


Today we have support for:
```
	Headings using #. Like: ## Heading ##

	Italic emphasis with _underscores_ and *asterisks*

	Strong/bold emphasis, with **asterisks** or __underscores__.

	Strikethrough with two tildes. ~~Scratch this.~~

	Tables

	Inline links.

	Inline code with single quotes.

	Blockquote with >
```