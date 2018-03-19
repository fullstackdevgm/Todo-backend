if [ -z "$1" ]; then
    echo "No configuration specified. Usage: $0 <config>"
    exit 1
fi 

CONFIG=$1
KEYFILE=sensitive/todo2014.crt

openssl smime -encrypt -aes256 -binary -outform PEM \
	-in "$CONFIG" \
	"$KEYFILE" \
	| pbcopy

echo "Encrypted payload copied to your clipboard"