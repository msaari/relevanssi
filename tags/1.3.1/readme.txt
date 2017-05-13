=== Relevanssi ===
Contributors: msaari
Donate link: http://www.mikkosaari.fi/relevanssi/
Tags: search, relevance
Requires at least: 2.6.5
Tested up to: 2.8
Stable tag: 1.3.1

Relevanssi replaces the basic WordPress search with a partial-match search that sorts the results based on relevance.

== Description ==

Relevanssi replaces the basic WordPress search with a partial-match search that sorts the results
based on relevance. It is a partial match search, so if user inputs several search terms, the
search will find all documents that match even one term, ranking highest those documents that match
all search terms.

Relevanssi does some fuzzy matching too, so if user searches for something that doesn't produce
any results, Relevanssi will look for similar terms.

The matching is based on basic tf * idf weighing, with some extra features added like a boost for
words that appear in titles.

In general the plugin doesn't affect the display of search results at all - that is left for the 
search result template to decide. However, if the option is set, Relevanssi will create custom
search result snippets that show the part of the document where the search hit was made. Relevanssi
can also highlight the query terms in the search results.

Relevanssi can keep a log of user queries and display both most popular queries and recent queries
that got no hits. The logging is a new feature that will be refined later.

Relevanssi owes a lot to [wpSearch](http://wordpress.org/extend/plugins/wpsearch/) by Kenny
Katzgrau.

The plugin might work with WordPress versions prior to 2.6.5 - that's just oldest release I've
tried. I'd guess 2.0 or 2.2 is the actual limit.

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
* To-do: The stop word list management needs small improvements.
* To-do: Improve the display of query logs. Any requests? What information would you like to see, what would be helpful?

== Changelog ==

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