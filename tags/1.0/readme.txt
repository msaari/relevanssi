=== Relevanssi ===
Contributors: msaari
Donate link: http://www.mikkosaari.fi/relevanssi/
Tags: search, relevance
Requires at least: 2.6.5
Tested up to: 2.8
Stable tag: 1.0

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

Relevanssi owes a lot to [wpSearch](http://wordpress.org/extend/plugins/wpsearch/) by Kenny
Katzgrau.

The plugin might work with WordPress versions prior to 2.6.5 - that's just oldest release I've
tried. I'd guess 2.0 or 2.2 is the actual limit.

== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

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
* For some reason, pages don't work properly in the search results. If the blog is using pretty
permalinks, the pages will use the basic http://www.example.com/?p=1 style links.
* When using WP 2.6.5, the plugin causes a white bar to appear on top of the screen. I have no
idea what causes it, I only know the plugin doesn't. The problem doesn't exist in 2.8.
* The stop word list management needs small improvements.

== Changelog ==

= 1.0 =
* First published version.