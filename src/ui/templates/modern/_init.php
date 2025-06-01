<?php
\app\models\core\Design::instance()
    ->addCss('dist/app.css')
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
