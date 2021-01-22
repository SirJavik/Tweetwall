<?php
require_once(__DIR__ . "/secrets.php");

$query = filter_input(
    INPUT_GET,
    "query",
    FILTER_SANITIZE_STRING,
    array("flags" => FILTER_FLAG_ENCODE_HIGH)
);

$max_results = filter_input(
    INPUT_GET,
    "max_results",
    FILTER_SANITIZE_NUMBER_INT
);

$curl = curl_init();
$url = "https://api.twitter.com/2/tweets/search/recent?query=" . urlencode($query)
    . "&max_results=$max_results"
    . "&tweet.fields=id,text,attachments&expansions=author_id,attachments.media_keys&media.fields=media_key,type,url,preview_image_url&user.fields=id,name,username,profile_image_url";

curl_setopt($curl, CURLOPT_USERAGENT, "Tweetwall/v" . VERSION . " (https://javik.net)");

curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . BEARER_TOKEN
));
curl_setopt($curl, CURLINFO_HEADER_OUT, true);

curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

$output = json_decode(curl_exec($curl), true);
curl_close($curl);

$data = $output["data"];
$includes = $output["includes"];

function authorAvatarLookup($authorId)
{
    global $includes;

    foreach ($includes["users"] as $user) {
        if ($user["id"] === $authorId) {
            return $user["profile_image_url"];
        }
    }
    return false;
}

function authorLookup($authorId)
{
    global $includes;

    foreach ($includes["users"] as $user) {
        if ($user["id"] === $authorId) {
            return $user["name"];
        }
    }
    return false;
}

function hasTweetMedia($tweet)
{
    if (array_key_exists("attachments", $tweet))
        return true;
    return false;
}

function getTweetMedia($tweet)
{
    global $includes;

    $mediaArray = [];

    foreach ($tweet["attachments"]["media_keys"] as $media_key) {
        foreach ($includes["media"] as $media) {
            if ($media["media_key"] === $media_key) {
                if (empty($media["url"])) {
                    $mediaArray[] = $media_key . ";"
                        . $media["type"] . ";"
                        . $media["preview_image_url"];
                } else {
                    $mediaArray[] = $media_key . ";"
                        . $media["type"] . ";"
                        . $media["url"] ;
                }

            }
        }
    }
    return $mediaArray;
}

foreach ($data as $tweet) {

    $authorName = authorLookup($tweet["author_id"]);
    $authorAvatar = authorAvatarLookup($tweet["author_id"]);
    $text = $tweet["text"];


    // HTML
    ?>
    <div class="tweet-container">
        <img class="tweet-avatar" src="<?php echo $authorAvatar ?>" alt="Avatar"/>
        <span class="tweet-user"><?php echo $authorName ?>:</span>
        <span class="tweet-text"><?php echo $text ?></span>

        <!-- BEGIN IMAGE OR VIDEO -->

        <?php
        if (hasTweetMedia($tweet)) {
            $medias = getTweetMedia($tweet);
            foreach ($medias as $media) {
                $mediaValues = explode(";", $media);
                ?> <img src="<?php echo $mediaValues[2] ?>" alt="<?php echo $mediaValues[2] ?>"/> <?php
            }
        }
        ?>

        <!-- END IMAGE OR VIDEO -->

    </div>
    <?php
    // END HTML
}

