#!/usr/bin/env bash
set -eu
# TODO swap to -Eeuo pipefail above (after handling all potentially-unset variables)

# usage: docker_process_init_files [file [file [...]]]
#    ie: docker_process_init_files /always-init.d/*
# process initializer files, based on file extensions and permissions
docker_process_init_files() {
    printf '\n'
    local f
    for f; do
        # https://github.com/docker-library/postgres/issues/450#issuecomment-393167936
        # https://github.com/docker-library/postgres/pull/452
        if [ -x "$f" ]; then
            printf '%s: running %s\n' "$0" "$f"
            "$f"
        else
            printf '%s: sourcing %s\n' "$0" "$f"
            . "$f"
        fi
        printf '\n'
    done
}

docker_process_init_files /docker-entrypoint-init.d/*

exec /usr/local/bin/docker-php-entrypoint $@
