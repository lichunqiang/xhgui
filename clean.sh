#!/usr/bin/env bash

dir=`pwd`

cmd="load('$dir/mongo.js'); cleanUp()"

mongo 10.9.193.137 --eval "$cmd"