<?php
if (!isset($_REQUEST['user'])) {
    http_response_code(400);
    print('No username given!');
    die();
}

$user = $_REQUEST['user'];
$url = sprintf('https://www.instagram.com/%s/?__a=1', $user);
$json = file_get_contents($url);

if (!$json) {
    http_response_code(500);
    print('Error fetching data!');
    die();
}

$object = json_decode($json);

if (json_last_error() != JSON_ERROR_NONE) {
    http_response_code(500);
    print('Error parsing data');
    die();
}

header("Content-type: text/xml");
printf(
    "<?xml version='1.0' encoding='UTF-8'?><rss version='2.0'><channel><title>Instagram feed for %s</title>\n",
    $object->graphql->user->full_name
);

foreach ($object->graphql->user->edge_owner_to_timeline_media->edges as $node) {
    $caption = '';
    if (count($node->node->edge_media_to_caption->edges) > 0) {
        $caption = $node->node->edge_media_to_caption->edges[0]->node->text;
    }

    $item = <<<'EOT'
<item>
    <pubDate>%s</pubDate>
    <title>Picture %s</title>
    <guid>%s</guid>
    <link>https://instagram.com/p/%s/</link>
    <description>
        <![CDATA[<img src="%s" alt="%s" /><br />%s]]>
    </description>
</item>\n
EOT;
    printf(
        $item,
        date('r', $node->node->taken_at_timestamp),
        $node->node->id,
        $node->node->id,
        $node->node->shortcode,
        $node->node->display_url,
        $node->node->accessibility_caption,
        $caption
    );
}
print("</channel></rss>\n");
?>
