<?php
/*
wpClassified V1.2
RSS Feeds
* Author Website : http://www.forgani.com
*/
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
	die('You are not allowed to call this page directly.'); 
}

global $wpdb, $table_prefix;

$wpcSettings = get_option('wpClassified_data');

$limit=$wpcSettings['rss_feed_num'];
if(!isset($limit)) $limit=15;

$feed= $_GET['wpcfeed'];
# Get Data


$sql = "SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$table_prefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id, {$table_prefix}wpClassified_ads.post FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$table_prefix}users WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id  AND {$table_prefix}users.id = {$table_prefix}wpClassified_ads.author ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date DESC LIMIT 0, ".$limit;
$posts = $wpdb->get_results($sql);

		
# Define Channel Elements
$rssTitle=get_bloginfo('name').' - '.__("Classified");

$rssLink= (get_bloginfo('wpurl')."/".$pageinfo["post_name"].'?page=wpClassified&wpcfeed');
$atomLink= (get_bloginfo('wpurl')."/".$pageinfo["post_name"]."?page=wpClassified&amp;wpcfeed=all");
$rssDescription=get_bloginfo('description');
$rssGenerator=__('wp-classified Version ') . '1.2';
		
$rssItem=array();
	

if($posts)	{
	foreach($posts as $post){
		# Define Item Elements
		$item = new stdClass;			
		$item->title=$post->subject;
		$item->pubDate=@date($wpcSettings['date_format'], $post->date); 
		$item->category=$post->name;
		
		/*
		# clean up the content for the plain text email
		$post_content = html_entity_decode($post->post, ENT_QUOTES);
		$post_content = _filter_content($post_content, '');
		$post_content = _filter_nohtml_kses($post_content);
		$post_content = stripslashes($post_content);
		$item->description=$post_content;
		*/

		$item->guid=create_rss_link("ads_subject", array("name"=>$ad->subject, "lid"=>$post->lists_id, "asid"=>$ad->ads_subjects_id));
		$rssItem[]=$item;
	}
}

# Send headers and XML
header("HTTP/1.1 200 OK");
header('Content-Type: application/xml');
header("Cache-control: max-age=3600");
header("Expires: ".date('r', time()+3600));
header("Pragma: ");
echo'<?xml version="1.0" ?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
	<title><?php rss_filter($rssTitle) ?></title>
	<link><?php $rssLink ?></link>
	<description><![CDATA[<?php rss_filter($rssDescription) ?>]]></description>
	<generator><?php rss_filter($rssGenerator) ?></generator>
	<atom:link href="<?php rss_filter($atomLink) ?>" rel="self" type="application/rss+xml" />
<?php foreach($rssItem as $item): ?>
<item>
	<title><?php rss_filter($item->title) ?></title>
	<link><?php $item->link ?></link>
	<category><?php rss_filter($item->category) ?></category>
	<guid isPermaLink="true"><?php rss_filter($item->guid) ?></guid>
	<description><![CDATA[<?php rss_filter($item->description) ?>]]></description>
	<pubDate><?php rss_filter($item->pubDate) ?></pubDate>
</item>
<?php endforeach; ?>
</channel>
</rss>
