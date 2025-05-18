<?php
\app\models\core\Design::instance()
    ->addCss(array(
        'app.css',
    ))
    ->addJs(array(
        'foundation/foundation.js',
        'foundation/foundation.topbar.js',
    ), 'footer')
    ->addInlineJs(
        <<<EOJS
            window.addEventListener('DOMContentLoaded', function() {
                (function($) {
                    jQuery(document).foundation();
                })(jQuery);
            });
        EOJS,
        'footer',
        1000
    )
;
