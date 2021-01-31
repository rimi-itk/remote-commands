_remote-command()
{
    local state com cur host hosts host_command commands options

    host=""
    if [[ ${words[2]:-} != -* ]]; then
        host=${words[2]}
        if [[ ${words[3]:-} != -* ]]; then
            host_command=${words[3]}
        fi
    fi

    cur=${words[${#words[@]}]}

    # lookup for host
    for word in "${words[@]:1}"; do
        if [[ $word != -* ]]; then
            com=$word
            break
        fi
    done

    [[ ${cur} == --* ]] && state="option"

    [[ $cur == $com ]] && state="host"

    if [[ ! -z "$host" && -z "$state" ]]; then
        state="host-command"
    fi

    if [[ ! -z "$host_command" ]]; then
        state="host-command-option"
    fi

    case $state in
        host)
            hosts=("${(@f)$(${words[1]} --list --no-ansi --raw 2>/dev/null | awk '{ gsub(/:/, "\\:", $1); print }' | awk '{if (NF>1) print $1 ":" substr($0, index($0,$2)); else print $1}')}")
            _describe 'host' hosts
        ;;
        host-command)
            commands=("${(@f)$(${words[1]} --host-completion=command ${host} --no-ansi 2>/dev/null | awk '{ gsub(/:/, "\\:", $1); print }' | awk '{if (NF>1) print $1 ":" substr($0, index($0,$2)); else print $1}')}")
            _describe 'command' commands
        ;;
        host-command-option)
            options=("${(@f)$(${words[1]} --host-completion=command-options ${host} ${host_command} --no-ansi 2>/dev/null | sed -n '/Options/,/^$/p' | sed -e '1d;$d' | sed 's/[^--]*\(--.*\)/\1/' | sed -En 's/[^ ]*(-(-[[:alnum:]]+){1,})[[:space:]]+(.*)/\1:\3/p' | awk '{$1=$1};1')}")
            _describe 'option' options
        ;;
    esac
}

compdef _remote-command remote-drupal-drush
compdef _remote-command remote-symfony-console
