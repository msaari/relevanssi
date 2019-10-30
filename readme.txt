=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search
Requires at least: 4.8.3
Tested up to: 5.3
Requires PHP: 5.6
Stable tag: 4.3.4
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
* Export and import settings.
* [WP CLI commands](https://www.relevanssi.com/user-manual/wp-cli/).
* Related posts.

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
= 4.3.4 =
* New feature: You can now give Gutenberg blocks a CSS class `relevanssi_noindex` to exclude them from being indexed. Relevanssi will not index Gutenberg blocks that have the class.
* New feature: Relevanssi automatically skips some custom fields from common plugins that only contain unnecessary metadata.
* New feature: The search results breakdown is added to the post objects and can be found in $post->relevanssi_hits. The data also includes new fields and the breakdown from the excerpt settings page can now show author, excerpt, custom field and MySQL column hits.
* New feature: Relevanssi can now index Ninja Tables table content. This is something of an experimental feature right now, feedback is welcome.
* New feature: New filter hook `relevanssi_indexing_query` filters the indexing query and is mostly interesting for debugging reasons.
* Minor fix: Deleted and trashed comment contents were not deindexed when the comment was removed. That has been corrected now.
* Minor fix: Phrase matching is now applied to attachments as well, including the attachments indexed for parent post.
* Minor fix: Phrase matching only looks at custom fields that are indexed by Relevanssi.
* Minor fix: Exact match bonus now uses the original query without synonyms added for the exact match check.
* Minor fix: Paid Membership Pro filtering is only applied to published posts to prevent drafts from showing up in the search results.

= 4.3.3 =
* New feature: New filter hook `relevanssi_indexing_adjust` can be used to stop Relevanssi from adjusting the number of posts indexed at once during the indexing.
* New feature: New filter hook `relevanssi_acf_field_value` filters ACF field values before they are indexed.
* New feature: New filter hook `relevanssi_disabled_shortcodes` filters the array containing shortcodes that are disabled when indexing.
* Removed feature: The `relevanssi_indexing_limit` option wasn't really used anymore, so it has been removed.
* Changed behaviour: Indexing exclusions from Yoast SEO and SEOPress are applied in a different way in the indexing, making for a smoother indexing process.
* Changed behaviour: WP Table Reloaded support has been removed; you really shouldn't be using WP Table Reloaded anymore.
* Minor fix: Relevanssi won't choke on ACF fields with array or object values anymore.
* Minor fix: Relevanssi uninstall process left couple of Relevanssi options in the database.
* Minor fix: WPML language filter didn't work when `fields` was set to `ids` or `id=>parent`.

= 4.3.2 =
* New feature: SEOPress support, posts marked "noindex" in SEOPress are no longer indexed by Relevanssi by default.
* Changed behaviour: Membership plugin compatibility is removed from `relevanssi_default_post_ok` function and has been moved to individual compatibility functions for each supported membership plugin. This makes it much easier to for example disable the membership plugin features if required.
* Minor fix: The `searchform` shortcode now works better with different kinds of search forms.
* Minor fix: Yoast SEO compatibility won't block indexing of posts with explicitly allowed indexing.
* Minor fix: The `relevanssi_the_tags()` function printed out plain text, not HTML code like it should. The function now also accepts the post ID as a parameter.
* Minor fix: Excerpt creation and highlighting have been improved a little.

= 4.3.1.1 =
* Remove notice about undefined index.

= 4.3.1 =
* Adding a missing file.

= 4.3.0 =
* New feature: Multi-phrase searches now respect AND and OR operators. If multiple phrases are included in an OR search, any posts with at least one phrase will be included. In AND search, all phrases must be included.
* New feature: Admin search has been improved: there's a post type dropdown and the search is triggered when you press enter. The debug information has a `div` tag around it with the id `debugging`, so you can hide them with CSS if you want to. The numbering of results also makes more sense.
* New feature: The date parameters (`year`, `monthnum`, `w`, `day`, `hour`, `minute`, `second`, `m`) are now supported.
* New feature: New filter hook `relevanssi_indexing_limit` filters the default number of posts to index (10). If you have issues with indexing timing out, you can try adjusting this to a smaller number like 5 or 1.
* New feature: Support for Paid Membership Pro added.
* New feature: WordPress SEO support, posts marked "noindex" in WordPress SEO are no longer indexed by Relevanssi by default.
* Removed feature: qTranslate is no longer supported.
* Major fix: Tax query searching had some bugs in it, manifesting especially into Polylang not limiting the languages correctly. Some problems with the test suites were found and fixed, and similar problems won't happen again.
* Minor fix: Admin search only shows editing options to users with enough capabilities to use them.
* Minor fix: Phrase searching now uses filterable post statuses instead of a hard-coded set of post statuses.
* Minor fix: The plugin action links were missing on the Plugins page list, they're back now.
* Minor fix: Search terms with slashes won't cause errors anymore.
* Minor fix: Relevanssi admin pages have been examined for accessibility and form labels have been improved in many places.
* Deprecated: `relevanssi_get_term_taxonomy()` function is deprecated and will be removed at some point in the future.

== Upgrade notice ==
= 4.3.4 =
* Comment indexing bug fix, compatibility improvements and minor bug fixes and improvements.

= 4.3.3 =
* Bug fixes and overall improvements.

= 4.3.2 =
* Yoast SEO compatibility fix, minor updates.

= 4.3.1.1 =
* Remove an error notice.

= 4.3.1 =
* Fixes the broken 4.3.0 release.

= 4.3.0 =
* Major bug fixes for taxonomy queries, new features and smaller improvements.
