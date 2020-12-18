=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search, product search, woocommerce search
Requires at least: 4.9
Tested up to: 5.6.1
Requires PHP: 7.0
Stable tag: 4.10.2
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
= 4.10.2 =
* New feature: You can force Relevanssi to be active by setting the query variable `relevanssi` to `true`. Thanks to Jan Willem Oostendorp.
* Changed behaviour: Relevanssi has been moved from `the_posts` filter to `posts_pre_query`. This change doesn't do much, but increases performance slightly as WordPress needs to do less useless work, as now the default query is no longer run. Thanks to Jan Willem Oostendorp.
* Minor fix: Highlighting didn't work properly when highlighting something immediately following a HTML tag.
* Minor fix: You can no longer set the value of minimum word length to less than 1 or higher than 9 from the settings page.
* Minor fix: Importing options broke synonym and stopword settings.
* Minor fix: Improves the Rank Math SEO compatibility to avoid errors in plugin activation.
* Minor fix: WPML search results that included non-post results caused fatal errors and crashes. This fixes the crashing and makes non-post results work better in both WPML and Polylang.

= 4.10.1 =
* Major fix: The multilingual stopwords and synonyms were used based on the global language. Now when indexing posts, the post language is used instead of the global language.

= 4.10.0 =
* New feature: Relevanssi now supports multilingual synonyms and stopwords. Relevanssi now has a different set of synonyms and stopwords for each language. This feature is compatible with WPML and Polylang.
* New feature: SEO by Rank Math compatibility is added: posts marked as 'noindex' with Rank Math are not indexed by Relevanssi.
* Minor fix: With keyword matching set to 'whole words' and the 'expand highlights' disabled, words that ended with an 's' weren't highlighted correctly.
* Minor fix: The 'Post exclusion' setting didn't work correctly. It has been fixed.
* Minor fix: It's now impossible to set negative weights in searching settings. They did not work as expected anyway.
* Minor fix: Relevanssi had an unnecessary index on the `doc` column in the `wp_relevanssi` database table. It is now removed to save space. Thanks to Matthew Wang.
* Minor fix: Improved Oxygen Builder support makes sure `ct_builder_shortcodes` custom field is always indexed.

= 4.9.1 =
* Changed behaviour: The `relevanssi_excerpt_part` filter hook now gets the post ID as a second parameter. The documentation for the filter has been fixed to match actual use: this filter is applied to the excerpt part after the highlighting and the ellipsis have been added.
* Changed behaviour: The `relevanssi_index_custom_fields` filter hook is no longer used when determining which custom fields are used for phrase searching. If you have a use case where this change matters, please contact us.
* Minor fix: The `relevanssi_excerpt` filter hook was removed in 4.9.0. It is now restored and behaves the way it did before.
* Minor fix: Avoids undefined variable warnings from the Pretty Links compatibility code.
* Minor fix: The Oxygen Builder compatibility has been improved. Now shortcodes in Oxygen Builder content are expanded, if that setting is enabled in Relevanssi settings.

= 4.9.0 =
* New feature: There's now a "Debugging" tab in the Relevanssi settings, letting you see how the Relevanssi index sees posts. This is familiar to Premium users, but is now available in the free version as well.
* New feature: The SEO Framework plugin is now supported and posts set excluded from the search in SEO Framework settings will be excluded from the index.
* New feature: There's a new option, "Expand highlights". Enabling it makes Relevanssi expand partial-word highlights to cover the full word. This is useful when doing partial matching and when using a stemmer.
* New feature: New filter hook `relevanssi_excerpt_part` allows you to modify the excerpt parts before they are combined together. This doesn't do much in the free version.
* New feature: Improved compatibility with Oxygen Builder. Relevanssi automatically indexes the Oxygen Builder content and cleans it up. New filter hooks `relevanssi_oxygen_section_filters` and `relevanssi_oxygen_section_content` allow easier filtering of Oxygen content to eg. remove unwanted sections.
* Changed behaviour: The "Uncheck this for non-ASCII highlights" option has been removed. Highlights are now done in a slightly different way that should work in all cases, including for example Cyrillic text, thus this option is no longer necessary.
* Minor fix: Fixes phrase searching using non-US alphabet.
* Minor fix: Relevanssi would break admin searching for hierarchical post types. This is now fixed, Relevanssi won't do that anymore.
* Minor fix: Relevanssi indexing now survives better shortcodes that change the global `$post`.
* Minor fix: Warnings about missing `relevanssi_update_counts` function are now removed.
* Minor fix: Paid Membership Pro support now takes notice of the "filter queries" setting.
* Minor fix: OR logic didn't work correctly when two phrases both had the same word (for example "freedom of speech" and "free speech"). The search would always be an AND search in those cases. That has been fixed.
* Minor fix: Relevanssi no longer blocks the Pretty Links admin page search.
* Minor fix: The "Respect 'exclude_from_search'" setting did not work if no post type parameter was included in the search parameters.
* Minor fix: The category inclusion and exclusion setting checkboxes on the Searching tab didn't work. The setting was saved, but the checkboxes wouldn't appear.

= 4.8.3 =
* New feature: Both `relevanssi_fuzzy_query` and `relevanssi_term_where` now get the current search term as a parameter.
* Minor fix: Relevanssi database tables don't have PRIMARY keys, only UNIQUE keys. In case this is a problem (for example on Digital Ocean servers), deactivate and activate Relevanssi to fix the problem.
* Minor fix: When `posts_per_page` was set to -1, the `max_num_pages` was incorrectly set to the number of posts found. It should, of course, be 1.
* Minor fix: Excluding from logs didn't work if user IDs had spaces between them ('user_a, user_b'). This is now fixed for good, the earlier fix didn't work.

= 4.8.2 =
* New feature: New filter hook `relevanssi_term_where` lets you filter the term WHERE conditional for the search query.
* Minor fix: Doing the document count updates asynchronously caused problems in some cases (eg. importing posts). Now the document count is only updated after a full indexing and once per week.
* Minor fix: Phrase matching has been improved to make it possible to search for phrases that include characters like the ampersand.

= 4.8.1 =
* Major fix: Changes in WooCommerce 4.4.0 broke the Relevanssi searches. This makes the WooCommerce search work again.
* Minor fix: Excluding from logs didn't work if user IDs had spaces between them ('user_a, user_b'). Now the extra spaces don't matter.
* Minor fix: The asynchronous doc count action in the previous version could cause an infinite loop with the Snitch logger plugin. This is prevented now: the async action doesn't run after indexing unless a post is actually indexed.
* Minor fix: Relevanssi indexing procedure was triggered for autosaved drafts, causing possible problems with the asynchronous doc count action.
* Minor fix: The `relevanssi_index_custom_fields` filter hook was not applied when doing phrase matching, thus phrases could not be found when they were in custom fields added with the filter.

= 4.8.0 =
* Changed behaviour: Relevanssi now requires PHP 7.
* Changed behaviour: Relevanssi now sorts strings with `strnatcasecmp()` instead of `strcasecmp()`, leading to a more natural results with strings that include numbers.
* Changed behaviour: Relevanssi init is now moved from priority 10 to priority 1 on the `init` hook to avoid problems with missing TablePress compatibility.
* New feature: New filter hook `relevanssi_get_approved_comments_args` filters the arguments to `get_approved_comments` in comment indexing. This can be used to index custom comment types, for example.
* New feature: Content wrapped in the `noindex` tags is no longer used for excerpts.
* New feature: The `[et_pb_fullwidth_code]` shortcode is now removed completely, including the contents, when Relevanssi is indexing and building excerpts.
* Major fix: Relevanssi didn't index new comments when they were added; when a post was indexed or the whole index rebuilt, comment content was included. We don't know how long this bug has existed, but it is now fixed. Rebuild the index to get all comment content included in the index.
* Minor fix: Autoload has been disabled for several options that are not needed often.
* Minor fix: Phrase matching did not work correctly in visible custom fields.
* Minor fix: TablePress support could cause halting errors if posts were inserted before Relevanssi has loaded itself (on `init` priority 10). These errors will no longer happen.
* Minor fix: The doc count update, which is a heavy task, is now moved to an asynchronous action to avoid slowing down the site for users.
* Minor fix: Relevanssi only updates doc count on `relevanssi_insert_edit()` when the post is indexed.

== Upgrade notice ==
= 4.10.2 =
* Switch from `the_posts` to `posts_pre_query`, bug fixes.

= 4.10.1 =
* Corrects the multilingual stopwords and synonyms.

= 4.10.0 =
* Adds support for multilingual stopwords and synonyms.

= 4.9.1 =
* Bug fixing, better Oxygen Builder compatibility.

= 4.9.0 =
* New debugging feature, lots of minor fixes.

= 4.8.3 =
* Small bug fixes and database improvements.

= 4.8.2 =
* Performance and phrase search improvements.

= 4.8.1 =
* WooCommerce 4.4 compatibility, other minor fixes.

= 4.8.0 =
* Fixes a major bug in comment indexing, if you include comments in the index rebuild the index after updating.