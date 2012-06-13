# Wordpress Plugin: GitHub and BitBucket Project Lister

This is a Wordpress plugin that will list your open source projects from
github or bitbucket (or both). You can have them inserted in a standard post
or page, or have them inserted in your sidebar. It's in use at
[codefury.net](http://codefury.net/projects).

So in short, just placing:

`{{github:username,bitbucket:username,sortby:watchers,sortdir:desc}}`

in a post would pull your projects from both github and bitbucket, merge them into one list,
sort them in descending order by watchers, cache it, and place the list in your post. More below!

## Maintainer

This project is maintained by [Kenny Katzgrau](http://codefury.net) ([@\_kennyk\_](http://twitter.com/_kennyk_)). It
was supposed to be a quick project, but I got really into it.

## Installation

Download a [zip](https://github.com/katzgrau/wordpress-github/zipball/master) extract
it into `/wp-content/plugins/`. Rename the extracter folder to wordpress-github.

Should you should now have a `/wp-content/plugins/wordpress-github` directory.

Now log into the WP admin panel and activate the plugin. Go to the 'GitHub/BitBucket'
settings page and do your thing (if you'd like, the plugin doesn't _need_ any configuration).

## Examples

### Insert in a page or post

Once you install the plug-in (see below), create a page (or open one) where you'd like
your projects to be listed. On my personal website, this would be my '[Projects](http://codefury.net/projects)'
page, which I was tired of updating :)

At the point where you'd like to insert your projects, put:

`{{github:your_username}}`

So for me, that was `{{github:katzgrau}}`. But if you'd like to include projects
from multiple sources, you can do something like this:

`{{github:username,bitbucket:username}}`

So for me, that was `{{github:katzgrau,bitbucket:katzgrau}}`

### Insert in the sidebar (or some other widgety place)

This plugin also comes with a widget. Go to your Wordpress widgets page, and
activate the 'GitHub Projects' widget.

The 'Title' field is label for the widget listing. You might want to just put "Projects".

For 'Sources', put in a project string like: `github:your_username`. I use something like:

`github:katzgrau,bitbucket:katzgrau`

## Customization and Sorting

You can edit _how_ projects are listed on the settings page. Look for the
'Github/BitBucket' settings link in the Wordpress admin panel.

Customization options include formatting and sorting. For example, if you want projects
with the most watchers listed first, do:

`{{github:username,sortby:watchers,sortdir:desc}`

You can also sort alphabetically:

`{{github:username,sortby:name,sortdir:asc}}`

Lastly, you can sort by the last update or push. **Note:** This is currently supported by github's API only.

`{{github:username,bitbucket:username,sortby:updated,sortdir:desc}}`

## Other details

The plugin caches your projects for 1 hour. If you'd like to configure this,
 edit `WPGH_Core::$_cacheExpiration` in `wordpress-github.php`. This might be
 configurable via the admin interface later.

Caching is done via another project of mine, [WP-Easy-Cache](https://github.com/katzgrau/WP-Easy-Cache).

## Special Thanks

Special thanks to contributors, including:

* [mhutchin](http://github.com/mhutchin)
* [benallfree](http://github.com/benallfree)
* [jmccrohan](http://github.com/jmccrohan)
* [marcwickenden](http://offensivecoder.com/)

## License (MIT)

Copyright (C) 2012 by Kenny Katzgrau <katzgrau@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
