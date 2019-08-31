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
$item = <<<'EOT'
<item>
    <pubDate>%s</pubDate>
    <title>Picture %s</title>
    <guid>%s</guid>
    <link>https://instagram.com/p/%s/</link>
    <description>
        <![CDATA[<img src="%s" alt="%s" /><br />%s]]>
    </description>
</item>
EOT;

foreach ($object->graphql->user->edge_owner_to_timeline_media->edges as $edge) {
    $caption = '';
    if (count($edge->node->edge_media_to_caption->edges) > 0) {
        $caption = $edge->node->edge_media_to_caption->edges[0]->node->text;
    }

    printf(
        $item,
        date('r', $edge->node->taken_at_timestamp),
        $edge->node->id,
        $edge->node->id,
        $edge->node->shortcode,
        $edge->node->display_url,
        $edge->node->accessibility_caption,
        $caption
    );
}
print("</channel></rss>\n");
?>
