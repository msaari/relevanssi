=== Relevanssi ===
Contributors: msaari
Donate link: http://www.mikkosaari.fi/relevanssi/
Tags: search, relevance, better search
Requires at least: 2.5
Tested up to: 3.0.1
Stable tag: 2.1.1

Relevanssi replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

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

Relevanssi can create custom search result snippets that show the part of the document where the
search hit was made. Relevanssi can also highlight the query terms in the search results.

Relevanssi can keep a log of user queries and display both most popular queries and recent queries
that got no hits.

Relevanssi supports the hidden input field `cat` to restrict searches to certain categories (or
tags, since those are pretty much the same). Just add a hidden input field named `cat` in your
search form and list the desired category or tag IDs in the `value` field - positive numbers
include those categories and tags, negative numbers exclude them. You can also set the
restriction from general plugin settings (and then override it in individual search forms with
the special field). This works with custom taxonomies as well, just replace `cat` with the name
of your taxonomy.

Relevanssi also supports custom post types.

With Relevanssi, you can also get Google-style "Did you mean?" suggestions, when search fails
to produce results. These suggestions are based on successful user searches.

In addition of post and page content (including tags and categories), Relevanssi can index
comments and pingbacks. It can also expand shortcodes in post content before indexing, so
that everything the user sees on the entry page will be included in the index.

Relevanssi owes a lot to [wpSearch](http://wordpress.org/extend/plugins/wpsearch/) by Kenny
Katzgrau.

== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

To update your installation, simply overwrite the old files with the new, activate the new
version and if the new version has changes in the indexing, rebuild the index.

= Using custom search results =
If you want to use the custom search results, make sure your search results template uses 
`the_excerpt()` to display the entries, because the plugin creates the custom snippet by replacing
the post excerpt.

If you're using a plugin that affects excerpts (like Advanced Excerpt), you may run into some
problems. For those cases, I've included the function `relevanssi_the_excerpt()`, which you can
use instead of `the_excerpt()`. It prints out the excerpt, but doesn't apply `wp_trim_excerpt()`
filters (it does apply `the_content()`, `the_excerpt()`, and `get_the_excerpt()` filters).

To avoid trouble, use the function like this:

`<?php if (function_exists('relevanssi_the_excerpt')) { relevanssi_the_excerpt(); }; ?>`

See Frequently Asked Questions for more instructions on what you can do with
Relevanssi.

= Uninstalling =
To uninstall the plugin, first click the "Remove plugin data" button on the plugin settins page
to remove options and database tables, then remove the plugin using the normal WordPress
plugin management tools.

== Frequently Asked Questions ==

= Sorting search results =

If you want something else than relevancy ranking, you can use orderby and order parameters. Orderby
accepts $post variable attributes and order can be "asc" or "desc". The most relevant attributes
here are most likely "post_date" and "comment_count".

If you want to give your users the ability to sort search results by date, you can just add a link
to http://www.yourblogdomain.com/?s=search-term&orderby=date&order=desc to your search result
page.

Order by relevance is either orderby=relevance or no orderby parameter at all.

= Displaying the relevance score =

Relevanssi stores the relevance score it uses to sort results in the $post variable. Just add
something like

echo $post->relevance_score

to your search results template inside a PHP code block to display the relevance score.

= Did you mean? suggestions =
To use Google-style "did you mean?" suggestions, first enable search query logging. The
suggestions are based on logged queries, so without good base of logged queries, the
suggestions will be odd and not very useful.

To use the suggestions, add the following line to your search result template, preferably
before the have_posts() check:

`<?php if (function_exists('relevanssi_didyoumean')) { relevanssi_didyoumean(get_search_query(), "<p>Did you mean: ", "?</p>", 5); }?>`

The first parameter passes the search term, the second is the text before the result,
the third is the text after the result and the number is the amount of search results
necessary to not show suggestions. With the default value of 5, suggestions are not
shown if the search returns more than 5 hits.

= Search shortcode =
Relevanssi also adds a shortcode to help making links to search results. That way users
can easily find more information about a given subject from your blog. The syntax is
simple:

`[search]John Doe[/search]`

This will make the text John Doe a link to search results for John Doe. In case you
want to link to some other search term than the anchor text (necessary in languages
like Finnish), you can use:

`[search term="John Doe"]Mr. John Doe[/search]`

Now the search will be for John Doe, but the anchor says Mr. John Doe.

One more parameter: setting `[search phrase="on"]` will wrap the search term in
quotation marks, making it a phrase. This can be useful in some cases.

= Restricting searches with taxonomies =

You can use taxonomies to restrict search results to posts and pages tagged with a certain 
taxonomy term. If you have a custom taxonomy of "People" and want to search entries tagged
"John" in this taxonomy, just use `?s=keyword&people=John` in the URL. You should be able to use
an input field in the search form to do this, as well - just name the input field with the name
of the taxonomy you want to use.

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
* Known issue: The most common cause of blank screens when indexing is the lack of the mbstring extension. Make sure it's installed.
* Known issue: In general, multiple Loops on the search page may cause surprising results. Please make sure the actual search results are the first loop.
* Known issue: Relevanssi doesn't necessarily play nice with plugins that modify the excerpt. If you're having problems, try using relevanssi_the_excerpt() instead of the_excerpt().
* Known issue: I know the plugin works with WP 2.5, but it loses some non-essential functionality. The shortcode stuff doesn't work with WP 2.5, which doesn't support shortcodes. Compatibility with older versions of WP hasn't been tested.
* Known issue: Custom post types and private posts is problematic - I'm using default 'read_private_*s' capability, which might not always work.
* Known issue: There are reported problems with custom posts combined with custom taxonomies, the taxonomy restriction doesn't necessarily work.
* To-do: The stop word list management needs small improvements.
* To-do: Improve the display of query logs. Any requests? What information would you like to see, what would be helpful?
* To-do: Option to set the number of search results returned.

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.

== Changelog ==

= 2.1.1 =
* "Did you mean" suggestions now work in blogs that are not in root directory.
* Early 2.1 downloads had faulty encodings. Update to make sure you've got a good file.

= 2.1 =
* An experimental "Did you mean" suggestion feature. Feedback is most welcome.
* Added a short code to facilitate adding links to search results.
* Fixed a small bug that in some cases caused MySQL errors.

= 2.0.3 =
* Fixed problems relating to the orderby parameter.

= 2.0.2 =
* Small bug fix: with private posts, sometimes correct amount of posts weren't displayed.

= 2.0.1 =
* Exclude posts/pages option wasn't saved on the options page. It works now.
* 2.0 included an unnecessary function that broke Relevanssi in WP 2.8.5. Fixed that.

= 2.0 =
* Post authors can now be indexed and searched. Author are indexed by their display name.
* In search results, $post->relevance_score variable will now contain the score of the search result.
* Comment authors are now included in the index, if comments are indexed.
* Search results can be sorted by any $post field and in any order, in addition of sorting them by relevancy.
* Private posts are indexed and displayed to the users capable of seeing them. This uses Role-Scoper plugin, if it's available, otherwise it goes by WordPress capabilities.
* Searches can be restricted with a taxonomy term (see FAQ for details).

= 1.9 =
* Excerpts are now better and will contain more search terms and not just the first hit.
* Fixed an error relating to shortcodes in excerpts.
* If comments are indexed, custom excerpts will show text from comments as well as post content.
* Custom post type posts are now indexed as they are edited. That didn't work before.
* Cleaned out more error notices.

= 1.8.1 =
* Sometimes empty ghost entries would appear in search results. No more.
* Added support for the WordPress' post_type argument to restrict search results to single post type.
* Relevanssi will now check for the presence of multibyte string functions and warn if they're missing.
* The category indexing option checkbox didn't work. It's now fixed.
* Small fix in the way punctuation is removed.
* Added a new indexing option to index all public post types.

= 1.8 =
* Fixed lots of error notices that popped up when E_NOTICE was on. Sorry about those.
* Custom post types can now be indexed if wanted. Default behaviour is to index all post types (posts, pages and custom types).
* Custom taxonomies can also be indexed in addition to standard post tags. Default behaviour is to index nothing. If somebody knows a way to list all custom taxonomies, that information would be appreciated.

= 1.7.3 =
* Small bug fix: code that created database indexes was broken. Say "ALTER TABLE `wp_relevanssi` ADD INDEX (doc)" and "ALTER TABLE `wp_relevanssi` ADD INDEX (term)" to your MySQL db to fix this for an existing installation.

= 1.7.2 =
* Small bug fix: public posts that are changed to private are now removed from index (password protected posts remain in index).
* An Italian translation is now included (thanks to Alessandro Fiorotto).

= 1.7.1 =
* Small fix: the hidden variable cat now accepts negative category and tag ids. Negative categories and tags are excluded in search. Mixing inclusion and exclusion is possible.

= 1.7 =
* Major bug fix: Relevanssi doesn't kill other post loops on the search result page anymore. Please let me know if Relevanssi feels too slow after the update.
* Post categories can now be indexed.

= 1.6 =
* Relevanssi is now able to expand shortcodes before indexing to include shortcode content to the index.
* Fixed a bug related to indexing, where tag stripping didn't work quite as expected.

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
* When indexing, HTML tags and `[shortcodes]` are removed.
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