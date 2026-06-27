<?php
header('Content-Type: application/json');
$config = dirname(__FILE__) . '/../configuration.php';
require_once $config;
require_once '../objects/Encoder.php';
require_once dirname(__FILE__) . '/../objects/functions.php';

$opts = array('http' =>
  array( 'timeout' => 1 )
); 
$context  = stream_context_create($opts);

$file = "{$global['systemRootPath']}cache/score.json";
$lifetimeSeconds = 60;
if (file_exists($file)) {
    $fileAge = time() - filemtime($file);
} else {
    $fileAge = $lifetimeSeconds*2;
}
error_log("SCORE ==> fileAge = $fileAge AND lifetimeSeconds = $lifetimeSeconds");

if ($fileAge > $lifetimeSeconds) {
    $encoders = Encoder::getAll();
    $site = array();

    foreach ($encoders as $value) {
        $site[$value['id']]['ping'] = json_decode(url_get_contents("{$global['webSiteRootURL']}ping/{$value['id']}", $context));
        $site[$value['id']]['siteURL'] = $value['siteURL'];
        
        // Get streamer credentials for cross-domain authentication
        $encoder = new Encoder($value['id']);
        $streamer = $encoder->getStreamer();
        $user = '';
        $pass = '';
        $streamerSiteURL = '';
        if ($streamer) {
            $user = $streamer->getUser();
            $pass = $streamer->getPass();
            $streamerSiteURL = $streamer->getSiteURL();
        }
        
        // Build serverStatus URL with authentication parameters
        $serverStatusUrl = buildServerStatusUrl($value['siteURL'], $user, $pass, $streamerSiteURL);
        $site[$value['id']]['serverStatus'] = json_decode(url_get_contents($serverStatusUrl, $context));
    }

    $content = json_encode($site);

    file_put_contents($file, $content);

} else {
    $content = url_get_contents($file);
}

echo $content;
