#!/bin/bash

if hash pharcc 2>/dev/null; then
    echo "pharcc command found!"
else
    wget https://github.com/cbednarski/pharcc/releases/download/v0.2.3/pharcc.phar
    chmod +x pharcc.phar
    sudo mv pharcc.phar /usr/local/bin/pharcc
fi

pharcc build && sudo mv moo.phar /usr/local/bin/moo
