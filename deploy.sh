#!/bin/bash
git add .
git commit -m "Deploy $(date)"
git push server master
ssh -p 32000 admin@13.112.247.169 "cd /var/www/html/joe && git reset --hard"
