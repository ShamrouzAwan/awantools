<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$limit = 20;
$posts = $db->fetchAll(
    "SELECT p.*, u.name AS author_name, u.username AS author_username
     FROM blog_posts p
     LEFT JOIN users u ON u.id = p.author_id
     WHERE p.status = 'published'
     ORDER BY p.published_at DESC, p.created_at DESC
     LIMIT ?",
    [$limit]
) ?: [];

$siteName = $settings->get('site_name', 'Awan Tools');
$tagline  = $settings->get('site_tagline', 'Made to Help');
$siteUrl  = rtrim($settings->get('site_url', ''), '/');
if (!$siteUrl) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$feedUrl  = $siteUrl . '/feed';
$lastBuild = !empty($posts) ? date(DATE_RSS, strtotime($posts[0]['published_at'] ?? $posts[0]['created_at'])) : date(DATE_RSS);

header('Content-Type: application/rss+xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title><?= htmlspecialchars($siteName) ?></title>
    <link><?= htmlspecialchars($siteUrl) ?></link>
    <description><?= htmlspecialchars($tagline) ?></description>
    <language>en</language>
    <lastBuildDate><?= $lastBuild ?></lastBuildDate>
    <atom:link href="<?= htmlspecialchars($feedUrl) ?>" rel="self" type="application/rss+xml"/>
    <generator>AWAN Platform</generator>
    <image>
      <url><?= htmlspecialchars($siteUrl . '/assets/img/og-image.png') ?></url>
      <title><?= htmlspecialchars($siteName) ?></title>
      <link><?= htmlspecialchars($siteUrl) ?></link>
    </image>
<?php foreach ($posts as $post):
    $postUrl  = $siteUrl . '/blog/' . $post['slug'];
    $pubDate  = date(DATE_RSS, strtotime($post['published_at'] ?? $post['created_at']));
    $excerpt  = $post['excerpt'] ?? '';
    if (!$excerpt && $post['content']) {
        $excerpt = strip_tags($post['content']);
        $excerpt = preg_replace('/\s+/', ' ', $excerpt);
        $excerpt = htmlspecialchars(mb_substr($excerpt, 0, 280, 'UTF-8')) . (mb_strlen($excerpt, 'UTF-8') > 280 ? '...' : '');
    } else {
        $excerpt = htmlspecialchars($excerpt);
    }
    $author = $post['author_name'] ?: $post['author_username'] ?: $siteName;
?>
    <item>
      <title><?= htmlspecialchars($post['title']) ?></title>
      <link><?= htmlspecialchars($postUrl) ?></link>
      <guid isPermaLink="true"><?= htmlspecialchars($postUrl) ?></guid>
      <pubDate><?= $pubDate ?></pubDate>
      <author><?= htmlspecialchars($author) ?></author>
      <description><?= $excerpt ?></description>
      <?php if ($post['content']): ?>
      <content:encoded><![CDATA[<?= $post['content'] ?>]]></content:encoded>
      <?php endif ?>
      <?php if ($post['cover_image']): ?>
      <enclosure url="<?= htmlspecialchars($siteUrl . $post['cover_image']) ?>" type="image/jpeg" length="0"/>
      <?php endif ?>
    </item>
<?php endforeach ?>
  </channel>
</rss>
