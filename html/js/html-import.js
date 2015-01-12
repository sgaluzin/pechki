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

    var names = ['Алена Водонаева', 'Ксения Собчак', 'Ирина Шейк', 'Мария Кожевникова', 'Каролина Севастьянова', 'Айза Долматова', 'Кэйт Клэпп', 'Вера Брежнева', 'Ольга Бузова', 'Ксения Бородина'];

    if ($.inArray('cell-photo', components)) {
        for (var i = 0; i < 20; i++) {
            var gridPhotoHtml = $($(components['cell-photo']).html()),
                rand = Math.floor((Math.random() * 10) + 1),
                srcPhoto = 'img/content/photos/cell-'+rand+'.jpg',
                srcAva = 'img/content/thumbs/thumb-'+rand+'.jpg';
            gridPhotoHtml.find('.photo').attr('src', srcPhoto);
            gridPhotoHtml.find('.title').text(names[rand]);
            gridPhotoHtml.find('.thumbnail').attr('src', srcAva);
            $('.grid-photo').append(gridPhotoHtml);
        }
    }

    if ($.inArray('cell-star', components)) {
        for (var i = 0; i < 4; i++) {
            var cellStarHtml = $($(components['cell-star']).html()),
                rand = Math.floor((Math.random() * 10) + 1),
                srcAva = 'img/content/thumbs/thumb-'+rand+'.jpg';
            cellStarHtml.find('.thumbnail').attr('src', srcAva);
            cellStarHtml.find('.title').text(names[rand]);
            $('.grid-stars.bottom-grid-star .stars-wrap').append(cellStarHtml);
        }
    }

    if ($.inArray('cell-star-edit', components)) {
        for (var i = 0; i < 16; i++) {
            var cellStarHtml = $($(components['cell-star-edit']).html()),
                rand = Math.floor((Math.random() * 10) + 1),
                srcAva = 'img/content/thumbs/thumb-'+rand+'.jpg';
            cellStarHtml.find('.thumbnail').attr('src', srcAva);
            cellStarHtml.find('.title').text(names[rand]);
            $('.grid-stars:not(.bottom-grid-star) .stars-wrap').append(cellStarHtml);
        }
        for (var i = 0; i < 6; i++) {
            var cellStarHtml = $($(components['cell-star-edit']).html()),
                rand = Math.floor((Math.random() * 10) + 1),
                srcAva = 'img/content/thumbs/thumb-'+rand+'.jpg';
            cellStarHtml.find('.thumbnail').attr('src', srcAva);
            cellStarHtml.find('.title').text(names[rand]);
            cellStarHtml.attr('class','cell-star subscribed');
            $('.aside.grid-stars .wrap').append(cellStarHtml);
        }
    }
    if ($.inArray('cell-star-subscribe-edit', components)) {
        for (var i = 0; i < 16; i++) {
            var cellStarHtml = $($(components['cell-star-subscribe-edit']).html()),
                rand = Math.floor((Math.random() * 10) + 1),
                srcAva = 'img/content/thumbs/thumb-'+rand+'.jpg';
            cellStarHtml.find('.thumbnail').attr('src', srcAva);
            cellStarHtml.find('.title').text(names[rand]);
            $('.grid-stars:not(.bottom-grid-star) .stars-wrap').append(cellStarHtml);
        }
    }

}, false);