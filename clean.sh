#!/usr/bin/env bash

dir=$(dirname $0)
cmd="load('$dir/mongo.js'); cleanUp()"

mongo --eval "$cmd"
