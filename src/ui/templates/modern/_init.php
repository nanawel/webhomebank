<?php
\app\models\core\Design::instance()
    ->addCss([
        'app.css',
    ])
    ->addJs([
        'foundation/foundation.js',
        'foundation/foundation.topbar.js',
    ], 'footer')
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
