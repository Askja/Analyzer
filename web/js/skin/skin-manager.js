$(document).ready(function() {
    $("." + defaultClassesPrefix + "theme").change(function() {
        var selectedTheme = this.value;

        $('link[rel="stylesheet"]').each(function(key, element) {
            if (element.href.indexOf('style') !== -1) {
                application.setTheme(selectedTheme, element);
            }
        });
    });
});