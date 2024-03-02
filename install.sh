#!/bin/bash

#https://github.com/box-project/box/blob/main/doc/installation.md#installation

if hash box 2>/dev/null; then
    echo "box command found!"
else
  brew tap box-project/box
  brew install box
fi

box compile && sudo mv moo ~/bin/moo
