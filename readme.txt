=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search
Requires at least: 4.9
Tested up to: 5.3.2
Requires PHP: 5.6
Stable tag: 4.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Relevanssi replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

== Description ==

Relevanssi replaces the standard WordPress search with a better search engine, with lots of features and configurable options. You'll get better results, better presentation of results - your users will thank you.

This is the free version of Relevanssi. There's also Relevanssi Premium, which has added features. For more information about Premium, see [Relevanssi.com](https://www.relevanssi.com/).

Do note that using Relevanssi may require large amounts (hundreds of megabytes) of database space (for a reasonable estimate, multiply the size of your `wp_posts` database table by three). If your hosting setup has a limited amount of space for database tables, using Relevanssi may cause problems. In those cases use of Relevanssi cannot be recommended.

= Key features =
* Search results sorted in the order of relevance, not by date.
* Fuzzy matching: match partial words, if complete words don't match.
* Find documents matching either just one search term (OR query) or require all words to appear (AND query).
* Search for phrases with quotes, for example "search phrase".
* Create custom excerpts that show where the hit was made, with the search terms highlighted.
* Highlight search terms in the documents when user clicks through search results.
* Search comments, tags, categories and custom fields.
* Multisite friendly.
* bbPress support.
* Gutenberg friendly.

= Advanced features =
* Adjust the weighting for titles, tags and comments.
* Log queries, show most popular queries and recent queries with no hits.
* Restrict searches to categories and tags using a hidden variable or plugin settings.
* Index custom post types and custom taxonomies.
* Index the contents of shortcodes.
* Google-style "Did you mean?" suggestions based on successful user searches.
* Support for [WPML multi-language plugin](http://wpml.org/) and [Polylang](https://wordpress.org/plugins/polylang/).
* Support for [s2member membership plugin](http://www.s2member.com/), [Members](https://wordpress.org/plugins/members/), [Groups](https://wordpress.org/plugins/groups/), [Simple Membership](https://wordpress.org/plugins/simple-membership/) and other membership plugins.
* Advanced filtering to help hacking the search results the way you want.
* Search result throttling to improve performance on large databases.
* Disable indexing of post content and post titles with a simple filter hook.

= Premium features (only in Relevanssi Premium) =
* Indexing attachment content (PDF, Office, Open Office).
* Improved spelling correction in "Did you mean?" suggestions.
* Searching across multiple sites in the same multisite installation.
* Search and index user profiles.
* Search and index taxonomy term pages (categories, tags, custom taxonomies).
* Search and index arbitrary columns in wp_posts MySQL table.
* Assign weights to any post types and taxonomies.
* Assign extra weight to new posts.
* Let the user choose between AND and OR searches, use + and - operator (AND and NOT).
* Export and import settings.
* [WP CLI commands](https://www.relevanssi.com/user-manual/wp-cli/).
* [Related posts](https://www.relevanssi.com/knowledge-base/related-posts/).
* [Redirects for searches](https://www.relevanssi.com/user-manual/redirects/).

= Relevanssi in Facebook =
You can find [Relevanssi in Facebook](https://www.facebook.com/relevanssi).

== Screenshots ==

1. Overview page
2. Indexing settings
3. Searching settings
4. Logging settings
5. Excerpts and highlights
6. Synonym settings
7. Stopword settings

== Installation ==

1. Install the plugin from the WordPress plugin screen.
1. Activate the plugin.
1. Go to the plugin settings page and build the index following the instructions there.
1. That's it!

Relevanssi uses the standard search form and doesn't usually need any changes in the search results template.

If the search does not bring any results, your theme probably has a query_posts() call in the search results template. That throws Relevanssi off. For more information, see [The most important Relevanssi debugging trick](https://www.relevanssi.com/knowledge-base/query_posts/).

= Uninstalling =
To uninstall the plugin remove the plugin using the normal WordPress plugin management tools (from the Plugins page, first Deactivate, then Delete). If you remove the plugin files manually, the database tables and options will remain.

== Frequently Asked Questions ==

= Knowledge Base =
You can find solutions and answers at the [Relevanssi Knowledge Base](https://www.relevanssi.com/category/knowledge-base/).

= Contextual help =
Answers to many common problems can be found from the contextual menu. Just click "Help" in the top right corner of your WordPress admin dashboard on the Relevanssi settings page.

= Relevanssi doesn't work =
If you the results don't change after installing and activating Relevanssi, the most likely reason is that you have a call to `query_posts()` on your search results template. This confuses Relevanssi. Try removing the `query_posts()` call and see what happens.

= Searching for words with ampersands or hyphens doesn't work =
Please read [Words with punctuation can't be found](https://www.relevanssi.com/knowledge-base/words-ampersands-cant-found/). This is a Relevanssi feature, but you can fix it from Relevanssi indexing settings.

= Where are the user search logs? =
See the top of the admin menu. There's 'User searches'.

= Displaying the relevance score =
Relevanssi stores the relevance score it uses to sort results in the $post variable. Just add something like

`echo $post->relevance_score`

to your search results template inside a PHP code block to display the relevance score.

= Did you mean? suggestions =
Relevanssi offers Google-style "Did you mean?" suggestions. See ["Did you mean" suggestions](https://www.relevanssi.com/knowledge-base/did-you-mean-suggestions/) in the Knowledge Base for more details.

= What is tf * idf weighing? =

It's the basic weighing scheme used in information retrieval. Tf stands for *term frequency* while idf is *inverted document frequency*. Term frequency is simply the number of times the term appears in a document, while document frequency is the number of documents in the database where the term appears.

Thus, the weight of the word for a document increases the more often it appears in the document and the less often it appears in other documents.

= What are stop words? =

Each document database is full of useless words. All the little words that appear in just about every document are completely useless for information retrieval purposes. Basically, their inverted document frequency is really low, so they never have much power in matching. Also, removing those words helps to make the index smaller and searching faster.

[](http://coderisk.com/wp/plugin/relevanssi/RIPS-XC1ekC4JKr)

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape for 2.5.5 fixes.
* Mohib Ebrahim for relentless bug hunting.
* John Calahan for extensive 4.0 beta testing.

== Changelog ==
= 4.5.0 =
* New feature: New filter hook `relevanssi_disable_stopwords` can be used to disable stopwords completely. Just add a filter function that returns `true`.
* Changed behaviour: Stopwords are no longer automatically restored if emptied. It's now possible to empty the stopword list. If you want to restore the stopwords from the file (or from the database, if you're upgrading from an earlier version of Relevanssi and find your stopwords missing), just click the button on the stopwords settings page that restores the stopwords.
* Changed behaviour: Changes to post weights in the `relevanssi_results` hook did not affect the relevance scores shown in excerpts. That's changed now, and the displayed scores are now taken from the `$doc_weight` array, which is returned in the return value array from `relevanssi_search()`.
* Changed behaviour: Excerpt length and type are now checked outside the loop that goes through the posts. This reduces the number of database calls required.
* Minor fix: Searching for regex special characters (for example parentheses, brackets) caused problems in excerpts.
* Minor fix: Improvements in handling highlighting for words with apostrophes.
* Minor fix: Disregard hanging commas at the end of post exclusion settings.
* Minor fix: Sometimes excerpts wouldn't have an ellipsis in the beginning even though they should.

= 4.4.1 =
* Major fix: Returns the missing stopwords.

= 4.4.0 =
* New feature: It's now possible to exclude image attachments from the index with a simple setting on the indexing settings page.
* New feature: Page builder short codes are now removed in Relevanssi indexing. This should reduce the amount of garbage data indexed for posts in Divi, Avada and other page builder themes.
* Changed behaviour: The `relevanssi_page_builder_shortcodes` filter hook is now applied both in indexing and excerpts, and has a second parameter that will inform you of the current context.
* Minor fix: Stopwords weren't case insensitive like they should. They are now. Also, stopwords are no longer stored in the `wp_relevanssi_stopwords` database table, but are now stored in the `relevanssi_stopwords` option.
* Minor fix: A comma at the end of the custom field indexing setting made Relevanssi index all custom fields. This doesn't happen anymore and trailing commas are automatically removed, too.
* Minor fix: Accessibility improvements all around the admin interface. Screen reader support should be better, feel free to report any further ways to make this better.
* Minor fix: Doing searches without search terms and with the throttle disabled could cause problems. Relevanssi now makes sure throttle is always on when doing termless searches.
* Minor fix: Untokenized search terms are used for building excerpts, which makes highlighting in excerpts work better.
* Minor fix: Indexing did not adjust the number of posts indexed at one go like it should.

== Upgrade notice ==
= 4.5.0 =
* Bug fixes and improvements to stopword management.

= 4.4.1 =
* Fixes missing stopwords problem in 4.4.0.

= 4.4.0 =
* Changes in relevanssi_page_builder_shortcodes filter hook, page builder indexing and image attachments.
