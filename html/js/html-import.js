$(document).ready(function () {

    var components = [];

    $('link[rel="import"]').each(function () {
        var imports = this.import,
            componentName = $(this).data('name'),
            isSingleComponent = $(this).data('issingle'),
            insertEl = imports.querySelector('body');

        components[componentName] = insertEl;

        if (componentName != undefined && isSingleComponent != undefined) {
            var wrapEl = $('#' + componentName);
            wrapEl.append($(insertEl).html());
        }
    });

    if ($.inArray('grid-product-item', components)) {
        for (var i = 0; i < 16; i++) {
            var gridProdHtml = $($(components['grid-product-item']).html());
            $('.grid-product').append(gridProdHtml);
        }
    }


}, false);