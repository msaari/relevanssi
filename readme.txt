=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search, product search, woocommerce search
Requires at least: 4.9
Tested up to: 5.7
Requires PHP: 7.0
Stable tag: 4.12.5
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

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape for 2.5.5 fixes.
* Mohib Ebrahim for relentless bug hunting.
* John Calahan for extensive 4.0 beta testing.

== Changelog ==
= 4.12.5 =
* Changed behaviour: `relevanssi_excerpt_custom_field_content` now gets the post ID and list of custom field names as a parameter.
* Minor fix: Makes sure Relevanssi options are not wiped when the free version is deleted while Premium is active.
* Minor fix: Adds a trailing slash to the blog URL in Did you mean links.

= 4.12.4 =
* New feature: New action hooks `relevanssi_pre_the_content` and `relevanssi_post_the_content` fire before and after Relevanssi applies `the_content` filter to the post excerpts. Some Relevanssi default behaviour has been moved to these hooks so it can be modified.
* Changed behaviour: The `relevanssi_do_not_index` gets the post object as a third parameter.
* Minor fix: Remove errors from `relevanssi_strip_all_tags()` getting a `null` parameter.

= 4.12.3 =
* Major fix: Post type weights did not work; improving the caching had broken them.
* Minor fix: Relevanssi works better with soft hyphens now, removing them in indexing and excerpt-building.
* Minor fix: Stops indexing error messages in WPML.

= 4.12.2 =
* Major fix: Stops more problems with ACF custom field indexing.
* Major fix: Fixes a bug in search result caching that caused Relevanssi to make lots of unnecessary database queries.

= 4.12.1 =
* Major fix: Stops TypeError crashes from null custom field indexing.

= 4.12.0 =
* New feature: New filter hook `relevanssi_phrase_queries` can be used to add phrase matching queries to support more content types.
* New feature: New filter hook `relevanssi_excerpt_gap` lets you adjust the first line of excerpt optimization.
* Changed behaviour: The `relevanssi_admin_search_element` filter hook now gets the post object as the second parameter, rendering the filter hook more useful.
* Changed behaviour: Relevanssi now automatically optimizes excerpt creation in long posts. You can still use `relevanssi_optimize_excerpts` for further optimization, but it's probably not necessary.
* Changed behaviour: The `relevanssi_tag_before_tokenize` filter hook parameters were changed in order to be actually useful and to match what the filter hook is supposed to do.
* Minor fix: In some cases Relevanssi wouldn't highlight the last word of the title. This is more reliable now.
* Minor fix: Relevanssi will now add the `highlight` parameter only to search results, and not to other links on the search results page.
* Minor fix: Improved fringe cases in nested taxonomy queries.
* Minor fix: Taxonomy terms in WPML were not indexed correctly. Instead of the post language, the current language was used, so if your admin dashboard is in English, German posts would get English translations of the terms, not German. This is now fixed.
* Minor fix: Excerpt creation is now faster when multiple excerpts are not used.
* Minor fix: The SEO plugin noindex setting did not actually work. That has been fixed now.

= 4.11.1 =
* Major fix: The type hinting introduced for some functions turned out to be too strict, causing fatal errors. The type hinting has been relaxed (using nullable types would help, but that's a PHP 7.4 feature, and we don't want that).

= 4.11.0 =
* New feature: New filter hook `relevanssi_rendered_block` filters Gutenberg block content after the block has been rendered with `render_block()`.
* New feature: New filter hook `relevanssi_log_query` can be used to filter the search query before it's logged. This can be used to log instead the query that includes synonyms (available as a parameter to the filter hook).
* New feature: New filter hook `relevanssi_add_all_results` can be used to make Relevanssi add a list of all result IDs found to `$query->relevanssi_all_results`. Just make this hook return `true`.
* New feature: New filter hook `relevanssi_acceptable_hooks` can be used to adjust where in WP admin the Relevanssi admin javascripts are enqueued.
* New feature: Support for All-in-One SEO. Posts marked as 'Robots No Index' are not indexed by Relevanssi.
* New feature: New setting in advanced indexing settings to control whether Relevanssi respects the SEO plugin 'noindex' setting or not.
* Changed behaviour: Type hinting has been added to Relevanssi functions, which may cause errors if the filter functions are sloppy with data types.
* Changed behaviour: `relevanssi_the_title()` now supports the same parameters as `the_title()`, so you can just replace `the_title()` with it and keep everything else the same. The old behaviour is still supported.
* Changed behaviour: Relevanssi no longer logs queries with the added synonyms. You can use the `relevanssi_log_query` filter hook to return to the previous behaviour of logging the synonyms too. Thanks to Jan Willem Oostendorp.
* Changed behaviour: When using ACF and custom fields indexing set to 'all', Relevanssi will no longer index the meta fields (where the content begins with `field_`).
* Minor fix: The Oxygen compatibility made it impossible to index other custom fields than the Oxygen `ct_builder_shortcodes`. This has been improved now.
* Minor fix: Old legacy scripts that caused Javascript warnings on admin pages have been removed.
* Minor fix: In some cases, having less than or greater than symbols in PDF content would block that PDF content from being indexed.

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

== Upgrade notice ==
= 4.12.5 =
* Fixes minor bugs.

= 4.12.4 =
* Fixes minor bugs.

= 4.12.3 =
* Fixes post type weights and WPML indexing problems.

= 4.12.2 =
* Stops Relevanssi from crashing when saving posts with ACF fields, major performance boost.

= 4.12.1 =
* Stops TypeError crashes from null custom field indexing.

= 4.12.0 =
* New features and bug fixes.

= 4.11.1 =
* Prevents surprising fatal errors.

= 4.11.0 =
* New filter hooks, bug fixes.

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