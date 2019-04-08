=== Relevanssi Premium - A Better Search ===
Contributors: msaari
Donate link: http://www.relevanssi.com/
Tags: search, relevance, better search
Requires at least: 4.8.3
Tested up to: 5.1
Stable tag: 2.2.5

Relevanssi Premium replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

== Description ==

Relevanssi replaces the standard WordPress search with a better search engine, with lots of features and configurable options. You'll get better results, better presentation of results - your users will thank you.

= Key features =
* Search results sorted in the order of relevance, not by date.
* Fuzzy matching: match partial words, if complete words don't match.
* Find documents matching either just one search term (OR query) or require all words to appear (AND query).
* Search for phrases with quotes, for example "search phrase".
* Create custom excerpts that show where the hit was made, with the search terms highlighted.
* Highlight search terms in the documents when user clicks through search results.
* Search comments, tags, categories and custom fields.

= Advanced features =
* Adjust the weighting for titles, tags and comments.
* Log queries, show most popular queries and recent queries with no hits.
* Restrict searches to categories and tags using a hidden variable or plugin settings.
* Index custom post types and custom taxonomies.
* Index the contents of shortcodes.
* Google-style "Did you mean?" suggestions based on successful user searches.
* Automatic support for [WPML multi-language plugin](http://wpml.org/).
* Automatic support for [s2member membership plugin](http://www.s2member.com/).
* Advanced filtering to help hacking the search results the way you want.
* Search result throttling to improve performance on large databases.
* Disable indexing of post content and post titles with a simple filter hook.
* Multisite support.

= Premium features (only in Relevanssi Premium) =
* PDF content indexing.
* Search result throttling to improve performance on large databases.
* Improved spelling correction in "Did you mean?" suggestions.
* Searching over multiple subsites in one multisite installation.
* Indexing and searching user profiles.
* Weights for post types, including custom post types.
* Limit searches with custom fields.
* Index internal links for the target document (sort of what Google does).
* Search using multiple taxonomies at the same time.

Relevanssi is available in two versions, regular and Premium. Regular Relevanssi is and will remain free to download and use. Relevanssi Premium comes with a cost, but will get all the new features. Standard Relevanssi will be updated to fix bugs, but new features will mostly appear in Premium. Also, support for standard Relevanssi depends very much on my mood and available time. Premium pricing includes support.

= Relevanssi in Facebook =
You can find [Relevanssi in Facebook](https://www.facebook.com/relevanssi). Become a fan to follow the development of the plugin, I'll post updates on bugs, new features and new versions to the Facebook page.

= Other search plugins =
Relevanssi owes a lot to [wpSearch](https://wordpress.org/extend/plugins/wpsearch/) by Kenny Katzgrau. Relevanssi was built to replace wpSearch, when it started to fail.

Search Unleashed is a popular search plugin, but it hasn't been updated since 2010. Relevanssi is in active development and does what Search Unleashed does.



== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

To update your installation, simply overwrite the old files with the new, activate the new version and if the new version has changes in the indexing, rebuild the index.

= Note on updates =
If it seems the plugin doesn't work after an update, the first thing to try is deactivating and reactivating the plugin. If there are changes in the database structure, those changes do not happen without a deactivation, for some reason.

= Changes to templates =
None necessary! Relevanssi uses the standard search form and doesn't usually need any changes in the search results template.

If the search does not bring any results, your theme probably has a query_posts() call in the search results template. That throws Relevanssi off. For more information, see [The most important Relevanssi debugging trick](http://www.relevanssi.com/knowledge-base/query_posts/).

= How to index =
Check the options to make sure they're to your liking, then click "Save indexing options and build the index". If everything's fine, you'll see the Relevanssi options screen again with a message "Indexing successful!"

If something fails, usually the result is a blank screen. The most common problem is a timeout: server ran out of time while indexing. The solution to that is simple: just return to Relevanssi screen (do not just try to reload the blank page) and click "Continue indexing". Indexing will continue. Most databases will get indexed in just few clicks of "Continue indexing". You can follow the process in the "State of the Index": if the amount of documents is growing, the indexing is moving along.

If the indexing gets stuck, something's wrong. I've had trouble with some plugins, for example Flowplayer video player stopped indexing. I had to disable the plugin, index and then activate the plugin again. Try disabling plugins, especially those that use shortcodes, to see if that helps. Relevanssi shows the highest post ID in the index - start troubleshooting from the post or page with the next highest ID. Server error logs may be useful, too.

= Using custom search results =
If you want to use the custom search results, make sure your search results template uses `the_excerpt()` to display the entries, because the plugin creates the custom snippet by replacing the post excerpt.

If you're using a plugin that affects excerpts (like Advanced Excerpt), you may run into some problems. For those cases, I've included the function `relevanssi_the_excerpt()`, which you can use instead of `the_excerpt()`. It prints out the excerpt, but doesn't apply `wp_trim_excerpt()` filters (it does apply `the_content()`, `the_excerpt()`, and `get_the_excerpt()` filters).

To avoid trouble, use the function like this:

`<?php if (function_exists('relevanssi_the_excerpt')) { relevanssi_the_excerpt(); }; ?>`

See Frequently Asked Questions for more instructions on what you can do with Relevanssi.

= The advanced hacker option =
If you're doing something unusual with your search and Relevanssi doesn't work, try using `relevanssi_do_query()`. See [Knowledge Base](http://www.relevanssi.com/knowledge-base/relevanssi_do_query/).

= Uninstalling =
To uninstall the plugin remove the plugin using the normal WordPress plugin management tools (from the Plugins page, first Deactivate, then Delete). If you remove the plugin files manually, the database tables and options will remain.

= Combining with other plugins =
Relevanssi doesn't work with plugins that rely on standard WP search. Those plugins want to access the MySQL queries, for example. That won't do with Relevanssi. [Search Light](http://wordpress.org/extend/plugins/search-light/), for example, won't work with Relevanssi.

Some plugins cause problems when indexing documents. These are generally plugins that use shortcodes to do something somewhat complicated. One such plugin is [MapPress Easy Google Maps](http://wordpress.org/extend/plugins/mappress-google-maps-for-wordpress/). When indexing, you'll get a white screen. To fix the problem, disable either the offending plugin or shortcode expansion in Relevanssi while indexing. After indexing, you can activate the plugin again.

== Frequently Asked Questions ==

= Where is the Relevanssi search box widget? =
There is no Relevanssi search box widget.

Just use the standard search box.

= Where are the user search logs? =
See the top of the admin menu. There's 'User searches'. There. If the logs are empty, please note showing the results needs at least MySQL 5.

= Displaying the number of search results found =

The typical solution to showing the number of search results found does not work with Relevanssi. However, there's a solution that's much easier: the number of search results is stored in a variable within $wp_query. Just add the following code to your search results template:

`<?php echo 'Relevanssi found ' . $wp_query->found_posts . ' hits'; ?>`

= Advanced search result filtering =

If you want to add extra filters to the search results, you can add them using a hook. Relevanssi searches for results in the _relevanssi table, where terms and post_ids are listed. The various filtering methods work by listing either allowed or forbidden post ids in the query WHERE clause. Using the `relevanssi_where` hook you can add your own restrictions to the WHERE clause.

These restrictions must be in the general format of ` AND doc IN (' . {a list of post ids, which could be a subquery} . ')`

For more details, see where the filter is applied in the `relevanssi_search()` function. This is stricly an advanced hacker option for those people who're used to using filters and MySQL WHERE clauses and it is possible to break the search results completely by doing something wrong here.

There's another filter hook, `relevanssi_hits_filter`, which lets you modify the hits directly. The filter passes an array, where index 0 gives the list of hits in the form of an array of post objects and index 1 has the search query as a string. The filter expects you to return an array containing the array of post objects in index 0 (`return array($your_processed_hit_array)`).

= Direct access to query engine =
Relevanssi can't be used in any situation, because it checks the presence of search with the `is_search()` function. This causes some unfortunate limitations and reduces the general usability of the plugin.

You can now access the query engine directly. There's a new function `relevanssi_do_query()`, which can be used to do search queries just about anywhere. The function takes a WP_Query object as a parameter, so you need to store all the search parameters in the object (for example, put the search terms in `$your_query_object->query_vars['s']`). Then just pass the WP_Query object to Relevanssi with `relevanssi_do_query($your_wp_query_object);`.

Relevanssi will process the query and insert the found posts as `$your_query_object->posts`. The query object is passed as reference and modified directly, so there's no return value. The posts array will contain all results that are found.

= Sorting search results =
If you want something else than relevancy ranking, you can use orderby and order parameters. Orderby accepts $post variable attributes and order can be "asc" or "desc". The most relevant attributes here are most likely "post_date" and "comment_count".

If you want to give your users the ability to sort search results by date, you can just add a link to http://www.yourblogdomain.com/?s=search-term&orderby=post_date&order=desc to your search result page.

Order by relevance is either orderby=relevance or no orderby parameter at all.

= Filtering results by date =
You can specify date limits on searches with `by_date` search parameter. You can use it your search result page like this: http://www.yourblogdomain.com/?s=search-term&by_date=1d to offer your visitor the ability to restrict their search to certain time limit (see [RAPLIQ](http://www.rapliq.org/) for a working example).

The date range is always back from the current date and time. Possible units are hour (h), day (d), week (w), month (m) and year (y). So, to see only posts from past week, you could use by_date=7d or by_date=1w.

Using wrong letters for units or impossible date ranges will lead to either defaulting to date or no results at all, depending on case.

Thanks to Charles St-Pierre for the idea.

= Displaying the relevance score =
Relevanssi stores the relevance score it uses to sort results in the $post variable. Just add something like

`echo $post->relevance_score`

to your search results template inside a PHP code block to display the relevance score.

= Did you mean? suggestions =
To use Google-style "did you mean?" suggestions, first enable search query logging. The suggestions are based on logged queries, so without good base of logged queries, the suggestions will be odd and not very useful.

To use the suggestions, add the following line to your search result template, preferably before the have_posts() check:

`<?php if (function_exists('relevanssi_didyoumean')) { relevanssi_didyoumean(get_search_query(), "<p>Did you mean: ", "?</p>", 5); }?>`

The first parameter passes the search term, the second is the text before the result, the third is the text after the result and the number is the amount of search results necessary to not show suggestions. With the default value of 5, suggestions are not shown if the search returns more than 5 hits.

= Search shortcode =
Relevanssi also adds a shortcode to help making links to search results. That way users can easily find more information about a given subject from your blog. The syntax is simple:

`[search]John Doe[/search]`

This will make the text John Doe a link to search results for John Doe. In case you want to link to some other search term than the anchor text (necessary in languages like Finnish), you can use:

`[search term="John Doe"]Mr. John Doe[/search]`

Now the search will be for John Doe, but the anchor says Mr. John Doe.

One more parameter: setting `[search phrase="on"]` will wrap the search term in quotation marks, making it a phrase. This can be useful in some cases.

= Restricting searches to categories and tags =
Relevanssi supports the hidden input field `cat` to restrict searches to certain categories (or tags, since those are pretty much the same). Just add a hidden input field named `cat` in your search form and list the desired category or tag IDs in the `value` field - positive numbers include those categories and tags, negative numbers exclude them.

This input field can only take one category or tag id (a restriction caused by WordPress, not Relevanssi). If you need more, use `cats` and use a comma-separated list of category IDs.

You can also set the restriction from general plugin settings (and then override it in individual search forms with the special field). This works with custom taxonomies as well, just replace `cat` with the name of your taxonomy.

If you want to restrict the search to categories using a dropdown box on the search form, use a code like this:

`<form method="get" action="<?php bloginfo('url'); ?>">
	<div><label class="screen-reader-text" for="s">Search</label>
	<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" />
<?php
	wp_dropdown_categories(array('show_option_all' => 'All categories'));
?>
	<input type="submit" id="searchsubmit" value="Search" />
	</div>
</form>`

This produces a search form with a dropdown box for categories. Do note that this code won't work when placed in a Text widget: either place it directly in the template or use a PHP widget plugin to get a widget that can execute PHP code.

= Restricting searches with taxonomies =

You can use taxonomies to restrict search results to posts and pages tagged with a certain taxonomy term. If you have a custom taxonomy of "People" and want to search entries tagged "John" in this taxonomy, just use `?s=keyword&people=John` in the URL. You should be able to use an input field in the search form to do this, as well - just name the input field with the name of the taxonomy you want to use.

It's also possible to do a dropdown for custom taxonomies, using the same function. Just adjust the arguments like this:

`wp_dropdown_categories(array('show_option_all' => 'All people', 'name' => 'people', 'taxonomy' => 'people'));`

This would do a dropdown box for the "People" taxonomy. The 'name' must be the keyword used in the URL, while 'taxonomy' has the name of the taxonomy.

= Automatic indexing =
Relevanssi indexes changes in documents as soon as they happen. However, changes in shortcoded content won't be registered automatically. If you use lots of shortcodes and dynamic content, you may want to add extra indexing. Here's how to do it:

`if (!wp_next_scheduled('relevanssi_build_index')) {
	wp_schedule_event( time(), 'daily', 'relevanssi_build_index' );
}`

Add the code above in your theme functions.php file so it gets executed. This will cause WordPress to build the index once a day. This is an untested and unsupported feature that may cause trouble and corrupt index if your database is large, so use at your own risk. This was presented at [forum](http://wordpress.org/support/topic/plugin-relevanssi-a-better-search-relevanssi-chron-indexing?replies=2).

= Highlighting terms =
Relevanssi search term highlighting can be used outside search results. You can access the search term highlighting function directly. This can be used for example to highlight search terms in structured search result data that comes from custom fields and isn't normally highlighted by Relevanssi.

Just pass the content you want highlighted through `relevanssi_highlight_terms()` function. The content to highlight is the first parameter, the search query the second. The content with highlights is then returned by the function. Use it like this:

`if (function_exists('relevanssi_highlight_terms')) {
    echo relevanssi_highlight_terms($content, get_search_query());
}
else { echo $content; }`

= Multisite searching =
To search multiple blogs in the same WordPress network, use the `searchblogs` argument. You can add a hidden input field, for example. List the desired blog ids as the value. For example, searchblogs=1,2,3 would search blogs 1, 2, and 3.

The features are very limited in the multiblog search, none of the advanced filtering works, and there'll probably be fairly serious performance issues if searching common words from multiple blogs.

= What is tf * idf weighing? =

It's the basic weighing scheme used in information retrieval. Tf stands for *term frequency* while idf is *inverted document frequency*. Term frequency is simply the number of times the term appears in a document, while document frequency is the number of documents in the database where the term appears.

Thus, the weight of the word for a document increases the more often it appears in the document and the less often it appears in other documents.

= What are stop words? =

Each document database is full of useless words. All the little words that appear in just about every document are completely useless for information retrieval purposes. Basically, their inverted document frequency is really low, so they never have much power in matching. Also, removing those words helps to make the index smaller and searching faster.

== Known issues and To-do's ==
* Known issue: The most common cause of blank screens when indexing is the lack of the mbstring extension. Make sure it's installed.
* Known issue: In general, multiple Loops on the search page may cause surprising results. Please make sure the actual search results are the first loop.
* Known issue: Relevanssi doesn't necessarily play nice with plugins that modify the excerpt. If you're having problems, try using relevanssi_the_excerpt() instead of the_excerpt().
* Known issue: When a tag is removed, Relevanssi index isn't updated until the post is indexed again.

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape.
* Mohib Ebrahim for relentless bug hunting.
* John Blackbourn for amazing internal link feature and other fixes.
* John Calahan for extensive 2.0 beta testing.

== Changelog ==
= 2.2.5 =
* EXISTS and NOT EXISTS didn't work for taxonomy terms in searches.
* WPML post type handling has been improved. If post type allows fallback for default language, Relevanssi will support that.
* Relevanssi now reminds you to set up automatic trimming for the logs. It's a really good idea, otherwise the logs will become bloated, which will hurt search performance.
* New filter: `relevanssi_user_indexing_args` lets you adjust the arguments for the user indexing query, making easy for example to adjust which user roles are indexed.
* API keys are handled better in single installations on multisites.
* The pinning query is significantly faster.
* In some cases, the related posts feature could show wrong number of posts. That's now fixed.
* The Groups posts filter is only applied to public posts to avoid drafts being shown to people who shouldn't see them.
* The `posts_per_page` query variable didn't work; it's now added to the introduced query variables so that it works.
* Relevanssi won't log empty queries anymore.
* WP CLI searches were disabled when Relevanssi is active; it should work now.
* New filter: `relevanssi_pdf_for_parent_query` lets you adjust the MySQL query for fetching PDFs to index with the parent post.
* New filter: `relevanssi_pdf_for_parent_insert_data` lets you manipulate the INSERT data for PDFs indexed with the parent post.
* The default tax query relation was switched from OR to AND to match the WP_Query default behaviour.
* Gutenberg can cause duplicated postmeta fields for pinning and unpinning. Relevanssi will now remove the duplicate post meta when the post is saved.
* When used with WP 5.1, Relevanssi will now use `wp_insert_site` instead of the now-deprecated `wpmu_new_blog`.
* Multisite blog creation is handled better in WP 5.1+.
* Relevanssi now supports Restrict Content Pro permissions.
* The Relevanssi metabox is now only shown for indexed post types.

= 2.2.4.2 =
* Fixes couple of JS issues with the metabox scripts.
* Improves meta key sorting.
* New actions: `pre_relevanssi_related` is triggered before related posts searches and `post_relevanssi_related` after the searches.

= 2.2.4.1 =
* It's now possible to disable thumbnails and titles for related posts.
* The related posts custom template is looked for in the right place.
* Metabox buttons work without Gutenberg.
* Drafts shouldn't appear in the related posts anymore.
* Choosing the default thumbnail for related posts is more pleasant now.
* You can now disable auto-appended related posts for a post.
* You can disable a post so that it won't appear as a related post.

= 2.2.4 =
* New Related posts feature can be used to display related posts for your content.
* Relevanssi metabox on post edit pages is now in the sidebar by default. It doesn't move automatically on older installations, but you can drag it there. It's recommended, as from now on the look will be designed for sidebar use.
* Search performance has been improved.
* Redirects have been improved a bit.
* Multisite searching was broken when sorting posts.
* Using period as a thousands separator could cause weird results.
* Members plugin compatibility has been improved: it's only used if the 'content permissions' feature has been enabled.
* New JetPack taxonomies and post types have been added to the block list so they won't appear in Relevanssi settings.
* Attachment indexing will bypass zip files.
* New filter: `relevanssi_accept_mime_type` lets you filter attachment reading by MIME type.
* New filter: `relevanssi_do_not_read` lets you filter attachment reading by post ID.
* API key field was not shown if Relevanssi was installed on a single site in a multisite network. Now it's shown correctly.
* New filter: `relevanssi_search_form` works exactly like `get_search_form`, but only applies to the Relevanssi shortcode search forms.
* Relevanssi settings page won't let you exclude categories you have restricted the search to.

= 2.2.3.1 =
* The new Redirects feature caused unforeseen consequences. This version should fix them.

= 2.2.3 =
* New Redirects feature lets you redirect keywords directly to specific result pages.
* Choosing "CSS Style" for highlighting was not possible. That is now fixed.
* Gutenberg reusable block indexing was fatally broken with the latest Gutenberg version. That has been updated.
* Relevanssi now by default respects the WooCommerce "exclude from search" setting.
* `post__not_in` still didn't work properly, it does now.
* New filter: `relevanssi_comparison_order` can be used to define the sorting order when sorting the results by post type.
* English stemmer has been improved a bit.
* "Did you mean" process included a very slow query. It is now cached, leading in some cases to massive performance improvements (we're talking about several seconds here).
* Highlights inside `code` and similar blocks are handled better now.

= 2.2.2.1 =
* Fixed broken "User searches" page.
* "How Relevanssi sees this post" is hidden by default.

= 2.2.2 =
* Relevanssi can now index Gutenberg reusable blocks. (This functionality broke once already before release, so that can happen, since Gutenberg is still in very active development.)
* The `post__in` and `post__not_in` parameters didn't work, and are now fixed. `post_parent__in` and `post_parent__not_in` are also improved.
* You can use named meta queries for sorting posts. Meta query sorting is improved in other ways as well.
* Log export didn't work properly.
* The `relevanssi_premium_get_post()` now has a default value (-1) for the blog ID.
* Adding stopwords from the common word list has been fixed.
* The `relevanssi_get_words_having` filter hook is now also applied to the free version Did you mean queries.
* New filters: `relevanssi_1day` and `relevanssi_7days` can be used to adjust the number of days for log displays, so instead of 1, 7 and 30 days you can have anything you want.

= 2.2.1 =
* The admin search has been moved to a separate page and made visible to editors, authors and contributors.
* New filter: `relevanssi_admin_search_capability` can be used to adjust who sees the admin search page.
* "How Relevanssi sees the post" feature on post edit screens actually works now.
* Numeric meta values (`meta_value_num`) are now sorted as numbers and not strings.

= 2.2.0.1 =
* 2.2 claimed to be version 2.1.7.

= 2.2 =
* New feature: Search tab on Relevanssi settings page allows you to perform searches in WP admin using Relevanssi.
* New feature: Post type archives can now be indexed. This will index the label and the description of the post type.
* New feature: Relevanssi now has a privacy mode that blocks outside connections: at the moment that means update checks and indexing attachments with Relevanssi indexing services. This helps keep your site private and can in some configurations speed up plugin page loads a lot.
* New feature: On post edit screens, you can now see how Relevanssi sees the post. This should help debugging indexing issues.
* New feature: You can now export the search log as a CSV file.
* New filter: `relevanssi_entities_inside_pre` and `relevanssi_entities_inside_code` adjust how HTML entities are handled inside `pre` and `code` tags.
* New filter: `relevanssi_attachment_server_url` allows the use of custom attachment reading server.
* User meta fields are indexed as custom fields, not as post content, and the `customfield_detail` column is also filled.
* Pinned posts have `$post->relevanssi_pinned` set to 1 for debugging purposes, but you can also use this for styling the posts in the search results templates.
* Multisite searching updates: exact match bonus and the recency bonus did not work in multisite searches. Now they work.
* Multisite search also had quite a few bugs squashed.
* The basic Did you mean feature has been toned down a bit, to make the suggestions slightly less weird in some cases.
* Post parent parameters now accept 0 as a value, making it easier to search for children of any post or posts without a parent.
* Pinning caused an error message when `fields` was set to `ids`. Now the behaviour is correct, and the pinning code also won't run, unless something is pinned.
* Taxonomy terms and users are handled better in multi-word AND searches.

== Upgrade notice ==

= 2.2.5 =
* Lots of bug fixes and improvements.

= 2.2.4.2 =
* Improvements for Related posts and meta value sorting.

= 2.2.4.1 =
* Improvements for Related posts.

= 2.2.4 =
* New Related posts feature.

= 2.2.3.1 =
* Fix for Redirect issues.

= 2.2.3 =
* New Redirects feature and bug fixes.

= 2.2.2.1 =
* Fixes broken User searches page.

= 2.2.2 =
* Minor improvements here and there, particularly in custom field sorting.

= 2.2.1 =
* Admin search moved, working "How Relevanssi sees this".

= 2.2.0.1 =
* Simple version number fix.

= 2.2 =
* Several new features, lots of bug fixes.