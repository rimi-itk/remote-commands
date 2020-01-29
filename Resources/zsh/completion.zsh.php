_remote-command()
{
    local state com cur site sites site_command commands options

    site=""
    if [[ ${words[2]:-} != -* ]]; then
        site=${words[2]}
        if [[ ${words[3]:-} != -* ]]; then
            site_command=${words[3]}
        fi
    fi

    cur=${words[${#words[@]}]}

    # lookup for site
    for word in "${words[@]:1}"; do
        if [[ $word != -* ]]; then
            com=$word
            break
        fi
    done

    [[ ${cur} == --* ]] && state="option"

    [[ $cur == $com ]] && state="site"

    if [[ ! -z "$site" && -z "$state" ]]; then
        state="site-command"
    fi

    if [[ ! -z "$site_command" ]]; then
        state="site-command-option"
    fi

    case $state in
        site)
            sites=("${(@f)$(${words[1]} list --no-ansi --raw 2>/dev/null | awk '{ gsub(/:/, "\\:", $1); print }' | awk '{if (NF>1) print $1 ":" substr($0, index($0,$2)); else print $1}')}")
            _describe 'site' sites
        ;;
        site-command)
            commands=("${(@f)$(${words[1]} ${site} --site-completion=command --no-ansi --raw 2>/dev/null | awk '{ gsub(/:/, "\\:", $1); print }' | awk '{if (NF>1) print $1 ":" substr($0, index($0,$2)); else print $1}')}")
            _describe 'command' commands
        ;;
        site-command-option)
            options=("${(@f)$(${words[1]} ${site} ${site_command} --site-completion=command-options --no-ansi 2>/dev/null | sed -n '/Options/,/^$/p' | sed -e '1d;$d' | sed 's/[^--]*\(--.*\)/\1/' | sed -En 's/[^ ]*(-(-[[:alnum:]]+){1,})[[:space:]]+(.*)/\1:\3/p' | awk '{$1=$1};1')}")
            _describe 'option' options
        ;;
    esac
}

compdef _remote-command remote-drupal-drush
compdef _remote-command remote-symfony-console
