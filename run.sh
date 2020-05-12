#!/usr/bin/env bash

function parse_args {
    if [ $# -eq 0 ]
    then
        echo "Specify target project directory"
        exit 1
    fi
    target=$(realpath $1)
    EXTRA_DOCKER_OPTIONS+=("-v$target:/target:ro")
}

function parse_chains_args() {
    options=$(getopt -o c: -l chains: -- "$@")
    eval set -- "$options"
    while true; do
        case "$1" in
            -c|--chains)
                chains=$(realpath $2)
                EXTRA_DOCKER_OPTIONS+=("-v$chains:/chains")
                shift 2
            ;;
            --)
                shift
                break
            ;;
            *)
                echo "Unsupport option" >&2
                exit 1
            ;;
        esac
    done
}

function parse_manual_args() {
    options=$(getopt -o c:p: -l chains:port: -- "$@")
    eval set -- "$options"
    while true; do
        case "$1" in
            -c|--chains)
                chains=$(realpath $2)
                EXTRA_DOCKER_OPTIONS+=("-v$chains:/chains")
                shift 2
            ;;
            -p|--port)
                port=$2
                shift 2
            ;;
            --)
                shift
                break
            ;;
            *)
                echo "Unsupport option" >&2
                exit 1
            ;;
        esac
    done

    EXTRA_DOCKER_OPTIONS+=(-p "${port:-8080}":80)
    parse_args "$@"
}

function check_image() {
    if ! docker inspect --type=image php-chain &> /dev/null
    then
       echo "Building container"
       make
    fi
}

function usage() {
    cat <<EOF
Php-chain management tool.

Usage:
  ${0##*/} COMMAND [OPTIONS]

Commands:
  analyze              full analyze
  build_chains         find potential chains in project
  count_metrics        analyze potential chains and score them
  help                 prints this message
  manual_analyze       launch WEB interface, where you can manually
                       analyze chains
EOF
}

white_list=("analyze" "build_chains" "count_metrics" "help" "manual_analyze")

operation="$1"

if [[ -z $operation ]]; then
    echo "Have to specify mode" >&2
    exit 1
fi

shift

if [[ " ${white_list[*]} " != *" $operation "* ]]; then
    echo "Unknown operation '$operation'" >&2
    usage
    exit 1
fi

EXTRA_DOCKER_OPTIONS=()
COMMAND=()

case "$operation" in
    analyze )
        parse_args "$@"
    ;;
    build_chains )
        parse_args "$@"
        EXTRA_DOCKER_OPTIONS+=("--entrypoint" "php")
        COMMAND+=("$operation.php")
    ;;
    count_metrics )
        parse_chains_args "$@"
        EXTRA_DOCKER_OPTIONS+=("--entrypoint" "php")
        COMMAND+=("$operation.php")
    ;;
    help )
        usage
        exit 0
    ;;
    manual_analyze )
        parse_manual_args "$@"
        EXTRA_DOCKER_OPTIONS+=("--entrypoint" "php")
        COMMAND+=(-S 0.0.0.0:80 -t webui)
    ;;
esac

check_image

echo "Starting..."
exec docker run --rm \
     "${EXTRA_DOCKER_OPTIONS[@]}" \
     -v "$(pwd)/res":/res \
     php-chain "${COMMAND[@]}"
