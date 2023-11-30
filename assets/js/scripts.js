/**
 * Created by Richard on 19/09/2016.
 */

console.log('ACPAE');
ACPAE = {
    common: {
        init: function () {
            'use strict';
            //uncomment to debug
            console.log('common test login form');

            //add js class
            jQuery('body').addClass('acpaeJs')

        }
    },
    page: {
        init: function () {
            //uncomment to debug
            //console.log('pages');
        }
    },
    post: {
        init: function () {
            //uncomment to debug
            //console.log('posts');
        }
    }
};

ACPAE_UTIL = {
    exec: function (template, handle) {
        var ns = ACPAE,
            handle = (handle === undefined) ? "init" : handle;

        if (template !== '' && ns[template] && typeof ns[template][handle] === 'function') {
            ns[template][handle]();
        }
    },
    init: function () {
        var body = document.body,
            template = body.getAttribute('data-post-type'),
            handle = body.getAttribute('data-post-slug');

        ACPAE_UTIL.exec('common');
        ACPAE_UTIL.exec(template);
        ACPAE_UTIL.exec(template, handle);
    }
};
jQuery(document).ready(ACPAE_UTIL.init);
