=== Plugin Name ===
Contributors: Kenny Katzgrau
Tags: github,bitbucket,projects,project,list
Requires at least: 3.0
Tested up to: 3.0.5
Stable tag: 1.0.3

This is a Wordpress plugin that will list your open source projects from
github or bitbucket in-page or via sidebar.

== Description ==

This is a Wordpress plugin that will list your open source projects from
github or bitbucket (or both). You can have them inserted in a standard post
or page, or have them inserted in your sidebar. It's in use at
[codefury.net](http://codefury.net/projects).

So in short, just placing:

`{{github:username,bitbucket:username,sortby:watchers,sortdir:desc}}`

in a post would pull your projects from both github and bitbucket, merge them into one list,
sort them in descending order by watchers, cache it, and place the list in your post.
More detail at: [the github repository](https://github.com/katzgrau/wordpress-github)

The plugin also comes packaged with a widget in case you'd just like to
list your projects in the sidebar. Full details are on the settings page.

Follow [@_kennyk_](http://twitter.com/_kennyk_) for updates, or visit
[codefury.net](http://codefury.net)

== Installation ==

There aren't really any special instructions for installing this plug-in. Once
installed, be sure to go to the 'GitHub/BitBucket' settings page if you'd
like to customize your template.