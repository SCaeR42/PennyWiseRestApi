#!/bin/sh
set -e

date +%s > /tmp/started_at

exec "$@"
