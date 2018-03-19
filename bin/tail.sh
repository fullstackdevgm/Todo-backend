#! /bin/bash
tail -f /var/log/apache2/error.log  | sed -e 's/\\n/\n/g'
