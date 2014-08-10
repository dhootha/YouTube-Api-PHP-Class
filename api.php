<?

class YouTubeAPI {

    private $baseUrl = 'https://www.googleapis.com/youtube/v3/';
    private $key = null;
    private $channelId = null;
    private $channelName = null;

    public function setApiKey($key) {
        $url = sprintf('%schannels?part=statistics&forUsername=%s&key=%s', $this->baseUrl, "smosh", $key);
        $document = @file_get_contents($url);
        if($document === false) {
            return false;
        } else {
            $this->key = $key;
            return true;
        }
    }

    public function getCommentsByVideoId($videoId) {
        //Old YouTube API (v2)
        $url = sprintf('https://gdata.youtube.com/feeds/api/videos/%s/comments?orderby=published&alt=json&orderby=published', $videoId);
        $document = @file_get_contents($url);
        if($document === false) {
            return false;
        }
        $document = json_decode($document, true);
        $totalComments = $document["feed"]['openSearch$totalResults']['$t'];
        if($totalComments > 1000) {
            $totalComments = 1000;
        }
        $comments = array();
        $passes = intval($totalComments / 50) +1;
        $commentsPasses = 1;
        for($i = 0; $i < $passes; $i++) {
            $url = sprintf('https://gdata.youtube.com/feeds/api/videos/%s/comments?orderby=published&alt=json&orderby=published&max-results=50&start-index=%d', $videoId, $commentsPasses);
            $document = @file_get_contents($url);
            $document = json_decode($document, true);
            $tempComments = $document["feed"]["entry"];
            if($tempComments !== null) {
                for($j = 0; $j < count($tempComments); $j++) {
                    $comments[] = array(
                        "published"    =>    strtotime($tempComments[$j]["published"]['$t']),
                        "updated"      =>    strtotime($tempComments[$j]["updated"]['$t']),
                        "text"         =>    $tempComments[$j]["content"]['$t'],
                        "displayName"  =>    $tempComments[$j]["author"][0]["name"]['$t'],
                        "channelId"    =>    $tempComments[$j]['yt$channelId']['$t'],
                    );
                }
            }
            $commentsPasses += 50;
        }
        return $comments;
    }

    public function getChannelIdByChannelName($channelName) {
        if($this->key == null) {
            return false;
        } else {
            $url = sprintf('%schannels?part=statistics&forUsername=%s&key=%s', $this->baseUrl, $channelName, $this->key);
            $document = file_get_contents($url);
            $document = json_decode($document, true);

            if(isset($document["items"][0]["id"])) {
                $this->channelId = $document["items"][0]["id"];
                $this->channelName = $channelName;
                return $document["items"][0]["id"];
            } else {
                return false;
            }
        }
    }
    public function getChannelDataByChannelId($channelId = null, $key = null) {
        if($channelId === null) {
            if($this->channelId === null) {
                return false;
            } else {
                $channelId = $this->channelId;
            }
        }
        if($key === null) {
            if($this->key === null) {
                return false;
            } else {
                $key = $this->key;
            }
        }
        
        $channelData = array();
        
        $url = sprintf('%schannels?part=statistics&id=%s&key=%s', $this->baseUrl, $channelId, $key);
        
        $document = @file_get_contents($url);
        $document = json_decode($document, true);
        $channelData["viewCount"] =       $document["items"][0]["statistics"]["viewCount"];
        $channelData["subscriberCount"] = $document["items"][0]["statistics"]["subscriberCount"];
        $channelData["videoCount"] =      $document["items"][0]["statistics"]["videoCount"];
        
        $url = sprintf('%schannels?part=brandingSettings&id=%s&key=%s', $this->baseUrl, $channelId, $key);
        $document = @file_get_contents($url);
        $document = json_decode($document, true);
        $channelData["title"] =           $document["items"][0]["brandingSettings"]["channel"]["title"];
        $channelData["bannerImage"] =     $document["items"][0]["brandingSettings"]["image"]["bannerTabletHdImageUrl"];
        $channelData["trailer"] =         $document["items"][0]["brandingSettings"]["channel"]["unsubscribedTrailer"];
        $channelData["description"] =     $document["items"][0]["brandingSettings"]["channel"]["description"];
        
        $url = sprintf('%schannels?part=contentDetails&id=%s&key=%s', $this->baseUrl, $channelId, $key);
        $document = @file_get_contents($url);
        $document = json_decode($document, true);
        $channelData["googleid"] =        $document["items"][0]["contentDetails"]["googlePlusUserId"];
        
        $url = sprintf('%schannels?part=snippet&fields=items%%2Fsnippet%%2Fthumbnails&id=%s&key=%s', $this->baseUrl, $channelId, $key);
        $document = @file_get_contents($url);
        $document = json_decode($document, true);
        $channelData["profileImage"] =    $document["items"][0]["snippet"]["thumbnails"]["high"]["url"];

        $channelData["network"] =         get_meta_tags(sprintf("http://www.youtube.com/watch?v=%s", $channelData["trailer"]))['attribution'];
        return $channelData;
    }
}
