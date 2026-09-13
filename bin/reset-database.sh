#!/bin/bash

ddev console doctrine:database:drop --force
ddev console doctrine:database:create
ddev console doctrine:schema:update --force --complete

