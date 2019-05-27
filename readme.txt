=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search
Requires at least: 4.8.3
Tested up to: 5.2.1
Requires PHP: 5.6
Stable tag: 4.2.0
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
= 4.2.0 =
* New feature: The search form shortcode has a new parameter `dropdown` which can be used to add a category dropdown, like this: `[searchform dropdown="category"]`.
* New feature: Relevanssi can now use the contents of the PDF files indexed with WP File Download.
* New filter: `relevanssi_indexing_tokens` can be used to filter the tokens (individual words) before they are indexed.
* Removed filter: `relevanssi_default_meta_query_relation` did not have any effect anymore.
* Changed behaviour: The default taxonomy relation was set to AND in 4.1.4, but wasn't properly applied before. Now it is really switched.
* Changed behaviour: New post types have been added to list of forbidden post types Relevanssi won't show as indexing options (ACF, TablePress and WooCommerce).
* Major fix: Tax query processing has been completely refactored, eliminating all sorts of bugs, especially with various edge cases.
* Major fix: Gutenberg block indexing only worked with the Gutenberg plugin enabled. It now works with WP 5.0 built-in Gutenberg as well. If you use Gutenberg blocks, reindex to get all the block content in the index.
* Major fix: Excerpt-building and highlighting did not respect the "Keyword matching" setting. They do now, and the excerpts should be better now.
* Major fix: AND searches needed queries that could get too long for the database to handle. This has been fixed and optimized.
* Major fix: Taxonomy term subquery relations didn't work; now they are applied.
* Minor fix: iOS uses curly quotes by default, and that didn't work as a phrase operator. Now phrase operator works with curly quotes and straight quotes.
* Minor fix: The Did you mean broke with search terms longer than 255 characters.
* Minor fix: Phrases with numbers and one word like "team 17" didn't work, because numbers weren't counted as words.

= 4.1.4 =
* `EXISTS` and `NOT EXISTS` didn’t work for taxonomy terms in searches.
* WPML post type handling has been improved. If post type allows fallback for default language, Relevanssi will support that.
* Relevanssi now reminds you to set up automatic trimming for the logs. It’s a really good idea, otherwise the logs will become bloated, which will hurt search performance.
* The Groups posts filter is only applied to public posts to avoid drafts being shown to people who shouldn’t see them.
* The `posts_per_page` query variable didn’t work; it’s now added to the introduced query variables so that it works.
* Relevanssi won’t log empty queries anymore.
* The default tax query relation was switched from `OR` to `AND` to match the WP_Query default behaviour.
* When used with WP 5.1, Relevanssi will now use `wp_insert_site` instead of the now-deprecated `wpmu_new_blog`.
* Multisite blog creation is handled better in WP 5.1+.
* Relevanssi now supports Restrict Content Pro permissions.

= 4.1.3 =
* Improvements to meta key sorting.
* Relevanssi settings page won't let you exclude categories you have restricted the search to.
* Members plugin compatibility has been improved: it's only used if the 'content permissions' feature has been enabled.
* The excerpt settings page was a bit buggy.
* Slimstat analytics is now added to the blocked shortcodes list.
* New filter: `relevanssi_search_form` works exactly like `get_search_form`, but only applies to the Relevanssi shortcode search forms.
* New JetPack taxonomies and post types have been added to the block list so they won't appear in Relevanssi settings.

= 4.1.2 =
* Choosing "CSS Style" for highlighting was not possible. That is now fixed.
* Gutenberg reusable block indexing was fatally broken with the latest Gutenberg version. That has been updated.
* Relevanssi now by default respects the WooCommerce "exclude from search" setting.
* `post__not_in` still didn't work properly, it does now.
* New filter: `relevanssi_comparison_order` can be used to define the sorting order when sorting the results by post type.
* "Did you mean" process included a very slow query. It is now cached, leading in some cases to massive performance improvements (we're talking about several seconds here).
* Highlights inside `code` and similar blocks are handled better now.

= 4.1.1.2 =
* Fixes the broken User searches page.

= 4.1.1.1 =
* Adding the missing Gutenberg compatibility file.

= 4.1.1 =
* Relevanssi can now index Gutenberg reusable blocks. (This functionality broke once already before release, so that can happen, since Gutenberg is still in very active development.)
* The `post__in` and `post__not_in` parameters didn't work, and are now fixed. `post_parent__in` and `post_parent__not_in` are also improved.
* You can use named meta queries for sorting posts. Meta query sorting is improved in other ways as well.
* Log export didn't work properly.
* Adding stopwords from the common word list has been fixed.
* The `relevanssi_get_words_having` filter hook is now also applied to the free version Did you mean queries.
* New filters: `relevanssi_1day` and `relevanssi_7days` can be used to adjust the number of days for log displays, so instead of 1, 7 and 30 days you can have anything you want.

= 4.1.0.1 =
* Actually working admin search.

= 4.1 =
* New feature: You can now export the search log as a CSV file.
* New feature: Admin Search page allows you to perform searches in WP admin using Relevanssi.
* New filter: `relevanssi_admin_search_capability` can be used to adjust who sees the admin search page.
* New filter: `relevanssi_entities_inside_pre` and `relevanssi_entities_inside_code` adjust how HTML entities are handled inside `pre` and `code` tags.
* Numeric meta values (`meta_value_num`) are now sorted as numbers and not strings.
* Pinned posts have `$post->relevanssi_pinned` set to 1 for debugging purposes, but you can also use this for styling the posts in the search results templates.
* The Did you mean feature has been toned down a bit, to make the suggestions slightly less weird in some cases.
* Post parent parameters now accept 0 as a value, making it easier to search for children of any post or posts without a parent.
* Polylang compatibility has been improved.
* Phrases with apostrophes inside work better.
* The `relevanssi_excerpt` filter hook got a second parameter that holds the post ID.
* Custom field sorting actually works now.
* WP Search Suggest compatibility added.

== Upgrade notice ==
= 4.2.0 =
* New features, bug fixes, smaller improvements.

= 4.1.4 =
* Restrict Content Pro support, bug fixes and small improvements.

= 4.1.3 =
* Small improvements here and there.

= 4.1.2 =
* Better compatibility with Gutenberg, new features.

= 4.1.1.2 =
* Fixes the broken User searches page.

= 4.1.1.1 =
* Adding the missing Gutenberg compatibility file.

= 4.1.1 =
* Minor improvements here and there, particularly in custom field sorting.

= 4.1 =
* New features and plenty of small fixes.