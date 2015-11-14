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
        "jQuery(document).foundation();",
        'footer',
        1000
    )
;
