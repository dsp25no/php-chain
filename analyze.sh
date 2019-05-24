#!/usr/bin/env bash
if [ $# -eq 0 ]
then
    echo "Specify target directory"
    exit 1
fi
target=`realpath $1`

if [ ! -d $target ]
then
   echo "Bad target"
   exit 1
fi

if ! $(docker inspect --type=image php-chain &> /dev/null)
then
   echo "Building container"
   make
fi

echo "Starting analyze"
docker run -ti --rm -v $target:/target:ro -v `pwd`/res:/res php-chain
