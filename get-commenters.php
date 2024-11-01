<?php
/*
Plugin Name: Top/Recent Commenters
Version: 1.0
Plugin URI: http://www.coffee2code.com/wp-plugins/
Author: Scott Reilly
Author URI: http://www.coffee2code.com
Description: Retrieve the top commenters or most recent commenters to your site (if called outside "the loop") or for a particular post (if called inside "the loop").

=>> Visit the plugin's homepage for more information and latest updates  <<=

Installation: 

1. Download the file http://www.coffee2code.com/wp-plugins/get-commenters.zip and unzip it into your /wp-content/plugins/ directory.
-OR-
Copy and paste the the code ( http://www.coffee2code.com/wp-plugins/get-commenters.phps ) into a file called get-commenters.php, and put that file into your /wp-content/plugins/ directory.
2. Activate the plugin from your WordPress admin 'Plugins' page.
3. Optional: Look at the code for the 3 configuration options you can change --
	a.) You can determine a unique visitor in one of three ways (by name, email, or url); default is email
	b.) If you want to exclude certain people from the listings, you can list them
	c.) You can set whether links to visitor sites open in a new browser window (default is 'false')
4. Add a call to the function to your template (see examples below).


Example:
<ul>
<?php c2c_get_commenters('top', 3); ?>
</ul>

Outputs:
<ul>
<li><a href="http://www.joebob.org" title="Visit Joe Bob's site">Joe Bob</a> (75)</li>
<li>No Homepage Guy (56)</li>
<li><a href="http://www.suzy.org" title="Visit Suzy's site">Suzy</a> (41)</li>
</ul>

Example:
Recent love from: <?php c2c_get_commenters('recent', 3, '', ', ', true); ?>

Outputs:
Recent love from: <a href="http://www.joebob.org" title="Visit Joe Bob's site">Joe Bob</a>,
No Homepage Guy,
<a href="http://www.suzy.org" title="Visit Suzy's site">Suzy</a>

*/

/*
Copyright (c) 2004-2005 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation 
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, 
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if ( !isset($wpdb->comments) ) {	// For WP1.2 compatibility
	global $tablecomments;
	$wpdb->comments = $tablecomments;
}

function c2c_get_commenters ($type='top', $num_people=5, $before='<li>', $after='</li>', $omit_last_after=false, $do_exclusions=true) {
	if (0 >= $num_people) { return; }
	global $wpdb, $id;
	
	// The field you want to base the identity of the commenter on; must be one of 
	//	'comment_author', 'comment_author_url', or 'comment_author_email'
	$identity_field = 'comment_author_email';
	// If you would like to omit yourself or others from the listings, put the $identity_field values to exclude here:
	//	i.e. if the $identity_field is 'comment_author', then use names; if 'comment_author_email', use email addresses
	//	examples: 
	//		$exclude_from_listing = array('Me', 'Joe Bob', 'Sue');
	//		$exclude_from_listing = array('me@mysite.org', 'somebody@else.com');
	// NOTE: Exclusions are not performed if $do_exclusions is false
	$exclude_from_listing = array();
	// Open links in new browser window?
	$open_in_new_window = false;
	
	$sql = "SELECT comment_author, comment_author_url, comment_author_email, ";
	$sql .= ('top' == $type) ? "COUNT(comment_ID) AS total_comments " : "MAX(comment_date) AS most_recent_comment ";
	$sql .= "FROM $wpdb->comments ";
	$sql .= "WHERE comment_approved = '1' AND comment_author != '' ";
	if (!empty($id)) $sql .= "AND comment_post_ID = '$id' ";
	if ($do_exclusions && !empty($exclude_from_listing)) {
	   foreach ($exclude_from_listing as $exclude) $sql .= "AND $identity_field != '$exclude' ";
	}
	if ($identity_field != 'comment_author') $sql .= "AND $identity_field != '' ";
	$sql .= "GROUP BY $identity_field ORDER BY ";
	$sql .= ('top' == $type) ? "total_comments " : "most_recent_comment ";
	$sql .= "DESC LIMIT $num_people";
	$commenters = $wpdb->get_results($sql);
	if (empty($commenters)) return;
	
	foreach ($commenters as $commenter) {
		echo $before;
		if (!empty($commenter->comment_author_url)) {
			echo '<a title="Visit ' . $commenter->comment_author . '\'s site" href="' . $commenter->comment_author_url . '"';
			if ($open_in_new_window) echo ' target="_blank"';
			echo '>';
		}
		echo $commenter->comment_author;
		if (!empty($commenter->comment_author_url)) echo '</a>';
		if ('top' == $type) echo ' (' . $commenter->total_comments . ')';
		if (!$omit_last_after) echo $after;
		echo "\n";
	}
	return;
} //end c2c_get_commenters()

?>