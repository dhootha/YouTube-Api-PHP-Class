<?
require_once "api.php";


$api = new YouTubeAPI();

$result = $api->setApiKey('[YOUR_YOUTUBE_DATA_V3_API_KEY]');
var_dump($result);

$result = $api->getChannelIdByChannelName("InternetzTube2");
var_dump($result);

$result = $api->getChannelDataByChannelId();
var_dump($result);
