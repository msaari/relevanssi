=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search
Requires at least: 4.0
Tested up to: 5.0
Requires PHP: 5.6
Stable tag: 4.0.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Relevanssi replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

== Description ==

Relevanssi replaces the standard WordPress search with a better search engine, with lots of features and configurable options. You'll get better results, better presentation of results - your users will thank you.

This is the free version of Relevanssi. There's also Relevanssi Premium, which has added features. For more information about Premium, see [Relevanssi.com](https://www.relevanssi.com/).

Do note that using Relevanssi may require large amounts (hundreds of megabytes) of database space. If your hosting setup has a limited amount of space for database tables, using Relevanssi may cause problems. In those cases use of Relevanssi cannot be recommended.

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

= Advanced features =
* Adjust the weighting for titles, tags and comments.
* Log queries, show most popular queries and recent queries with no hits.
* Restrict searches to categories and tags using a hidden variable or plugin settings.
* Index custom post types and custom taxonomies.
* Index the contents of shortcodes.
* Google-style "Did you mean?" suggestions based on successful user searches.
* Support for [WPML multi-language plugin](http://wpml.org/) and [Polylang](https://wordpress.org/plugins/polylang/).
* Support for [s2member membership plugin](http://www.s2member.com/), [Members](https://wordpress.org/plugins/members/), [Groups](https://wordpress.org/plugins/groups/) and [Simple Membership](https://wordpress.org/plugins/simple-membership/).
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
* Highlighting search terms for visitors from external search engines.
* Export and import settings.
* [WP CLI commands](https://www.relevanssi.com/user-manual/wp-cli/).

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

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape for 2.5.5 fixes.
* Mohib Ebrahim for relentless bug hunting.
* John Calahan for extensive 4.0 beta testing.

== Changelog ==

= 4.0.11 =
* Home page links were getting the highlight parameter even though they shouldn't. This has been fixed.
* Added support for WP JV Post Reading Groups.
* Improved handling of HTML entities.
* Events Made Easy Calendar shortcodes are now removed when building excerpts.
* `set_time_limit()` was removed from the indexing; it's no longer necessary, and it can break the indexing on sites that don't allow the use of the function.
* `relevanssi_post_title_before_tokenize` filter was moved a bit so that it's the last thing that runs before tokenizing.
* Disabled shortcodes are handled better in the indexing: the shortcode names won't be indexed anymore like they were before.
* Made sure there won't be a warning for non-numeric values when searching.
* New filter: `relevanssi_clean_excerpt` lets you remove unwanted highlights from excerpts.
* Highlighting works better with `pre` and `code` tags.
* New filter: `relevanssi_comment_author_to_index` lets you filter comment author names before indexing.
* `relevanssi_comment_content_to_index` doesn't include the comment author name anymore.

= 4.0.10.1 =
* The privacy features caused an error notice with certain Relevanssi configurations, and the plugin required WP 4.9.6.

= 4.0.10 =
* Privacy: If you log search queries, Relevanssi will suggest some additional content to your privacy policy page.
* Privacy: Relevanssi now supports the new Privacy Policy and Personal Data tools in WordPress 4.9.6.
* Saving synonyms with quotes worked, but the synonyms showed up wrong.
* Relevanssi could in some situations override navigation menu links with links to the user profiles or taxonomy terms found in the search. This update fixes that behaviour.
* Random order works again; using orderby `rand` didn't work properly. The `rand(seed)` format is also supported now.
* Fixed quotes and apostrophes in Did you mean suggestions.

= 4.0.9 =
* Fixes broken tag and category indexing and searching. If you use tags and categories, rebuild the index after updating.
* Phrases were not highlighted correctly on documents. This is now fixed.
* Shortcode fix: 'wp_show_posts' shouldn't cause problems anymore.
* New filter: `relevanssi_indexing_restriction` allows filtering posts before indexing.
* New WooCommerce product visibility filtering tool makes WooCommerce product indexing faster.
* MemberPress post controls were loose and showed drafts to searchers. That is now fixed.
* Highlighting was too loose, even if matching was set to whole words.
* Highlighting now works better in cases where there's a hyphen or an apostrophe inside a word.

= 4.0.8 =
* Fixed cases where Relevanssi added an ellipsis even if the excerpt was from the start of the post.
* Highlighting now works with numeric search strings.
* Improved highlighting for accented words. Thanks to Paul Ryan.
* A surplus comma at the end of post exclusion setting won't break the search anymore.
* Fixed instructions for adjusting the throttle limit.

= 4.0.7 =
* Recent post bonus is now applied to searches.
* Exact term setting can now be disabled.
* Users of Members plugin would have drafts appear in search results. This is now fixed.

= 4.0.6 =
* Indexing bugs squashed.
* Missing tag and category weight settings returned.
* Fusion builder shortcodes are removed from excerpts.
* MemberPress post control was backwards.
* User searches page reset buttons fixed.
* WPML language filter fix.

== Upgrade notice ==

= 4.0.11 =
* Several small improvements, new filters and highlighting fixes.

= 4.0.10.1 =
* Privacy feature bug fix.

= 4.0.10 =
* Privacy update, with some bug fixes.

= 4.0.9 =
* Fixes broken tag and category searching and indexing. Reindex after the update!

= 4.0.8 =
* Improvements to highlighting and excerpts.

= 4.0.7 =
* Small bug fixes.

= 4.0.6 =
* Indexing bugs fixed and WPML support corrected.
