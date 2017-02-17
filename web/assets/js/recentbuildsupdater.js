function updateRecentBuilds(path) {
    window.setInterval(function (path) {
        $.ajax(path, {
            success: function (data) {
                if (data) {
                    $("#recentbuilds").find("tbody").html(data);
                }
            },
            ifModified: true
        });
    }, 10000, path);
}
