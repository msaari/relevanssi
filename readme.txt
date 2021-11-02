=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search, product search, woocommerce search
Requires at least: 4.9
Tested up to: 5.8.1
Requires PHP: 7.0
Stable tag: 4.14.4
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
= 4.14.4 =
* Minor fix: `relevanssi_orderby` did not always accept an array-format orderby parameter.
* Minor fix: Removes a highlighting problem stemming from uppercase search terms.
* Minor fix: Relevanssi removes highlights better from inside multiline HTML tags.
* Minor fix: When image attachment indexing was disabled, saving image attachments would still index the images. Image attachment blocking is now a `relevanssi_indexing_restriction` filter function, which means it's always active.

= 4.14.3 =
* Security fix: User searches page had a XSS vulnerability.

= 4.14.2 =
* Minor fix: Remove unnecessary database calls from admin pages.
* Minor fix: Improved Oxygen compatibility.

= 4.14.1 =
* Adds a missing file.

= 4.14.0 =
* New feature: New filter hook `relevanssi_render_blocks` controls whether Relevanssi renders blocks in a post or not. If you are having problems updating long posts with lots of blocks, having this filter hook return `false` for the post in question will likely help, as rendering the blocks in a long post can take huge amounts of memory.
* New feature: The user searches page has been improved a lot.
* New feature: The [searchform] shortcode has a new parameter, 'post_type_boxes', which creates a checkbox for each post type you list in the value. For example [searchform post_type_boxes='*post,page'] would create a search with a checkbox for 'post' and 'page' post types, with 'post' pre-checked.
* New feature: You can now have multiple dropdowns in one [searchform] shortcode. Anything that begins with 'dropdown' is considered a dropdown parameter, so you can do [searchform dropdown_1='category' dropdown_2='post_tag'] for example.
* New feature: New filter hook `relevanssi_search_params` lets you filter search parameters after they've been collected from the WP_Query.
* New feature: New filter hook `relevanssi_excerpt_post` lets you make Relevanssi skip creating excerpts for specific posts.
* Changed behaviour: Filter hooks `relevanssi_1day`, `relevanssi_7days` and `relevanssi_30days` are removed, as the user searches page is now different. The default value for `relevanssi_user_searches_limit` is now 100 instead of 20.
* Minor fix: In some languages, iOS uses „“ for quotes. Relevanssi now understands those for the phrase operator.
* Minor fix: Stops Relevanssi from blocking the admin search for WooCommerce coupons and other WooCommerce custom post types.
* Minor fix: Fixes problems with the WP-Members compatibility.
* Minor fix: New parameter for `relevanssi_tokenize()` introduces the context (indexing or search query). The `relevanssi_extract_phrases()` is only used on search queries.
* Minor fix: Relevanssi won't let you adjust synonyms and stopwords anymore if you use Polylang and are in 'Show all languages' mode.
* Minor fix: Highlighting is improved by a more precise HTML entity filter, thanks to Jacob Bearce.

= 4.13.3.1 =
* Minor fix: The Bricks compatibility was broken. This version fixes it.

= 4.13.3 =
* New feature: You can now add a post type dropdown to search forms with the [searchform] shortcode with the parameter 'dropdown' set to 'post_type'.
* New feature: Adds compatibility for Product GTIN (EAN, UPC, ISBN) for WooCommerce plugin.
* New feature: New filter hook `relevanssi_post_to_excerpt` lets you filter the post object before an excerpt is created from it.
* New feature: Relevanssi is now compatible with the Bricks page builder theme (requires Bricks 1.3.2).
* Minor fix: The ″ character is now counted as a quote.
* Minor fix: Running indexing through WP CLI doesn't cause PHP notices anymore.
* Minor fix: Sometimes the Did you mean would return really weird long suggestions from the search logs. That won't happen anymore.
* Minor fix: Improves tax_query handling in fringe cases with multiple AND clauses joined together with OR.
* Minor fix: Oxygen compatibility has been improved. Rich text fields and updating posts when they are saved in Oxygen now work better, and revisions are no longer indexed.
* Minor fix: Searching without a search term works much better now, you get more posts in the results (default value is up to 500).

= 4.13.2 =
* New feature: Adds support for Avada Live Search.
* New feature: Adds support for Fibo Search.
* Minor fix: Elementor library searches are not broken anymore when Relevanssi is enabled in admin.
* Minor fix: Relevanssi now understands array-style post_type[] parameters.
* Minor fix: Relevanssi now automatically considers the Turkish 'ı' the same as 'i'.

= 4.13.1 =
* New feature: Adds compatibility for WP-Members plugin, preventing blocked posts from showing up in the search results.
* New feature: New function `relevanssi_get_attachment_suffix()` can be used to return the attachment file suffix based on a post object or a post ID.
* Minor fix: Improves the Oxygen compatibility. Now also the [oxygen] shortcode tags are removed.

= 4.13.0 =
* New feature: New filter hook `relevanssi_phrase` filters each phrase before it's used in the MySQL query.
* New feature: Relevanssi can now add Google-style missing term lists to the search results. You can either use the `%missing%` tag in the search results breakdown settings, or you can create your own code: the missing terms are also in `$post->missing_terms`. Relevanssi Premium will also add "Must have" links when there's just one missing term.
* New feature: New filter hook `relevanssi_missing_terms_tag` controls which tag is used to wrap the missing terms.
* New feature: New filter hook `relevanssi_missing_terms_template` can be used to filter the template used to display the missing terms.
* New feature: New function `relevanssi_get_post_meta_for_all_posts()` can be used to fetch particular meta field for a number of posts with just one query.
* New feature: New filter hook `relevanssi_post_author` lets you filter the post author display_name before it is indexed.
* Changed behaviour: `relevanssi_strip_tags()` used to add spaces between HTML tags before stripping them. It no longer does that, but instead adds a space after specific list of tags (p, br, h1-h6, div, blockquote, hr, li, img) to avoid words being stuck to each other in excerpts.
* Changed behaviour: Relevanssi now indexes the contents of Oxygen Builder PHP & HTML code blocks.
* Changed behaviour: Relevanssi now handles synonyms inside phrases differently. If the new filter hook `relevanssi_phrase_synonyms` returns `true` (default value), synonyms create a new phrase (with synonym 'dog=hound', phrase `"dog biscuits"` becomes `"dog biscuits" "hound biscuits"`). If the value is `false`, synonyms inside phrases are ignored.
* Minor fix: Warnings when creating excerpts with search terms that contain a slash were removed.
* Minor fix: Better Ninja Tables compatibility to avoid problems with lightbox images.
* Minor fix: Relevanssi did not work well in the Media Library grid view. Relevanssi is now blocked there. If you need Relevanssi in Media Library searches, use the list view.
* Minor fix: Relevanssi excerpt creation didn't work correctly when numerical search terms were used.

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

== Upgrade notice ==
= 4.14.4 =
* Small bug fixes.

= 4.14.3 =
* Security fix: User searches page had a XSS vulnerability.

= 4.14.2 =
* Removes database calls on admin pages.

= 4.14.1 =
* Adds a missing file.

= 4.14.0 =
* User searches page update, bug fixes and improvements.

= 4.13.3.1 =
* Fixes the Bricks compatibility.

= 4.13.3 =
* Bug fixes and small improvements.

= 4.13.2 =
* Small bug and compatibility fixes.

= 4.13.1 =
* Compatibility for WP-Members added.

= 4.13.0 =
* Lots of new features and bug fixes.

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