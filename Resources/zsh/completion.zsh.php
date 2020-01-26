#compdef remote-command

_remote-command() {
    local cur

    cur=${words[${#words[@]}]}

    echo cur: $cur
}

_remote-command "$@"

<?php
// Local variables:
// Mode: sh
// End:
?>
