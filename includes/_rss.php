<?php
/*
wpClassified V1.2
RSS Feeds
* Author Website : http://www.forgani.com
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
	die('You are not allowed to call this page directly.'); 
}


$filename = $wpClassified->cache_dir . 'wpclassified.xml';
$fp = fopen($filename, 'w');


ob_start();


$wpcSettings = get_option('wpClassified_data');

$limit=$wpcSettings['rss_feed_num'];
if(!isset($limit)) $limit=20;

$pageinfo = $wpClassified->get_pageinfo();

# Get Data
$sql = "SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.status, {$table_prefix}wpClassified_ads.image_file,  {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$table_prefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id, {$table_prefix}wpClassified_ads.post FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$table_prefix}users WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id  AND {$table_prefix}users.id = {$table_prefix}wpClassified_ads.author ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date AND {$table_prefix}wpClassified_ads.status='active' DESC LIMIT 0, ".$limit;


$posts = $wpdb->get_results($sql);

		
# Define Channel Elements
$rssTitle=get_bloginfo('name').' - '.__("Classified");
$pageinfo = $wpClassified->get_pageinfo();
$rssLink = get_bloginfo('wpurl'). "/?page_id=". $pageinfo["ID"]. "&mp;_action=wpcfeed";
$atomLink= $rssLink;
$rssDescription=get_bloginfo('description');
$rssGenerator=__('wp-classified Version ') . '1.4';
		
$rssItem=array();
	

if($posts) {
	foreach($posts as $post){
		# Define Item Elements
		$item = new stdClass;
		$item->title=htmlspecialchars(trim($post->subject));
		$item->pubDate=@date($wpcSettings['date_format'], $post->date); 
		$item->category=htmlspecialchars(trim($post->name));
		$item->post=htmlspecialchars(trim($post->post));
		preg_replace(array('/\s/'), '', $post->image_file);
		if ( !empty($post->image_file) ) {
			$array = preg_split('/\#\#\#/', $post->image_file);
			$item->photo = $array[0];
		}

		/*
		# clean up the content for the plain text email
		$post_content = html_entity_decode($post->post, ENT_QUOTES);
		$post_content = _filter_content($post_content, '');
		$post_content = _filter_nohtml_kses($post_content);
		$post_content = stripslashes($post_content);
		$item->description=$post_content;
		*/

		$item->guid=wpcRssLink("ads_subject", array("name"=>$ad->subject, "lid"=>$post->lists_id, "asid"=>$post->ads_subjects_id));
		$rssItem[]=$item;
	}
}
if (empty($wp)) {
	require_once('wp-config.php');
	wp('feed=rss2');
}

?>
<?php echo '<?xml version="1.0" encoding="' . get_settings('blog_charset') . '"?'.'>'; ?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" 
xmlns:wfw="http://wellformedweb.org/CommentAPI/" 
xmlns:dc="http://purl.org/dc/elements/1.1/" <?php do_action('rss2_ns'); ?>>

<channel>
	<title><?php wpcRssFilter($rssTitle);  ?></title>
	<link><?php echo $wpClassified->plugin_url . '/cache/wpclassified.xml'; ?></link>
	<description><?php wpcRssFilter($rssDescription) ?></description>
	<generator><?php wpcRssFilter($rssGenerator) ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<pubDate><?php echo date("D, d-M-Y"); ?> 00:00:00 UTC</pubDate>

<?php
$contents = ob_get_clean();
fwrite($fp, $contents);
/*
	<atom:link href="<?php echo wpcRssFilter($atomLink) ?>" rel="self" type="application/rss+xml" />
*/
?>
<?php do_action('rss2_head'); ?>
<?php foreach($rssItem as $item): ?>
<?php ob_start(); start_wp();?>
	<item>
		<title><?php echo $item->title ?></title>
		<link><?php echo $item->guid ?></link>
		<category><?php echo $item->category ?></category>
		<guid isPermaLink="true"><?php echo $item->guid ?></guid>
		<!-- dc:creator><?php //the_author() ?></dc:creator -->
		<description><?php echo $item->post ?></description>
		<?php
		if (!empty($item->photo)) {
			?>
			<image>
			<url><?php echo $wpClassified->public_url ."/" . $item->photo; ?></url>
			</image>
			<?php
		}
		?>
		<pubDate><?php echo $item->pubDate ?></pubDate>
	</item>
<?php
	$contents = ob_get_clean();
   fwrite($fp, $contents);
?>
<?php endforeach; ?>
<?php ob_start(); ?>
</channel>
</rss>
<?php
	$contents = ob_get_clean();
	fwrite($fp, $contents);
?>