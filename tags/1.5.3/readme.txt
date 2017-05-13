=== Relevanssi ===
Contributors: msaari
Donate link: http://www.mikkosaari.fi/relevanssi/
Tags: search, relevance
Requires at least: 2.5
Tested up to: 2.8.4
Stable tag: 1.5.3

Relevanssi replaces the basic WordPress search with a partial-match search that sorts the results based on relevance.

== Description ==

Relevanssi replaces the basic WordPress search with a partial-match search that sorts the results
based on relevance. It is a partial match search, so if user inputs several search terms, the
search will find all documents that match even one term, ranking highest those documents that match
all search terms.

Relevanssi does some fuzzy matching too, so if user searches for something that doesn't produce
any results, Relevanssi will look for similar terms. Strict phrases using quotation marks
(like "search phrase") are supported.

The matching is based on basic tf * idf weighing, with some extra features added like a boost for
words that appear in titles.

In general the plugin doesn't affect the display of search results at all - that is left for the 
search result template to decide. However, if the option is set, Relevanssi will create custom
search result snippets that show the part of the document where the search hit was made. Relevanssi
can also highlight the query terms in the search results.

Relevanssi can keep a log of user queries and display both most popular queries and recent queries
that got no hits. The logging is a new feature that will be refined later.

Relevanssi supports the hidden input field `cat` to restrict searches to certain categories (or
tags, since those are pretty much the same). Just add a hidden input field named `cat` in your
search form and list the desired category or tag IDs in the `value` field. You can also set the
description from general plugin settings (and then override it in individual search forms with
the special field).

Relevanssi owes a lot to [wpSearch](http://wordpress.org/extend/plugins/wpsearch/) by Kenny
Katzgrau.

I know the plugin works with WP 2.5, but it loses some non-essential functionality (mostly
because `strip_shortcodes()` isn't supported. Compatibility with older versions of WP hasn't
been tested.

== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

To update your installation, simply overwrite the old files with the new, activate the new
version and if the new version has changes in the indexing, rebuild the index.

If you want to use the custom search results, make sure your search results template uses 
`the_excerpt()` to display the entries, because the plugin creates the custom snippet by replacing
the post excerpt.

If you're using a plugin that affects excerpts (like Advanced Excerpt), you may run into some
problems. For those cases, I've included the function `relevanssi_the_excerpt()`, which you can
use instead of `the_excerpt()`. It prints out the excerpt, but doesn't apply `wp_trim_excerpt()`
filters (it does apply `the_content()`, `the_excerpt()`, and `get_the_excerpt()` filters).

To avoid trouble, use the function like this:

`<?php if (function_exists('relevanssi_the_excerpt')) { relevanssi_the_excerpt(); }; ?>`

To uninstall the plugin, first click the "Remove plugin data" button on the plugin settins page
to remove options and database tables, then remove the plugin using the normal WordPress
plugin management tools.

== Frequently Asked Questions ==

= What is tf * idf weighing? =

It's the basic weighing scheme used in information retrieval. Tf stands for *term frequency*
while idf is *inverted document frequency*. Term frequency is simply the number of times the term
appears in a document, while document frequency is the number of documents in the database where
the term appears.

Thus, the weight of the word for a document increases the more often it appears in the document and
the less often it appears in other documents.

= What are stop words? =

Each document database is full of useless words. All the little words that appear in just about
every document are completely useless for information retrieval purposes. Basically, their
inverted document frequency is really low, so they never have much power in matching. Also,
removing those words helps to make the index smaller and searching faster.

== Known issues and To-do's ==
* Known issue: Relevanssi doesn't play nice with widgets that display recent posts. Right now it makes them disappear. Help with this problem would be most welcome.
* Known issue: In general, multiple Loops on the search page may cause surprising results, and please make sure the actual search results are the first loop.
* Known issue: Relevanssi doesn't necessarily play nice with plugins that modify the excerpt. If you're having problems, try using relevanssi_the_excerpt() instead of the_excerpt().
* To-do: The stop word list management needs small improvements.
* To-do: Improve the display of query logs. Any requests? What information would you like to see, what would be helpful?

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.

== Changelog ==

= 1.5.3 =
* Added a way to uninstall the plugin.
* A French translation is now included (thanks to Jean-Michel Meyer).

= 1.5.2 =
* Fixed a small typo in the code, tag and comment hit count didn't work in the breakdown. If you don't use the breakdown feature, updating is not necessary.

= 1.5.1 =
* User interface update, small changes to make the plugin easier to use.
* Fixed a small bug that sometimes causes "Empty haystack" warnings.

= 1.5 =
* Comments can now be indexed and searched (thanks to Cristian Damm).
* Tags can also be indexed (thanks to Cristian Damm).
* Search term hits in the titles can be highlighted in search results (thanks to Cristian Damm).
* When using custom excerpts, it's possible to add extra information on where the hits were made.
* Fuzzy matching is now user-adjustable.
* UTF-8 support is now better (thanks to Marcus Dalgren).

= 1.4.4 =
* Added an option to exclude posts or pages from search results. This feature was requested and provided by Cristian Damm.

= 1.4.3 =
* Indexing of custom fields is now possible. Just add a list of custom field names you want to include in the index on the settings page and re-index.

= 1.4.2 =
* Users can search for specific phrases by wrapping the phase with "quotes".
* Fixed a bug that caused broken HTML in some cases of highlighted search results (search term matches in highlighting HTML tags were being highlighted).
* Improved punctuation removal. This change requires reindexing the whole database.

= 1.4.1 =
* Fixed a bug that caused empty search snippets when using word-based snippets.
* Improved support for WP 2.5.
* Added an option to exclude categories and tags from search results.
* Added an option to index only posts or pages.
* Added French stopwords.

= 1.4 =
* Added an option to restrict searches to certain categories or tags, either by plugin option or hidden input field in the search form.
* The contents of `<script>` and other such tags are now removed from excerpts.
* When indexing, HTML tags and `[quicktags]` are removed.
* Digits are no longer removed from terms. Re-index database to get them indexed.
* Wrapped the output of `relevanssi_the_excerpt()` in <p> tags.
* Stopwords are no longer removed from search queries.
* Search result snippet length can now be determined in characters or whole words.

= 1.3.3 =
* Small bug fixes, removed the error message caused by a query that is all stop words.
* Content and excerpt filters are now applied to excerpts created by Relevanssi.
* Default highlight CSS class has a unique name, `search-results` was already used by WordPress.

= 1.3.2 =
* Quicktags are now stripped from custom-created excerpts.
* Added a function `relevanssi_the_excerpt()', which prints out the excerpt without triggering `wp_trim_excerpt()` filters.

= 1.3.1 =
* Another bug fix release.

= 1.3 =
* New query logging feature. Any feedback on query log display features would be welcome: what information you want to see?
* Added a CSS class option for search term highlighting.
* Fixed a bug in the search result excerpt generation code that caused endless loops with certain search terms.

= 1.2 =
* Added new features to display custom search result snippets and highlight the search terms in the results.

= 1.1.3 =
* Fixed a small bug, made internationalization possible (translations are welcome!).

= 1.1.2 =
* English stopword file had a problem, which is now fixed.

= 1.1.1 =
* Fixed a stupid bug introduced in the previous update. Remember always to test your code before sending files to repository!

= 1.1 =
* Fixes the problem with pages in search results.

= 1.0 =
* First published version.