=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search, product search, woocommerce search
Requires at least: 4.9
Tested up to: 6.2
Requires PHP: 7.0
Stable tag: 4.20.0
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
= 4.20.0 =
* New feature: Relevanssi can now create custom field specific excerpts that come from one custom field only and know which field that is.
* New feature: You can see the list of indexed custom field names in the indexing and excerpt settings.
* New feature: New filter hook `relevanssi_excerpt_specific_custom_field_content` filters the excerpt custom field content if `relevanssi_excerpt_specific_fields` is enabled.
* Changed behaviour: The `relevanssi_get_custom_field_content()` function now returns an array instead of string. If `relevanssi_excerpt_specific_fields` is off, the previous string return value is returned as a single-item array with the string in index 0. If the setting is on, the array keys are the field names.
* Minor fix: The stopword population during the multisite installation used the wrong database table, leading to failed population.
* Minor fix: Multisite installation is moved from `wp_insert_site` (priority 10) to `wp_initialize_site` (priority 200) in order to avoid trouble.
* Minor fix: The session ID is now included in the log export.
* Minor fix: The "none" value in category dropdowns from the searchform shortcode is changed from -1 to 0.

= 4.19.0 =
* New feature: Logging now includes a session ID (based on user ID for logged-in users, HTTP user agent for others, and current time, stable for 10 minutes per user). This is used to remove duplicate searches from live searches, keeping only the final search query.

= 4.18.4 =
* New feature: New filter hook `relevanssi_highlight_query` lets you modify the search query for highlighting.
* Changed behavior: Relevanssi no longer searches in feed searches by default.

= 4.18.3 =
* New feature: New filter hook `relevanssi_blocked_field_types` can be used to control which ACF field types are excluded from the index. By default, this includes 'repeater', 'flexible_content', and 'group'.
* New feature: New filter hook `relevanssi_acf_field_object` can be used to filter the ACF field object before Relevanssi indexes it. Return false to have Relevanssi ignore the field type.
* New feature: Relevanssi debug mode has more features now.
* Minor fix: ACF field exclusion is now recursive. If a parent field is excluded, all sub fields will also be excluded.
* Minor fix: Handling of data attributes in in-document highlighting had a bug that caused problems with third-party plugins.
* Minor fix: The indexing settings tab now checks if the wp_relevanssi database table exists and will create the table if it doesn't.

= 4.18.2 =
* New feature: Relevanssi now has a debug mode that will help troubleshooting and support.
* Minor fix: Using the_permalink() caused problems with search result links. That is now fixed. Relevanssi no longer hooks onto `the_permalink` hook and instead uses `post_link` and other similar hooks.

= 4.18.1 =
* New feature: New filter hook `relevanssi_add_highlight_and_tracking` can be used to force Relevanssi to add the `highlight` and tracking parameters to permalinks.
* Changed behaviour: The 'relevanssi_wpml_filter' filter function now runs on priority 9 instead of 10 to avoid problems with custom filters on relevanssi_hits_filter.
* Minor fix: Handle cases of missing posts better; relevanssi_get_post() now returns a WP_Error if no post is found.
* Minor fix: Search queries that contain apostrophes and quotes can now be deleted from the log.
* Minor fix: Avoid a slow query on the searching tab when the throttle is not enabled.

= 4.18.0 =
* New feature: Relevanssi now shows the MySQL `max_allowed_packet` size on the debug tab.
* New feature: Relevanssi now shows the indexing query on the debug tab.
* New feature: ACF field settings now include a 'Exclude from Relevanssi index' setting. You can use that to exclude ACF fields from the Relevanssi index.
* Minor fix: Relevanssi was adding extra quotes around search terms in the `highlight` parameter.
* Minor fix: Yet another update to data attributes in highlighting. Thanks to Faeddur.
* Minor fix: Taxonomy query handling was improved. This should help in particular Polylang users who've had problems with Relevanssi ignoring Polylang language restrictions.

== Upgrade notice ==
= 4.20.0 =
* Better method for handling custom fields in excerpts, bug fixes.

= 4.19.0 =
* Logs now include a session ID.

= 4.18.4 =
* No more searching in RSS feeds, new filter hook.

= 4.18.3 =
* Better ACF field controls, bug fixes.

= 4.18.2 =
* Fixes problems with broken permalinks.

= 4.18.1 =
* Small bug fixes.

= 4.18.0 =
* Debugging features, improved ACF support and bug fixes.