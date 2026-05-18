<?php
/** htmlspecialchars wrapper for templating. Use everywhere a PHP value is
 *  echoed into HTML. */
function h($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
