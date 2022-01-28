=== Relevanssi - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/buy-premium/
Tags: search, relevance, better search, product search, woocommerce search
Requires at least: 4.9
Tested up to: 5.9
Requires PHP: 7.0
Stable tag: 4.14.7
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
= 4.14.7 =
* User interface: The synonym settings page now alerts if the synonyms aren't active because of the AND search.

= 4.14.6 =
* Security fix: Extra hardening for AJAX requests. Some AJAX actions in Relevanssi could leak information to site subscribers who knew what to look for.

= 4.14.5 =
* Security fix: Any registered user could empty the Relevanssi index by triggering the index truncate AJAX action. That is no longer possible.
* New feature: The [searchform] shortcode has a new parameter, 'checklist', which you can use to create taxonomy checklists.
* Changed behaviour: The `relevanssi_search_again` parameter array has more parameters the filter can modify.
* Changed behaviour: The `relevanssi_show_matches` filter hook gets the post object as the second parameter.
* Minor fix: The `cats` and `tags` parameters work better and support array values.

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

== Upgrade notice ==
= 4.14.7 =
* Small user interface fixes.

= 4.14.6 =
* Security fix: Extra security checks for AJAX actions.

= 4.14.5 =
* Security fix: registered users could delete the Relevanssi index.

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