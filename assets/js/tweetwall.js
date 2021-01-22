function requestTweets(query, count = 10) {
    let uri = "requestTweets.php?query="+encodeURIComponent(query)+"&max_results="+count;
    console.log("URL: "+uri);

    $.ajax({
        url: uri,
        dataType: 'html',
        success: function (data) {
            $('#tweet-maincontainer').html(data);
        },
        error: function (xhr, status) {
            alert("Sorry, there was a problem!");
        },
    });
}

$(document).ready(function () {
    requestTweets(
        config["query"],
        config["max_results"])

    setInterval(
        function() {
            requestTweets(
                config["query"],
                config["max_results"])
        },
        config["interval"]*1000);
});