<?php
/*
  	Copyright 2009 Mikko Saari (email: mikko@mikkosaari.fi)

    This file is part of Relevanssi, a search plugin for WordPress.

    Relevanssi is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Relevanssi is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Relevanssi.  If not, see <http://www.gnu.org/licenses/>.


 	This was originally written by Kenny Katzgrau for wpSearch plugin,
 	so all credit belongs to him. I've renamed the class to avoid conflicts,
 	but that's it.

 	Original notes from Kenny Katzgrau:
	I hope Wordpress never decides to start checking types. This class
	pretends to be the database row object that WP's EZSQL thing passes
	back on a search.
*/
class RelevanssiSearchResult {
	public $ID; 				// 1
	public $post_author; 		// 1
	public $post_date; 			// 2008-07-13 10:27:30
	public $post_date_gmt; 		// 2008-07-13 14:27:30
	public $post_content; 		// Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!
	public $post_title; 		// Hello world!
	public $post_category; 		// 0
	public $post_excerpt; 		// 
	public $post_status; 		// publish
	public $comment_status; 	// open
	public $ping_status; 		// open
	public $post_password; 		// 
	public $post_name; 			// hello-world
	public $to_ping; 			// 
	public $pinged; 			// 
	public $post_modified; 		// 2008-07-13 10:27:30
	public $post_modified_gmt; 	// 2008-07-13 14:27:30
	public $post_content_filtered; // 
	public $post_parent; 		// 0
	public $guid; 				// http://localhost/wordpress/?p=1
	public $menu_order; 		// 0
	public $post_type; 			// post
	public $post_mime_type; 	// 
	public $comment_count; 		// 1

	public function RelevanssiSearchResult
	($ID, $post_author, $post_date, $post_date_gmt, $post_content, $post_title, $post_category,
	 $post_excerpt, $comment_status, $ping_status, $post_name, $to_ping, $pinged, $post_modified,
	 $post_modified_gmt, $post_content_filtered, $post_parent, $guid, $menu_order, $post_type,
	 $post_mime_type, $comment_count) {
		$this->ID                   = $ID;
		$this->post_author          = $post_author;
		$this->post_date            = $post_date;
		$this->post_date_gmt        = $post_date_gmt;
		$this->post_content         = $post_content;
		$this->post_title           = $post_title;
		$this->post_category        = $post_category;
		$this->post_excerpt         = $post_excerpt;
		$this->post_status          = $post_status;
		$this->comment_status       = $comment_status;
		$this->ping_status          = $ping_status;
		$this->post_name            = $post_name;
		$this->to_ping              = $to_ping;
		$this->pinged               = $pinged;
		$this->post_modified        = $post_modified;
		$this->post_modified_gmt    = $post_modified_gmt;
		$this->post_content_filtered= $post_content_filtered;
		$this->post_parent          = $post_parent;
		$this->guid                 = $guid;
		$this->menu_order           = $menu_order;
		$this->post_type            = $post_type;
		$this->post_mime_type       = $post_mime_type;
		$this->comment_count        = $comment_count;
	}
			
	public static function BuildWPResultFromHit($hit) {
		return new RelevanssiSearchResult($hit->ID, $hit->post_author, $hit->post_date,
			$hit->post_date_gmt, $hit->post_content, $hit->post_title, $hit->post_category,
			$hit->post_excerpt, $hit->comment_status, $hit->ping_status, $hit->post_name,
			$hit->to_ping, $hit->pinged, $hit->post_modified, $hit->post_modified_gmt,
			$hit->post_content_filtered, $hit->post_parent, $hit->guid, $hit->menu_order,
			$hit->post_type, $hit->post_mime_type, $hit->comment_count);
	}
}
?>