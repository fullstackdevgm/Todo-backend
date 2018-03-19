#!/bin/bash
echo "Decrypting $1 with key $2, storing the output in $3"
openssl smime -decrypt -inform PEM -binary \
	-in "$1" \
	-inkey "$2" \
	-out "$3"