<?php
\app\models\core\Design::instance()
    ->addCss('dist/app.css')
    /* FOUC-related directives / http://foundation.zurb.com/sites/docs/responsive-navigation.html#preventing-fouc */
    ->addInlineCss(<<<EOCSS
    .no-js {
        .no-js-hidden {
            display: none;
        }
    }
    EOCSS)
    ->addInlineJsModule(
        <<<EOJS
            window.addEventListener('DOMContentLoaded', function() {
                jQuery(document).foundation();
            });
        EOJS,
        'footer',
        1000
    )
;
